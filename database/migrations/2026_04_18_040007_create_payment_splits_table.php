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
        Schema::create('payment_splits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->decimal('monto_bruto', 12, 2);
            $table->decimal('comision_atlantia', 12, 2)->default(0.00);
            $table->decimal('monto_neto_vendedor', 12, 2);
            $table->enum('estado', ['pendiente', 'liquidado', 'retenido', 'reversado'])->default('pendiente')->index();
            $table->timestamp('liquidado_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'vendor_id']);
            $table->index(['vendor_id', 'estado']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
    }
};
