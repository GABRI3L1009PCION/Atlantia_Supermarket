<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        if (Schema::hasTable('delivery_offers')) {
            return;
        }

        Schema::create('delivery_offers', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('external_delivery_order_id')->nullable()->constrained('external_delivery_orders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('delivery_route_id')->nullable()->constrained('delivery_routes')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('repartidor_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('source_type', 30)->default('internal')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->decimal('estimated_gain', 12, 2)->default(0);
            $table->decimal('pickup_distance_km', 8, 2)->nullable();
            $table->decimal('delivery_distance_km', 8, 2)->nullable();
            $table->decimal('total_distance_km', 8, 2)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['repartidor_id', 'status']);
            $table->index(['pedido_id', 'status']);
            $table->index(['external_delivery_order_id', 'status']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_offers');
    }
};
