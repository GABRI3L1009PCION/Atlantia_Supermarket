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
        Schema::create('inventarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->unique()->constrained('productos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('stock_actual')->default(0);
            $table->unsignedInteger('stock_reservado')->default(0)->comment('Stock reservado por pedidos pendientes.');
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->unsignedInteger('stock_maximo')->nullable();
            $table->timestamp('ultima_actualizacion')->useCurrent()->index();
            $table->timestamps();

            $table->index(['stock_actual', 'stock_minimo']);
            $table->index(['ultima_actualizacion', 'stock_actual']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
