<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'units';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'precision',
        'is_base',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'precision' => 'integer',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }
}
