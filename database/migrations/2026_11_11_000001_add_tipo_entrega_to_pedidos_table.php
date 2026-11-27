<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda como recibe el cliente su pedido: domicilio, programado o recoger en tienda.
 * Los pedidos para recoger no pagan envio ni se asignan a repartidores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->string('tipo_entrega', 20)->default('domicilio')->after('direccion_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropIndex(['tipo_entrega']);
            $table->dropColumn('tipo_entrega');
        });
    }
};
