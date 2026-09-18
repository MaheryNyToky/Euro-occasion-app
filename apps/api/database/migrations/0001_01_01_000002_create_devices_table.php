<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_identifier');
            $table->string('name');
            $table->string('platform', 30); // windows, android, web
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->boolean('is_revoked')->default(false)->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'device_identifier']);
            $table->index(['tenant_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
