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
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->enum('facturacion_tipo', ['datos', 'cf'])->default('cf')->after('estado_pago');
            $table->string('facturacion_nombre', 180)->nullable()->after('facturacion_tipo');
            $table->string('facturacion_nit', 40)->nullable()->after('facturacion_nombre');
            $table->string('facturacion_email', 190)->nullable()->after('facturacion_nit')->index();
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropIndex(['facturacion_email']);
            $table->dropColumn([
                'facturacion_tipo',
                'facturacion_nombre',
                'facturacion_nit',
                'facturacion_email',
            ]);
        });
    }
};
