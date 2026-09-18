<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('on_hand', 15, 4)->default(0);
            $table->decimal('reserved', 15, 4)->default(0);
            $table->decimal('damaged', 15, 4)->default(0);
            $table->decimal('in_transit', 15, 4)->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'warehouse_id']);
        });

        // Unique constraint with NULLS NOT DISTINCT (PostgreSQL 15+)
        DB::statement('CREATE UNIQUE INDEX stock_balances_unique_idx ON stock_balances (tenant_id, product_id, COALESCE(variant_id, \'00000000-0000-0000-0000-000000000000\'), warehouse_id, COALESCE(location_id, \'00000000-0000-0000-0000-000000000000\'))');

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type', 30)->index(); // receipt, issue, transfer_out, transfer_in, adjustment_increase, adjustment_decrease, reverse
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->foreignUuid('base_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignUuid('source_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignUuid('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignUuid('source_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->foreignUuid('destination_location_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 100)->nullable();
            $table->text('reason')->nullable();
            $table->uuid('reversed_movement_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['tenant_id', 'product_id', 'source_warehouse_id']);
            $table->index(['tenant_id', 'destination_warehouse_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Unique idempotency key per tenant
        DB::statement('CREATE UNIQUE INDEX stock_movements_idempotency_idx ON stock_movements (tenant_id, idempotency_key) WHERE idempotency_key IS NOT NULL');

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignUuid('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('serial_number', 100);
            $table->string('status', 30)->default('in_stock')->index(); // in_stock, reserved, sold, damaged, in_transit
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->unsignedSmallInteger('warranty_months')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'serial_number']);
            $table->index(['tenant_id', 'product_id', 'status']);
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('lot_number', 100);
            $table->decimal('initial_quantity', 15, 4);
            $table->decimal('remaining_quantity', 15, 4);
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'lot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lots');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};
