<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una dirección escrita durante el pago queda como borrador:
 * solo se guarda en la cuenta del cliente cuando la compra se completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direcciones', function (Blueprint $table): void {
            $table->boolean('borrador')->default(false)->after('activa')->index();
        });
    }

    public function down(): void
    {
        Schema::table('direcciones', function (Blueprint $table): void {
            $table->dropIndex(['borrador']);
            $table->dropColumn('borrador');
        });
    }
};
