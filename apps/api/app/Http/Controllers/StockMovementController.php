<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\ReverseStockMovementRequest;
use App\Http\Requests\Stock\StoreStockMovementRequest;
use App\Models\StockMovement;
use App\Services\AuditService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * List stock movements with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockMovement::with([
            'product:id,tenant_id,sku,name',
            'variant:id,tenant_id,product_id,sku,name',
            'baseUnit:id,tenant_id,code,name',
            'sourceWarehouse:id,tenant_id,code,name',
            'destinationWarehouse:id,tenant_id,code,name',
            'actor:id,tenant_id,name,email',
        ])->orderBy('created_at', 'desc');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = $request->input('warehouse_id');
            $query->where(function ($q) use ($warehouseId) {
                $q->where('source_warehouse_id', $warehouseId)
                  ->orWhere('destination_warehouse_id', $warehouseId);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->input('actor_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $limit = min((int) $request->input('limit', 25), 100);
        $movements = $query->paginate($limit);

        return response()->json([
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page'     => $movements->perPage(),
                'total'        => $movements->total(),
                'last_page'    => $movements->lastPage(),
            ],
        ]);
    }

    public function store(StoreStockMovementRequest $request, StockMovementService $service): JsonResponse
    {
        $result = $service->create($request->validated(), $request->user()->id);
        $movement = $result['movement'];

        if (!$result['replayed']) {
            AuditService::log(
                action: 'stock.movement_created',
                auditable: $movement,
                after: $movement->toArray(),
                reason: $movement->reason
            );
        }

        return response()->json([
            'data' => $movement->load(['product', 'baseUnit', 'sourceWarehouse', 'destinationWarehouse']),
            'meta' => ['replayed' => $result['replayed']],
        ], $result['replayed'] ? 200 : 201);
    }

    public function reverse(string $id, ReverseStockMovementRequest $request, StockMovementService $service): JsonResponse
    {
        $result = $service->reverse($id, $request->validated(), $request->user()->id);
        $movement = $result['movement'];

        if (!$result['replayed']) {
            AuditService::log(
                action: 'stock.movement_reversed',
                auditable: $movement,
                after: $movement->toArray(),
                reason: $movement->reason
            );
        }

        return response()->json([
            'data' => $movement->load(['product', 'baseUnit', 'sourceWarehouse', 'destinationWarehouse', 'reversedMovement']),
            'meta' => ['replayed' => $result['replayed']],
        ], $result['replayed'] ? 200 : 201);
    }
}
