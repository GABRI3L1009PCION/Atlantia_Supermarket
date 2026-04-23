<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('pedido_historial_estados', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('estado_anterior', 50)->nullable()->index();
            $table->string('estado_nuevo', 50)->index();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
            $table->index(['estado_nuevo', 'created_at']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_historial_estados');
    }
};
