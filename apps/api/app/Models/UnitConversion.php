<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'unit_conversions';

    protected $fillable = [
        'tenant_id',
        'from_unit_id',
        'to_unit_id',
        'factor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'factor' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
