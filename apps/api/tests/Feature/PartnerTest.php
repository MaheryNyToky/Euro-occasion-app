<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private User $userA;
    private string $tokenA;

    private Tenant $tenantB;
    private User $userB;
    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'code' => 'partner-a',
            'name' => 'Entreprise Partner A',
            'status' => 'active',
        ]);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Agent A',
            'email' => 'agent@partnera.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->tokenA = $this->userA->createToken('tokenA')->plainTextToken;

        $this->tenantB = Tenant::create([
            'code' => 'partner-b',
            'name' => 'Entreprise Partner B',
            'status' => 'active',
        ]);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Agent B',
            'email' => 'agent@partnerb.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->tokenB = $this->userB->createToken('tokenB')->plainTextToken;
    }

    public function test_suppliers_and_customers_crud_and_isolation(): void
    {
        // 1. Create Supplier in Tenant A
        $supplierResponse = $this->withHeader('Authorization', "Bearer {$this->tokenA}")
            ->postJson('/api/v1/suppliers', [
                'code' => 'FOURN-001',
                'company_name' => 'Fournisseur International SARL',
                'contact_name' => 'M. Dupont',
                'email' => 'contact@fournisseur.com',
                'currency' => 'EUR',
                'payment_terms_days' => 30,
            ]);
        $supplierResponse->assertStatus(201)
            ->assertJsonPath('data.company_name', 'Fournisseur International SARL');

        // 2. Create Customer in Tenant A
        $customerResponse = $this->withHeader('Authorization', "Bearer {$this->tokenA}")
            ->postJson('/api/v1/customers', [
                'code' => 'CLI-001',
                'company_name' => 'Société Client Madagascar',
                'email' => 'client@mada.mg',
                'credit_limit' => 5000000.00,
            ]);
        $customerResponse->assertStatus(201)
            ->assertJsonPath('data.credit_limit', '5000000.00');

        // 3. Verify Tenant B cannot see Tenant A's suppliers or customers
        app('auth')->forgetGuards();

        $listSuppliersB = $this->withHeader('Authorization', "Bearer {$this->tokenB}")
            ->getJson('/api/v1/suppliers');
        $listSuppliersB->assertStatus(200)
            ->assertJsonCount(0, 'data');

        $listCustomersB = $this->withHeader('Authorization', "Bearer {$this->tokenB}")
            ->getJson('/api/v1/customers');
        $listCustomersB->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
