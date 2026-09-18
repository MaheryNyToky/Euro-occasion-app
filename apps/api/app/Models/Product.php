<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'base_unit_id',
        'sku',
        'reference',
        'manufacturer',
        'name',
        'description',
        'observation',
        'pieces_per_carton',
        'state',
        'has_variants',
        'requires_serial_number',
        'alert_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'has_variants' => 'boolean',
            'requires_serial_number' => 'boolean',
            'alert_threshold' => 'decimal:4',
            'pieces_per_carton' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /**
     * Logically archive product without destructive delete.
     */
    public function archive(): bool
    {
        $this->is_active = false;
        $this->save();
        return (bool) $this->delete();
    }
}
