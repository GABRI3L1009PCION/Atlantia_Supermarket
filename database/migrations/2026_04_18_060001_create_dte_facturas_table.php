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
        Schema::create('dte_facturas', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique()->comment('Identificador público seguro del DTE dentro de Atlantia.');
            $table->unsignedBigInteger('pedido_id')->nullable()->index()->comment('FK diferida hacia pedidos por dependencia circular.');
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->string('numero_dte', 80)->unique()->comment('Número interno correlativo del DTE.');
            $table->string('uuid_sat', 120)->nullable()->unique()->comment('UUID emitido por SAT Guatemala.');
            $table->string('serie', 40)->nullable()->index();
            $table->unsignedBigInteger('numero')->nullable()->index();
            $table->enum('tipo_dte', ['FACT', 'FCAM', 'FPEQ', 'NCRE', 'NDEB'])->default('FACT')->index();
            $table->decimal('monto_neto', 12, 2);
            $table->decimal('monto_iva', 12, 2)->default(0.00);
            $table->decimal('monto_total', 12, 2);
            $table->string('moneda', 3)->default('GTQ');
            $table->longText('xml_dte')->comment('XML certificado o pendiente de certificación.');
            $table->string('pdf_path', 500)->nullable()->comment('Ruta del PDF fiscal almacenado en S3-compatible.');
            $table->enum('estado', [
                'borrador',
                'certificado',
                'rechazado',
                'anulado',
            ])->default('borrador')->index();
            $table->timestamp('fecha_certificacion')->nullable()->index();
            $table->json('certificador_respuesta')->nullable()->comment('Respuesta completa normalizada de INFILE.');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'estado']);
            $table->index(['pedido_id', 'estado']);
            $table->index(['tipo_dte', 'estado']);
            $table->index(['estado', 'created_at']);
            $table->unique(['vendor_id', 'serie', 'numero']);
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_facturas');
    }
};
