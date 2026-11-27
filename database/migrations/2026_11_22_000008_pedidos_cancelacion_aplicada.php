<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca que ya se aplicaron los efectos de una cancelación (inventario, pago a tienda, reembolso),
 * para no repetirlos si el pedido se toca de nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->timestamp('cancelacion_aplicada_at')->nullable()->after('cancelado_at');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropColumn('cancelacion_aplicada_at');
        });
    }
};
