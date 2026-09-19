<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

class StockMovement extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'stock_movements';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'product_id',
        'variant_id',
        'quantity',
        'base_unit_id',
        'source_warehouse_id',
        'destination_warehouse_id',
        'source_location_id',
        'destination_location_id',
        'reference_type',
        'reference_id',
        'actor_id',
        'idempotency_key',
        'reason',
        'reversed_movement_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });

        // Strict append-only enforcement (ADR-005)
        static::updating(function () {
            throw new RuntimeException('Stock movements are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Stock movements are immutable and cannot be deleted.');
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reversedMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'reversed_movement_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(StockMovement::class, 'reversed_movement_id');
    }
}
