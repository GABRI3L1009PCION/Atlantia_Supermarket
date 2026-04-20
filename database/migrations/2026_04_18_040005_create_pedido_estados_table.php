<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('pedido_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('estado', [
                'pendiente',
                'confirmado',
                'preparando',
                'listo_para_entrega',
                'en_ruta',
                'entregado',
                'cancelado',
                'rechazado',
            ])->index();
            $table->text('notas')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_estados');
    }
};
