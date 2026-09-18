<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'devices';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'device_identifier',
        'name',
        'platform',
        'app_version',
        'last_synced_at',
        'last_ip',
        'is_revoked',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    /**
     * User associated with this device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
