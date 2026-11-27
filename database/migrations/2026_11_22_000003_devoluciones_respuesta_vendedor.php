<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El vendedor puede dar su versión de una devolución antes de que Atlantia decida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devoluciones', function (Blueprint $table): void {
            $table->boolean('vendedor_de_acuerdo')->nullable()->after('notas_admin');
            $table->text('respuesta_vendedor')->nullable()->after('vendedor_de_acuerdo');
            $table->timestamp('vendedor_respondio_at')->nullable()->after('respuesta_vendedor');
        });
    }

    public function down(): void
    {
        Schema::table('devoluciones', function (Blueprint $table): void {
            $table->dropColumn(['vendedor_de_acuerdo', 'respuesta_vendedor', 'vendedor_respondio_at']);
        });
    }
};
