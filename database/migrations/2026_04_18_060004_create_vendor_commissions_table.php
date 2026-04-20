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
        Schema::create('vendor_commissions', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro de la comisión mensual.');
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->unsignedSmallInteger('anio')->index();
            $table->unsignedTinyInteger('mes')->index();
            $table->decimal('total_ventas', 12, 2)->default(0.00);
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->decimal('monto_comision', 12, 2)->default(0.00);
            $table->decimal('renta_fija', 12, 2)->default(0.00);
            $table->decimal('monto_total', 12, 2)->default(0.00);
            $table->enum('estado', ['pendiente', 'facturada', 'pagada', 'vencida', 'anulada'])
                ->default('pendiente')
                ->index();
            $table->unsignedBigInteger('dte_comision_id')->nullable()->index()->comment('FK diferida hacia dte_facturas.');
            $table->date('fecha_emision')->nullable()->index();
            $table->date('fecha_vencimiento')->nullable()->index();
            $table->timestamp('pagada_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'anio', 'mes']);
            $table->index(['vendor_id', 'estado']);
            $table->index(['anio', 'mes', 'estado']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_commissions');
    }
};
