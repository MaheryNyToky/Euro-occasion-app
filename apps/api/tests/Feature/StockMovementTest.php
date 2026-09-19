<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
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
            'code' => 'stock-co',
            'name' => 'Stock Entreprise',
            'accounting_currency' => 'MGA',
            'status' => 'active',
        ]);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Responsable Stock',
            'email' => 'stock@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $this->token = $this->user->createToken('stock-test')->plainTextToken;
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
            'sku' => 'STOCK-001',
            'name' => 'Produit de test',
            'state' => 'new',
            'is_active' => true,
        ]);
        $site = Site::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'SITE-TEST',
            'name' => 'Site test',
            'is_active' => true,
        ]);
        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $site->id,
            'code' => 'WH-TEST',
            'name' => 'Entrepôt test',
            'allow_negative_stock' => false,
            'is_active' => true,
        ]);
    }

    public function test_receipt_issue_and_transfer_update_balances_atomically(): void
    {
        $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ])->assertCreated();

        $this->postMovement([
            'type' => 'issue',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'quantity' => 3,
        ])->assertCreated();

        $destination = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->warehouse->site_id,
            'code' => 'WH-DEST',
            'name' => 'Entrepôt destination',
            'is_active' => true,
        ]);
        $this->postMovement([
            'type' => 'transfer',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $destination->id,
            'quantity' => 2,
        ])->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 5,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $destination->id,
            'on_hand' => 2,
        ]);
        $this->assertDatabaseCount('stock_movements', 3);
    }

    public function test_issue_rejects_insufficient_available_stock(): void
    {
        $this->postMovement([
            'type' => 'issue',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'quantity' => 1,
        ])->assertStatus(422);

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_idempotency_key_replays_without_double_counting(): void
    {
        $payload = [
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 4,
            'idempotency_key' => 'receipt-stock-001',
        ];

        $this->postMovement($payload)->assertCreated()->assertJsonPath('meta.replayed', false);
        $this->postMovement($payload)->assertOk()->assertJsonPath('meta.replayed', true);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 4,
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_reverse_receipt_restores_balance_and_creates_audit(): void
    {
        $response = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ])->assertCreated();

        $originalId = $response->json('data.id');

        $revResponse = $this->reverseMovement($originalId, [
            'reason' => 'Erreur de réception',
        ])->assertCreated();

        $revId = $revResponse->json('data.id');
        $this->assertEquals('reverse', $revResponse->json('data.type'));
        $this->assertEquals($originalId, $revResponse->json('data.reversed_movement_id'));
        $this->assertEquals(10, (float) $revResponse->json('data.quantity'));
        $this->assertEquals($this->warehouse->id, $revResponse->json('data.source_warehouse_id'));
        $this->assertNull($revResponse->json('data.destination_warehouse_id'));

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 0,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'action' => 'stock.movement_reversed',
            'auditable_id' => $revId,
            'reason' => 'Erreur de réception',
        ]);
    }

    public function test_reverse_issue_restores_stock_to_source_warehouse(): void
    {
        $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ])->assertCreated();

        $issueResp = $this->postMovement([
            'type' => 'issue',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'quantity' => 4,
        ])->assertCreated();

        $issueId = $issueResp->json('data.id');

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 6,
        ]);

        $this->reverseMovement($issueId)->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 10,
        ]);
    }

    public function test_reverse_transfer_restores_both_source_and_destination_balances(): void
    {
        $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ])->assertCreated();

        $destination = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->warehouse->site_id,
            'code' => 'WH-DEST-2',
            'name' => 'Entrepôt destination 2',
            'is_active' => true,
        ]);

        $transferResp = $this->postMovement([
            'type' => 'transfer',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $destination->id,
            'quantity' => 3,
        ])->assertCreated();

        $transferId = $transferResp->json('data.id');

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 7,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $destination->id,
            'on_hand' => 3,
        ]);

        $this->reverseMovement($transferId)->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 10,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $destination->id,
            'on_hand' => 0,
        ]);
    }

    public function test_reverse_rejects_insufficient_stock_when_negative_not_allowed(): void
    {
        $receiptResp = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
        ])->assertCreated();

        $receiptId = $receiptResp->json('data.id');

        // Issue 4 out of 5
        $this->postMovement([
            'type' => 'issue',
            'product_id' => $this->product->id,
            'source_warehouse_id' => $this->warehouse->id,
            'quantity' => 4,
        ])->assertCreated();

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 1,
        ]);

        // Attempting to reverse original receipt of 5 needs to deduct 5, but only 1 is available
        $this->reverseMovement($receiptId)->assertStatus(422);

        // State remains untouched
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 1,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_reverse_idempotency_key_replays_safely(): void
    {
        $receiptResp = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 6,
        ])->assertCreated();

        $receiptId = $receiptResp->json('data.id');

        $payload = ['idempotency_key' => 'rev-key-001'];

        $this->reverseMovement($receiptId, $payload)
            ->assertCreated()
            ->assertJsonPath('meta.replayed', false);

        $this->reverseMovement($receiptId, $payload)
            ->assertOk()
            ->assertJsonPath('meta.replayed', true);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'on_hand' => 0,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_cannot_reverse_same_movement_twice_with_different_keys(): void
    {
        $receiptResp = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 6,
        ])->assertCreated();

        $receiptId = $receiptResp->json('data.id');

        $this->reverseMovement($receiptId)->assertCreated();
        $this->reverseMovement($receiptId)->assertStatus(422);
    }

    public function test_cannot_reverse_a_reverse_movement(): void
    {
        $receiptResp = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 6,
        ])->assertCreated();

        $receiptId = $receiptResp->json('data.id');
        $revResp = $this->reverseMovement($receiptId)->assertCreated();
        $revId = $revResp->json('data.id');

        $this->reverseMovement($revId)->assertStatus(422);
    }

    public function test_tenant_isolation_on_reverse(): void
    {
        $receiptResp = $this->postMovement([
            'type' => 'receipt',
            'product_id' => $this->product->id,
            'destination_warehouse_id' => $this->warehouse->id,
            'quantity' => 6,
        ])->assertCreated();

        $receiptId = $receiptResp->json('data.id');

        $otherTenant = Tenant::create([
            'code' => 'tenant-other',
            'name' => 'Autre Entreprise',
            'accounting_currency' => 'MGA',
            'status' => 'active',
        ]);
        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Autre Utilisateur',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $otherToken = $otherUser->createToken('other-test')->plainTextToken;

        app('auth')->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/v1/stock-movements/{$receiptId}/reverse")
            ->assertStatus(404);
    }

    private function postMovement(array $payload)
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/stock-movements', $payload);
    }

    private function reverseMovement(string $id, array $payload = [])
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/stock-movements/{$id}/reverse", $payload);
    }
}
