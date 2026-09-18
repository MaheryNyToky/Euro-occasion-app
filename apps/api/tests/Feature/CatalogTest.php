<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code' => 'catalog-co',
            'name' => 'Catalog Entreprise',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Chef Catalogue',
            'email' => 'chef@catalog.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
        TenantContext::setTenantId($this->tenant->id);

        $this->unit = Unit::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'U',
            'name' => 'Pièce',
            'precision' => 0,
            'is_base' => true,
        ]);
    }

    public function test_categories_can_be_created_with_hierarchy(): void
    {
        // 1. Parent category
        $parentResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/categories', [
                'code' => 'INFORMATIQUE',
                'name' => 'Matériel Informatique',
            ]);
        $parentResponse->assertStatus(201);
        $parentId = $parentResponse->json('data.id');

        // 2. Child category
        $childResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/categories', [
                'code' => 'LAPTOPS',
                'name' => 'Ordinateurs Portables',
                'parent_id' => $parentId,
            ]);
        $childResponse->assertStatus(201);

        // 3. List categories
        $listResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/categories');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.children');
    }

    public function test_product_with_variants_creation_and_unique_sku(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'SMARTPHONES',
            'name' => 'Téléphones & Smartphones',
        ]);

        $productData = [
            'sku' => 'IPHONE-13-128',
            'name' => 'Apple iPhone 13 128Go',
            'category_id' => $category->id,
            'base_unit_id' => $this->unit->id,
            'state' => 'used',
            'alert_threshold' => 3,
            'has_variants' => true,
            'variants' => [
                [
                    'sku' => 'IPHONE-13-128-NOIR',
                    'name' => 'Noir Minuit',
                    'qr_code' => 'QR-IPHONE13-NOIR',
                    'attribute_values' => ['color' => 'Noir Minuit'],
                ],
                [
                    'sku' => 'IPHONE-13-128-BLEU',
                    'name' => 'Bleu',
                    'qr_code' => 'QR-IPHONE13-BLEU',
                    'attribute_values' => ['color' => 'Bleu'],
                ],
            ],
        ];

        // 1. Create product
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.sku', 'IPHONE-13-128')
            ->assertJsonPath('data.state', 'used')
            ->assertJsonCount(2, 'data.variants');

        $productId = $response->json('data.id');

        // 2. Duplicate SKU in same tenant should fail validation (422)
        $duplicateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/products', [
                'sku' => 'IPHONE-13-128',
                'name' => 'Autre produit même SKU',
                'base_unit_id' => $this->unit->id,
            ]);
        $duplicateResponse->assertStatus(422);

        // 3. Same SKU in a different tenant MUST be accepted
        $otherTenant = Tenant::create([
            'code' => 'other-tenant',
            'name' => 'Autre Société',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Autre Agent',
            'email' => 'agent@other.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $otherUnit = Unit::create([
            'tenant_id' => $otherTenant->id,
            'code' => 'U',
            'name' => 'Pièce',
        ]);
        $otherToken = $otherUser->createToken('other')->plainTextToken;

        app('auth')->forgetGuards();

        $otherTenantResponse = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson('/api/v1/products', [
                'sku' => 'IPHONE-13-128', // Identical SKU
                'name' => 'Produit dans un autre tenant',
                'base_unit_id' => $otherUnit->id,
            ]);
        $otherTenantResponse->assertStatus(201);

        app('auth')->forgetGuards();

        // 4. Archive product in first tenant (soft delete)
        $archiveResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/products/{$productId}/archive", [
                'reason' => 'Fin de commercialisation du modèle',
            ]);
        $archiveResponse->assertStatus(200);

        // Product is soft deleted in database, not permanently deleted
        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_product_auto_sku_generation_manufacturer_and_initial_quantity(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/products', [
                'name' => 'MacBook Air M2 256Go',
                'manufacturer' => 'Apple',
                'category_name' => 'Ordinateurs Laptops',
                'base_unit_id' => $this->unit->id,
                'state' => 'used',
                'quantity' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'MacBook Air M2 256Go')
            ->assertJsonPath('data.manufacturer', 'Apple')
            ->assertJsonPath('data.category.name', 'Ordinateurs Laptops')
            ->assertJsonPath('data.total_on_hand', '5.0000');

        $sku = $response->json('data.sku');
        $this->assertNotEmpty($sku);
        $this->assertStringStartsWith('SKU-APP-', $sku);

        // Verify stock movement and balance were created
        $productId = $response->json('data.id');
        $this->assertDatabaseHas('stock_balances', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $productId,
            'on_hand' => 5,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenant->id,
            'product_id' => $productId,
            'type' => 'receipt',
            'quantity' => 5,
        ]);
    }
}
