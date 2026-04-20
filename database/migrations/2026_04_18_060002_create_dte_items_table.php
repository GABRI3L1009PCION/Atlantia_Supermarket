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
        Schema::create('dte_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dte_id')->constrained('dte_facturas')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete()->cascadeOnUpdate();
            $table->string('descripcion', 220)->comment('Descripción fiscal del bien o servicio.');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0.00);
            $table->decimal('monto_iva', 12, 2)->default(0.00);
            $table->decimal('monto_total', 12, 2);
            $table->timestamps();

            $table->index(['dte_id', 'producto_id']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_items');
    }
};
