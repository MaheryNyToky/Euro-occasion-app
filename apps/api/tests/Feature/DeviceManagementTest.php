<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_listing_and_revocation(): void
    {
        $tenant = Tenant::create([
            'code' => 'device-test-co',
            'name' => 'Device Test SARL',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Agent Logistique',
            'email' => 'logistique@devicetest.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $device = Device::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'device_identifier' => 'tablet-android-007',
            'name' => 'Tablette Rayon 1',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'last_synced_at' => now(),
            'is_revoked' => false,
        ]);

        $token = $user->createToken("device:{$device->name}")->plainTextToken;

        // 1. User can list devices
        $listResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('X-Device-Id', 'tablet-android-007')
            ->getJson('/api/v1/devices');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.device_identifier', 'tablet-android-007');

        // 2. Revoke device
        $revokeResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/devices/{$device->id}", [
                'reason' => 'Perte de la tablette sur le quai de déchargement',
            ]);

        $revokeResponse->assertStatus(200)
            ->assertJsonPath('data.device.is_revoked', true);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'is_revoked' => true,
        ]);

        // Assert audit log for revocation
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $tenant->id,
            'action' => 'device.revoked',
        ]);

        // 3. Any subsequent requests with this revoked device identifier must be rejected with 403
        // Note: create another token to test header rejection
        $anotherToken = $user->createToken('generic_token')->plainTextToken;

        $blockedResponse = $this->withHeader('Authorization', "Bearer {$anotherToken}")
            ->withHeader('X-Device-Id', 'tablet-android-007')
            ->getJson('/api/v1/me');

        $blockedResponse->assertStatus(403)
            ->assertJson([
                'error' => [
                    'code' => 403,
                    'message' => 'Cet appareil a été révoqué par la direction.',
                ],
            ]);
    }
}
