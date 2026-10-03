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
        if (! Schema::hasTable('courier_wallets')) {
            Schema::create('courier_wallets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->decimal('available_balance', 12, 2)->default(0);
                $table->decimal('pending_balance', 12, 2)->default(0);
                $table->decimal('cash_balance', 12, 2)->default(0);
                $table->decimal('negative_balance', 12, 2)->default(0);
                $table->timestamp('last_settlement_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('courier_wallet_movements')) {
            Schema::create('courier_wallet_movements', function (Blueprint $table): void {
                $table->id();
                $table->char('uuid', 36)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('delivery_route_id')->nullable()->constrained('delivery_routes')->nullOnDelete()->cascadeOnUpdate();
                $table->string('type', 40)->index();
                $table->decimal('amount', 12, 2);
                $table->string('status', 30)->default('available')->index();
                $table->string('description', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['pedido_id', 'type']);
                $table->index(['delivery_route_id', 'type']);
            });
        }
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_wallet_movements');
        Schema::dropIfExists('courier_wallets');
    }
};
