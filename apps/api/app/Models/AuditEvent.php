<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class AuditEvent extends Model
{
    use HasUuids, BelongsToTenant;

    protected $table = 'audit_events';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'device_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before_payload',
        'after_payload',
        'reason',
        'request_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before_payload' => 'array',
            'after_payload' => 'array',
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

        // Strict append-only enforcement
        static::updating(function () {
            throw new RuntimeException('Audit events are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit events are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
