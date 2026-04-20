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
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del pago.');
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('metodo', ['efectivo', 'transferencia', 'tarjeta'])->index();
            $table->decimal('monto', 12, 2);
            $table->enum('estado', ['pendiente', 'validando', 'aprobado', 'rechazado', 'anulado', 'reembolsado'])
                ->default('pendiente')
                ->index();
            $table->string('transaccion_id_pasarela', 120)->nullable()->index();
            $table->boolean('hmac_validado')->default(false)->index();
            $table->string('referencia_bancaria', 120)->nullable()->index();
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('validado_at')->nullable();
            $table->json('pasarela_payload')->nullable()->comment('Respuesta normalizada de la pasarela mock o real.');
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
            $table->index(['metodo', 'estado']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
