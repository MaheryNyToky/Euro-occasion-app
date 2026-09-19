<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * Apply a stock command atomically and return the immutable movement.
     */
    public function create(array $data, ?string $actorId = null): array
    {
        return DB::transaction(function () use ($data, $actorId): array {
            $idempotencyKey = $data['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $existing = StockMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return ['movement' => $existing, 'replayed' => true];
                }
            }

            $product = Product::findOrFail($data['product_id']);
            $variant = null;
            if (!empty($data['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)
                    ->findOrFail($data['variant_id']);
            }

            $type = $data['type'];
            $sourceWarehouse = $this->warehouse($data['source_warehouse_id'] ?? null, $type === 'issue' || $type === 'transfer');
            $destinationWarehouse = $this->warehouse($data['destination_warehouse_id'] ?? null, $type === 'receipt' || $type === 'transfer');
            $sourceLocation = $this->location($data['source_location_id'] ?? null, $sourceWarehouse?->id);
            $destinationLocation = $this->location($data['destination_location_id'] ?? null, $destinationWarehouse?->id);

            if ($type === 'transfer' && $sourceWarehouse->id === $destinationWarehouse->id
                && ($sourceLocation?->id === $destinationLocation?->id)) {
                throw ValidationException::withMessages(['destination_warehouse_id' => 'La source et la destination doivent être différentes.']);
            }

            $quantity = (float) $data['quantity'];
            if ($sourceWarehouse) {
                $this->adjustBalance($product, $variant, $sourceWarehouse, $sourceLocation, -$quantity);
            }
            if ($destinationWarehouse) {
                $this->adjustBalance($product, $variant, $destinationWarehouse, $destinationLocation, $quantity);
            }

            $movement = StockMovement::create([
                'type' => $type,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'base_unit_id' => $product->base_unit_id,
                'source_warehouse_id' => $sourceWarehouse?->id,
                'destination_warehouse_id' => $destinationWarehouse?->id,
                'source_location_id' => $sourceLocation?->id,
                'destination_location_id' => $destinationLocation?->id,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'actor_id' => $actorId,
                'idempotency_key' => $idempotencyKey,
                'reason' => $data['reason'] ?? null,
            ]);

            return ['movement' => $movement, 'replayed' => false];
        });
    }

    /**
     * Reverse an existing stock movement atomically and return the immutable reverse movement.
     */
    public function reverse(string $id, array $data, ?string $actorId = null): array
    {
        return DB::transaction(function () use ($id, $data, $actorId): array {
            $idempotencyKey = $data['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $existing = StockMovement::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return ['movement' => $existing, 'replayed' => true];
                }
            }

            $original = StockMovement::findOrFail($id);

            if ($original->type === 'reverse' || !empty($original->reversed_movement_id)) {
                throw ValidationException::withMessages(['movement' => 'Une contre-opération ne peut pas être inversée.']);
            }

            $alreadyReversed = StockMovement::where('reversed_movement_id', $original->id)->first();
            if ($alreadyReversed) {
                throw ValidationException::withMessages(['movement' => 'Ce mouvement a déjà fait l\'objet d\'une contre-opération.']);
            }

            if (!in_array($original->type, ['receipt', 'issue', 'transfer'], true)) {
                throw ValidationException::withMessages(['movement' => "Le type de mouvement {$original->type} ne peut pas être inversé."]);
            }

            $product = Product::findOrFail($original->product_id);
            $variant = $original->variant_id
                ? ProductVariant::where('product_id', $product->id)->find($original->variant_id)
                : null;

            // Invert source and destination
            $sourceWarehouse = $this->warehouse($original->destination_warehouse_id, false);
            $destinationWarehouse = $this->warehouse($original->source_warehouse_id, false);
            $sourceLocation = $this->location($original->destination_location_id, $sourceWarehouse?->id);
            $destinationLocation = $this->location($original->source_location_id, $destinationWarehouse?->id);

            $quantity = (float) $original->quantity;

            if ($sourceWarehouse) {
                $this->adjustBalance($product, $variant, $sourceWarehouse, $sourceLocation, -$quantity);
            }
            if ($destinationWarehouse) {
                $this->adjustBalance($product, $variant, $destinationWarehouse, $destinationLocation, $quantity);
            }

            $movement = StockMovement::create([
                'type' => 'reverse',
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'base_unit_id' => $original->base_unit_id,
                'source_warehouse_id' => $sourceWarehouse?->id,
                'destination_warehouse_id' => $destinationWarehouse?->id,
                'source_location_id' => $sourceLocation?->id,
                'destination_location_id' => $destinationLocation?->id,
                'reference_type' => $data['reference_type'] ?? 'stock_movement',
                'reference_id' => $data['reference_id'] ?? $original->id,
                'actor_id' => $actorId,
                'idempotency_key' => $idempotencyKey,
                'reason' => $data['reason'] ?? "Contre-opération du mouvement {$original->id}",
                'reversed_movement_id' => $original->id,
            ]);

            return ['movement' => $movement, 'replayed' => false];
        });
    }

    private function warehouse(?string $id, bool $required): ?Warehouse
    {
        if (!$id) {
            if ($required) {
                throw ValidationException::withMessages(['warehouse_id' => 'Un entrepôt est requis pour ce type de mouvement.']);
            }
            return null;
        }

        return Warehouse::where('is_active', true)->findOrFail($id);
    }

    private function location(?string $id, ?string $warehouseId): ?Location
    {
        if (!$id) {
            return null;
        }

        return Location::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->findOrFail($id);
    }

    private function adjustBalance(Product $product, ?ProductVariant $variant, Warehouse $warehouse, ?Location $location, float $delta): void
    {
        $query = StockBalance::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id);
        $variant ? $query->where('variant_id', $variant->id) : $query->whereNull('variant_id');
        $location ? $query->where('location_id', $location->id) : $query->whereNull('location_id');

        $balance = $query->lockForUpdate()->first();
        if (!$balance) {
            $balance = StockBalance::create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'warehouse_id' => $warehouse->id,
                'location_id' => $location?->id,
                'on_hand' => 0,
                'version' => 1,
            ]);
        }

        $available = (float) $balance->on_hand - (float) $balance->reserved - (float) $balance->damaged;
        if ($delta < 0 && !$warehouse->allow_negative_stock && $available + $delta < 0) {
            throw ValidationException::withMessages(['quantity' => 'Stock disponible insuffisant pour cette sortie.']);
        }

        $balance->on_hand = (float) $balance->on_hand + $delta;
        $balance->version = (int) $balance->version + 1;
        $balance->save();
    }
}
