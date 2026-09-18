<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_data_is_strictly_isolated(): void
    {
        // 1. Create two distinct tenants
        $tenantA = Tenant::create([
            'code' => 'tenant-a',
            'name' => 'Société A',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'code' => 'tenant-b',
            'name' => 'Société B',
            'accounting_currency' => 'EUR',
            'timezone' => 'Europe/Paris',
            'status' => 'active',
        ]);

        // 2. Create users for Tenant A
        TenantContext::setTenantId($tenantA->id);
        $userA = User::create([
            'name' => 'Alice Tenant A',
            'email' => 'alice@tenanta.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $deviceA = Device::create([
            'device_identifier' => 'dev-a-01',
            'name' => 'Terminal A',
            'platform' => 'android',
        ]);

        // 3. Create users for Tenant B
        TenantContext::setTenantId($tenantB->id);
        $userB = User::create([
            'name' => 'Bob Tenant B',
            'email' => 'bob@tenantb.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $deviceB = Device::create([
            'device_identifier' => 'dev-b-01',
            'name' => 'Terminal B',
            'platform' => 'windows',
        ]);

        // 4. Assert isolation in Tenant A context
        TenantContext::setTenantId($tenantA->id);
        $usersFoundA = User::all();
        $this->assertCount(1, $usersFoundA);
        $this->assertEquals($userA->id, $usersFoundA->first()->id);
        $this->assertNull(User::find($userB->id)); // Bob from Tenant B should NOT be visible

        $devicesFoundA = Device::all();
        $this->assertCount(1, $devicesFoundA);
        $this->assertEquals($deviceA->id, $devicesFoundA->first()->id);
        $this->assertNull(Device::find($deviceB->id));

        // 5. Assert isolation in Tenant B context
        TenantContext::setTenantId($tenantB->id);
        $usersFoundB = User::all();
        $this->assertCount(1, $usersFoundB);
        $this->assertEquals($userB->id, $usersFoundB->first()->id);
        $this->assertNull(User::find($userA->id)); // Alice from Tenant A should NOT be visible

        // 6. Assert unique email per tenant constraint allows identical emails across DIFFERENT tenants
        $duplicateEmailInTenantB = User::create([
            'name' => 'Alice in Tenant B',
            'email' => 'alice@tenanta.com', // Same email, but in Tenant B!
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $this->assertNotNull($duplicateEmailInTenantB->id);
        $this->assertEquals($tenantB->id, $duplicateEmailInTenantB->tenant_id);

        TenantContext::clear();
    }
}
