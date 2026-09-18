<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_event_is_recorded_and_is_append_only(): void
    {
        $tenant = Tenant::create([
            'code' => 'audit-tenant',
            'name' => 'Société Audit',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        TenantContext::setTenantId($tenant->id);

        $audit = AuditService::log(
            action: 'tenant.updated',
            auditable: $tenant,
            before: ['status' => 'pending'],
            after: ['status' => 'active'],
            reason: 'Validation initiale'
        );

        $this->assertNotNull($audit->id);
        $this->assertEquals('tenant.updated', $audit->action);
        $this->assertEquals($tenant->id, $audit->tenant_id);
        $this->assertEquals(['status' => 'pending'], $audit->before_payload);
        $this->assertEquals(['status' => 'active'], $audit->after_payload);

        // Verify update is forbidden (strict append-only)
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit events are immutable and cannot be updated.');
        $audit->update(['action' => 'tampered']);
    }

    public function test_audit_event_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'code' => 'audit-tenant-del',
            'name' => 'Société Audit Del',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        TenantContext::setTenantId($tenant->id);

        $audit = AuditService::log(
            action: 'security.check',
            auditable: $tenant
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Audit events are immutable and cannot be deleted.');
        $audit->delete();
    }
}
