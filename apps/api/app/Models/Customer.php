<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'code',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'currency',
        'tax_number',
        'address',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get display name of customer (Company or Full Name).
     */
    public function getDisplayNameAttribute(): string
    {
        if (!empty($this->company_name)) {
            return $this->company_name;
        }

        return trim("{$this->first_name} {$this->last_name}");
    }
}
