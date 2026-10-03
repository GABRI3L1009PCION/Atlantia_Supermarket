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
        if (Schema::hasTable('courier_support_tickets')) {
            return;
        }

        Schema::create('courier_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('external_delivery_order_id')->nullable()->constrained('external_delivery_orders')->nullOnDelete()->cascadeOnUpdate();
            $table->string('type', 50)->index();
            $table->string('priority', 30)->default('normal')->index();
            $table->string('status', 30)->default('open')->index();
            $table->text('message');
            $table->text('support_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['priority', 'status']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_support_tickets');
    }
};
