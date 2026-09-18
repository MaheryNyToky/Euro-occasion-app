<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code' => 'eurocasion',
            'name' => 'Eurocasion SARL',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Directeur Test',
            'email' => 'admin@eurocasion.mg',
            'password' => Hash::make('SecretPass123!'),
            'status' => 'active',
        ]);
    }

    public function test_user_can_login_with_valid_credentials_and_device(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'eurocasion',
            'email' => 'admin@eurocasion.mg',
            'password' => 'SecretPass123!',
            'device_identifier' => 'device-win-001',
            'device_name' => 'Poste Principal Windows',
            'platform' => 'windows',
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-Request-Id')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'status', 'permissions'],
                    'tenant' => ['id', 'code', 'name', 'accounting_currency', 'timezone'],
                    'device' => ['id', 'device_identifier', 'name', 'platform'],
                ],
            ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // Assert device was stored
        $this->assertDatabaseHas('devices', [
            'tenant_id' => $this->tenant->id,
            'device_identifier' => 'device-win-001',
            'platform' => 'windows',
        ]);

        // Assert audit event was recorded
        $this->assertDatabaseHas('audit_events', [
            'tenant_id' => $this->tenant->id,
            'action' => 'auth.login',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'eurocasion',
            'email' => 'admin@eurocasion.mg',
            'password' => 'BadPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 401,
                    'message' => 'Identifiants ou entreprise invalides.',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_tenant(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'nonexistent_company',
            'email' => 'admin@eurocasion.mg',
            'password' => 'SecretPass123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 401,
                    'message' => 'Identifiants ou entreprise invalides.',
                ],
            ]);
    }

    public function test_login_fails_if_user_is_inactive(): void
    {
        $this->user->update(['status' => 'suspended']);

        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'eurocasion',
            'email' => 'admin@eurocasion.mg',
            'password' => 'SecretPass123!',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => [
                    'code' => 403,
                    'message' => 'Ce compte utilisateur a été désactivé.',
                ],
            ]);
    }

    public function test_me_returns_profile_for_authenticated_user(): void
    {
        $token = $this->user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->user->id,
                    'email' => 'admin@eurocasion.mg',
                    'tenant' => [
                        'id' => $this->tenant->id,
                        'code' => 'eurocasion',
                    ],
                ],
            ]);
    }

    public function test_token_can_be_refreshed(): void
    {
        $oldTokenResult = $this->user->createToken('token_to_refresh');
        $oldToken = $oldTokenResult->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'token_type'],
            ]);

        $newToken = $response->json('data.token');
        $this->assertNotEquals($oldToken, $newToken);

        // Assert old token was deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $oldTokenResult->accessToken->id,
        ]);

        app('auth')->forgetGuards();

        // Old token should no longer work
        $failedResponse = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/me');
        $failedResponse->assertStatus(401);

        app('auth')->forgetGuards();

        // New token should work
        $successResponse = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/me');
        $successResponse->assertStatus(200);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $tokenResult = $this->user->createToken('token_to_logout');
        $token = $tokenResult->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Déconnexion réussie.',
                ],
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id,
        ]);

        app('auth')->forgetGuards();

        // Attempting to access /me with revoked token should fail
        $deniedResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/me');
        $deniedResponse->assertStatus(401);
    }
}
