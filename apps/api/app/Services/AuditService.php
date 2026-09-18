<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Record an audit event.
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?string $tenantId = null,
        ?string $userId = null,
        ?string $deviceId = null
    ): AuditEvent {
        $request = request();

        $resolvedTenantId = $tenantId ?? TenantContext::getTenantId();
        $resolvedUserId = $userId ?? ($request?->user()?->id ?? null);
        
        $resolvedDeviceId = null;
        if (!empty($deviceId)) {
            $resolvedDeviceId = Str::isUuid($deviceId) 
                ? $deviceId 
                : \App\Models\Device::withoutGlobalScopes()
                    ->where('tenant_id', $resolvedTenantId)
                    ->where('device_identifier', $deviceId)
                    ->value('id');
        } elseif ($headerDeviceId = $request?->header('X-Device-Id')) {
            $resolvedDeviceId = \App\Models\Device::withoutGlobalScopes()
                ->where('tenant_id', $resolvedTenantId)
                ->where('device_identifier', $headerDeviceId)
                ->value('id');
        }

        $requestId = $request?->attributes?->get('request_id') ?? (string) Str::uuid();

        return AuditEvent::create([
            'tenant_id' => $resolvedTenantId,
            'user_id' => $resolvedUserId,
            'device_id' => $resolvedDeviceId,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? (string) $auditable->getKey() : null,
            'before_payload' => $before,
            'after_payload' => $after,
            'reason' => $reason,
            'request_id' => $requestId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
