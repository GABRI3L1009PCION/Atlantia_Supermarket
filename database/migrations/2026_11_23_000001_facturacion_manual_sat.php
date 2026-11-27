<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturación manual: la tienda puede facturar ella misma en la SAT (Agencia Virtual / FEL gratuito)
 * y registrar en Atlantia el número de autorización y el PDF de cada factura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_fiscal_profiles', function (Blueprint $table): void {
            $table->string('modo_facturacion', 20)->nullable()->after('certificador_fel')
                ->comment('manual_sat: factura en la SAT y la registra; certificador: automática con su certificador.');
        });

        Schema::table('dte_facturas', function (Blueprint $table): void {
            $table->string('origen', 20)->default('certificador')->after('tipo_dte')->index()
                ->comment('certificador (emitida por Atlantia) o manual (registrada por la tienda).');
        });
    }

    public function down(): void
    {
        Schema::table('dte_facturas', function (Blueprint $table): void {
            $table->dropIndex(['origen']);
            $table->dropColumn('origen');
        });
        Schema::table('vendor_fiscal_profiles', function (Blueprint $table): void {
            $table->dropColumn('modo_facturacion');
        });
    }
};
