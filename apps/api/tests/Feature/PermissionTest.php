<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateWithTenant;
use App\Http\Middleware\CheckPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code' => 'rbac-tenant',
            'name' => 'RBAC Entreprise',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jean Magasinier',
            'email' => 'jean@rbac.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        \App\Services\TenantContext::setTenantId($this->tenant->id);

        // Define a test route protected by CheckPermission
        Route::middleware(['auth:sanctum', AuthenticateWithTenant::class, CheckPermission::class.':stock.create'])
            ->post('/api/v1/test/stock-movements', function () {
                return response()->json(['status' => 'success', 'message' => 'Stock created']);
            });

        Route::middleware(['auth:sanctum', AuthenticateWithTenant::class, CheckPermission::class.':stock.view_cost'])
            ->get('/api/v1/test/stock-costs', function () {
                return response()->json(['status' => 'success', 'cost' => '10000.00']);
            });
    }

    public function test_global_permission_allows_access(): void
    {
        $stockCreatePermission = Permission::create([
            'name' => 'Créer mouvement de stock',
            'code' => 'stock.create',
            'category' => 'stock',
        ]);

        $role = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gestionnaire Stock',
            'code' => 'stock_manager',
        ]);

        $role->permissions()->attach($stockCreatePermission->id);

        // Assign global role to user
        $this->user->assignRole($role);

        $this->assertTrue($this->user->hasPermissionTo('stock.create'));
        // Sensitive permission should NOT be granted
        $this->assertFalse($this->user->hasPermissionTo('stock.view_cost'));

        $token = $this->user->createToken('test_token')->plainTextToken;

        // User can access route requiring stock.create
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/test/stock-movements');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // User is forbidden (403) from accessing route requiring stock.view_cost
        $forbiddenResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/test/stock-costs');

        $forbiddenResponse->assertStatus(403)
            ->assertJson([
                'error' => [
                    'code' => 403,
                    'message' => "Accès refusé : la permission 'stock.view_cost' est requise pour cette opération.",
                ],
            ]);
    }

    public function test_scoped_permission_enforcement(): void
    {
        $stockTransferPerm = Permission::create([
            'name' => 'Transférer stock',
            'code' => 'stock.transfer',
            'category' => 'stock',
        ]);

        $role = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Responsable Entrepôt Local',
            'code' => 'warehouse_supervisor',
        ]);

        $role->permissions()->attach($stockTransferPerm->id);

        $warehouse1Id = (string) Str::uuid();
        $warehouse2Id = (string) Str::uuid();

        // Assign role scoped specifically to warehouse1
        $this->user->assignRole($role, 'warehouse', $warehouse1Id);

        // Must have permission for warehouse1
        $this->assertTrue($this->user->hasPermissionTo('stock.transfer', 'warehouse', $warehouse1Id));

        // Must NOT have permission for warehouse2
        $this->assertFalse($this->user->hasPermissionTo('stock.transfer', 'warehouse', $warehouse2Id));
    }
}
