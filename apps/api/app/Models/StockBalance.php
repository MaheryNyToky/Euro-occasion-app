<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'stock_balances';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'location_id',
        'on_hand',
        'reserved',
        'damaged',
        'in_transit',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'decimal:4',
            'reserved' => 'decimal:4',
            'damaged' => 'decimal:4',
            'in_transit' => 'decimal:4',
            'version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Quantity actually available for sale or transfer (on_hand - reserved - damaged).
     */
    public function getAvailableAttribute(): float
    {
        return (float) $this->on_hand - (float) $this->reserved - (float) $this->damaged;
    }
}
