<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockQueryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Product $product;
    private Warehouse $warehouse;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code' => 'query-co',
            'name' => 'Query Entreprise',
            'accounting_currency' => 'MGA',
            'status' => 'active',
        ]);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Responsable Stock',
            'email' => 'stock-query@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->token = $this->user->createToken('query-test')->plainTextToken;
        TenantContext::setTenantId($this->tenant->id);

        $unit = Unit::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'PCS',
            'name' => 'Pièce',
            'precision' => 0,
            'is_base' => true,
        ]);
        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'base_unit_id' => $unit->id,
            'sku' => 'QUERY-001',
            'name' => 'Produit de consultation',
            'state' => 'new',
            'is_active' => true,
        ]);
        $site = Site::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'SITE-Q',
            'name' => 'Site requête',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $site->id,
            'code' => 'WH-Q',
            'name' => 'Entrepôt requête',
            'allow_negative_stock' => false,
            'is_active' => true,
        ]);
    }

    // ---------------------------------------------------------------
    // GET /api/v1/stock-movements
    // ---------------------------------------------------------------

    public function test_list_movements_returns_only_tenant_movements(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();
        $this->postMovement(['type' => 'receipt', 'quantity' => 3])->assertCreated();

        $resp = $this->getMovements()->assertOk();
        $this->assertCount(2, $resp->json('data'));
        $this->assertEquals(2, $resp->json('meta.total'));
    }

    public function test_list_movements_returns_empty_for_new_tenant(): void
    {
        $resp = $this->getMovements()->assertOk();
        $this->assertCount(0, $resp->json('data'));
        $this->assertEquals(0, $resp->json('meta.total'));
    }

    public function test_list_movements_filter_by_product_id(): void
    {
        // Create a second product
        $unit = Unit::where('tenant_id', $this->tenant->id)->first();
        $otherProduct = Product::create([
            'tenant_id' => $this->tenant->id,
            'base_unit_id' => $unit->id,
            'sku' => 'QUERY-002',
            'name' => 'Autre produit',
            'state' => 'new',
            'is_active' => true,
        ]);

        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();
        $this->postMovement(['type' => 'receipt', 'quantity' => 3, 'product_id_override' => $otherProduct->id])->assertCreated();

        $resp = $this->getMovements(['product_id' => $this->product->id])->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals($this->product->id, $resp->json('data.0.product_id'));
    }

    public function test_list_movements_filter_by_type(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 10])->assertCreated();
        $this->postMovement(['type' => 'issue', 'quantity' => 3])->assertCreated();

        $resp = $this->getMovements(['type' => 'receipt'])->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals('receipt', $resp->json('data.0.type'));
    }

    public function test_list_movements_filter_by_warehouse_id(): void
    {
        $other = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->warehouse->site_id,
            'code' => 'WH-OTHER',
            'name' => 'Autre entrepôt',
            'is_active' => true,
        ]);

        $this->postMovement(['type' => 'receipt', 'quantity' => 10])->assertCreated();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/stock-movements', [
                'type' => 'transfer',
                'product_id' => $this->product->id,
                'source_warehouse_id' => $this->warehouse->id,
                'destination_warehouse_id' => $other->id,
                'quantity' => 2,
            ])->assertCreated();

        // warehouse_id filtre sur source OR destination
        $resp = $this->getMovements(['warehouse_id' => $other->id])->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals('transfer', $resp->json('data.0.type'));
    }

    public function test_list_movements_filter_by_date_range(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();

        $resp = $this->getMovements([
            'from' => now()->toDateString(),
            'to'   => now()->toDateString(),
        ])->assertOk();
        $this->assertCount(1, $resp->json('data'));

        $resp2 = $this->getMovements([
            'from' => now()->addDay()->toDateString(),
        ])->assertOk();
        $this->assertCount(0, $resp2->json('data'));
    }

    public function test_list_movements_pagination_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postMovement(['type' => 'receipt', 'quantity' => 1])->assertCreated();
        }

        $resp = $this->getMovements(['limit' => 2])->assertOk();
        $this->assertCount(2, $resp->json('data'));
        $this->assertEquals(5, $resp->json('meta.total'));
        $this->assertEquals(3, $resp->json('meta.last_page'));
    }

    public function test_list_movements_tenant_isolation(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();

        $otherTenant = Tenant::create([
            'code' => 'other-q',
            'name' => 'Autre tenant',
            'accounting_currency' => 'MGA',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Autre',
            'email' => 'other-q@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $otherToken = $otherUser->createToken('other-q')->plainTextToken;

        app('auth')->forgetGuards();

        $resp = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson('/api/v1/stock-movements')
            ->assertOk();
        $this->assertCount(0, $resp->json('data'));
    }

    // ---------------------------------------------------------------
    // GET /api/v1/stock-balances
    // ---------------------------------------------------------------

    public function test_list_balances_returns_only_tenant_balances(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 8])->assertCreated();

        $resp = $this->getBalances()->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals('8.0000', $resp->json('data.0.on_hand'));
        $this->assertEquals($this->warehouse->id, $resp->json('data.0.warehouse_id'));
    }

    public function test_list_balances_filter_by_warehouse_id(): void
    {
        $other = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->warehouse->site_id,
            'code' => 'WH-BAL2',
            'name' => 'Entrepôt balance 2',
            'is_active' => true,
        ]);

        $this->postMovement(['type' => 'receipt', 'quantity' => 10])->assertCreated();
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/stock-movements', [
                'type' => 'transfer',
                'product_id' => $this->product->id,
                'source_warehouse_id' => $this->warehouse->id,
                'destination_warehouse_id' => $other->id,
                'quantity' => 4,
            ])->assertCreated();

        $resp = $this->getBalances(['warehouse_id' => $other->id])->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals('4.0000', $resp->json('data.0.on_hand'));
    }

    public function test_list_balances_filter_available_only(): void
    {
        // Create a balance with on_hand=0 (never moved — seed directly)
        StockBalance::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 0,
            'version' => 1,
        ]);

        // Ensure this is the only record
        $resp = $this->getBalances()->assertOk();
        $this->assertCount(1, $resp->json('data'));

        // available_only should exclude on_hand=0 lines
        $resp2 = $this->getBalances(['available_only' => 'true'])->assertOk();
        $this->assertCount(0, $resp2->json('data'));

        // Now add some stock
        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();

        $resp3 = $this->getBalances(['available_only' => 'true'])->assertOk();
        $this->assertCount(1, $resp3->json('data'));
        $this->assertArrayHasKey('available', $resp3->json('data.0'));
    }

    public function test_list_balances_includes_available_computed_field(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 10])->assertCreated();

        $resp = $this->getBalances()->assertOk();
        $balance = $resp->json('data.0');
        $this->assertArrayHasKey('available', $balance);
        $this->assertEquals(10.0, (float) $balance['available']);
    }

    public function test_list_balances_filter_by_product_id(): void
    {
        $unit = Unit::where('tenant_id', $this->tenant->id)->first();
        $otherProduct = Product::create([
            'tenant_id' => $this->tenant->id,
            'base_unit_id' => $unit->id,
            'sku' => 'QUERY-003',
            'name' => 'Produit balance 3',
            'state' => 'new',
            'is_active' => true,
        ]);

        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();

        // Receipt for other product
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/stock-movements', [
                'type' => 'receipt',
                'product_id' => $otherProduct->id,
                'destination_warehouse_id' => $this->warehouse->id,
                'quantity' => 7,
            ])->assertCreated();

        $resp = $this->getBalances(['product_id' => $this->product->id])->assertOk();
        $this->assertCount(1, $resp->json('data'));
        $this->assertEquals($this->product->id, $resp->json('data.0.product_id'));
    }

    public function test_list_balances_tenant_isolation(): void
    {
        $this->postMovement(['type' => 'receipt', 'quantity' => 5])->assertCreated();

        $otherTenant = Tenant::create([
            'code' => 'other-bal',
            'name' => 'Autre tenant bal',
            'accounting_currency' => 'MGA',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Autre',
            'email' => 'other-bal@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $otherToken = $otherUser->createToken('other-bal')->plainTextToken;

        app('auth')->forgetGuards();

        $resp = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->getJson('/api/v1/stock-balances')
            ->assertOk();
        $this->assertCount(0, $resp->json('data'));
    }

    public function test_list_warehouses_returns_active_warehouses_for_current_tenant(): void
    {
        Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->warehouse->site_id,
            'code' => 'WH-INACTIVE',
            'name' => 'Entrepôt inactif',
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/warehouses')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->warehouse->id, $response->json('data.0.id'));
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function postMovement(array $overrides): \Illuminate\Testing\TestResponse
    {
        $payload = [
            'type' => $overrides['type'],
            'product_id' => $overrides['product_id_override'] ?? $this->product->id,
            'quantity' => $overrides['quantity'],
        ];

        if ($overrides['type'] === 'receipt') {
            $payload['destination_warehouse_id'] = $this->warehouse->id;
        } elseif ($overrides['type'] === 'issue') {
            $payload['source_warehouse_id'] = $this->warehouse->id;
        }

        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/stock-movements', $payload);
    }

    private function getMovements(array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/stock-movements?' . http_build_query($params));
    }

    private function getBalances(array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/stock-balances?' . http_build_query($params));
    }
}
