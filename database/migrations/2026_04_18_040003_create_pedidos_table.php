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
        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del pedido.');
            $table->string('numero_pedido', 40)->unique()->comment('Código humano de seguimiento para cliente y soporte.');
            $table->foreignId('pedido_padre_id')->nullable()->constrained('pedidos')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('cliente_id')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->nullable()->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('direccion_id')->constrained('direcciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedBigInteger('dte_id')->nullable()->index()->comment('FK diferida hacia dte_facturas por dependencia circular.');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('envio', 12, 2)->default(0.00);
            $table->decimal('impuestos', 12, 2)->default(0.00);
            $table->decimal('descuento', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);
            $table->enum('estado', [
                'pendiente',
                'confirmado',
                'preparando',
                'listo_para_entrega',
                'en_ruta',
                'entregado',
                'cancelado',
                'rechazado',
            ])->default('pendiente')->index();
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta'])->index();
            $table->enum('estado_pago', ['pendiente', 'validando', 'pagado', 'rechazado', 'reembolsado'])->default('pendiente')->index();
            $table->text('notas')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamp('cancelado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'estado']);
            $table->index(['vendor_id', 'estado']);
            $table->index(['pedido_padre_id', 'estado']);
            $table->index(['metodo_pago', 'estado_pago']);
            $table->index(['estado', 'created_at']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
