<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UnitConversionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'code' => 'units-co',
            'name' => 'Units Entreprise',
            'accounting_currency' => 'MGA',
            'timezone' => 'Indian/Antananarivo',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Agent Stock',
            'email' => 'stock@units.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
        TenantContext::setTenantId($this->tenant->id);
    }

    public function test_units_can_be_created_and_converted(): void
    {
        // 1. Create Base Unit 'U' (Unité)
        $responseU = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/units', [
                'code' => 'U',
                'name' => 'Pièce Unitaire',
                'precision' => 0,
                'is_base' => true,
            ]);
        $responseU->assertStatus(201);
        $unitUId = $responseU->json('data.id');

        // 2. Create Unit 'BOX' (Boîte de 12)
        $responseBox = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/units', [
                'code' => 'BOX',
                'name' => 'Boîte de 12',
                'precision' => 2,
                'is_base' => false,
            ]);
        $responseBox->assertStatus(201);
        $unitBoxId = $responseBox->json('data.id');

        // 3. Define conversion: 1 BOX = 12 U
        $convResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/units/conversions', [
                'from_unit_id' => $unitBoxId,
                'to_unit_id' => $unitUId,
                'factor' => 12,
            ]);
        $convResponse->assertStatus(201);

        // 4. Test conversion service
        $unitService = new UnitConversionService();

        // 5 BOX -> 60 U (Direct)
        $qtyInU = $unitService->convert(5, $unitBoxId, $unitUId);
        $this->assertEquals(60.0, $qtyInU);

        // 24 U -> 2 BOX (Inverse)
        $qtyInBox = $unitService->convert(24, $unitUId, $unitBoxId);
        $this->assertEquals(2.0, $qtyInBox);

        // Same unit -> same quantity
        $this->assertEquals(15.0, $unitService->convert(15, $unitUId, $unitUId));

        // Unknown conversion throws exception
        $kgUnit = Unit::create([
            'tenant_id' => $this->tenant->id,
            'code' => 'KG',
            'name' => 'Kilogramme',
            'precision' => 3,
            'is_base' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $unitService->convert(10, $unitUId, $kgUnit->id);
    }
}
