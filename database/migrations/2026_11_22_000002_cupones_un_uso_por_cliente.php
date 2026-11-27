<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite limitar un cupón a un solo uso por cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cupones', function (Blueprint $table): void {
            $table->boolean('un_uso_por_cliente')->default(false)->after('solo_primera_compra');
        });
    }

    public function down(): void
    {
        Schema::table('cupones', function (Blueprint $table): void {
            $table->dropColumn('un_uso_por_cliente');
        });
    }
};
