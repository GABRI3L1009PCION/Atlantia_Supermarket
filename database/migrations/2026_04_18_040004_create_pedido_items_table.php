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
        Schema::create('pedido_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('producto_nombre_snapshot', 180)->comment('Nombre del producto al momento de la compra.');
            $table->string('producto_sku_snapshot', 80)->nullable();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario_snapshot', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0.00);
            $table->decimal('impuestos', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index(['pedido_id', 'producto_id']);
            $table->index(['producto_id', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
