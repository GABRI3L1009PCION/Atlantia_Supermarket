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
        Schema::create('carrito_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario_snapshot', 12, 2)->comment('Precio visible al cliente al agregar al carrito.');
            $table->timestamps();

            $table->unique(['carrito_id', 'producto_id']);
            $table->index(['producto_id', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrito_items');
    }
};
