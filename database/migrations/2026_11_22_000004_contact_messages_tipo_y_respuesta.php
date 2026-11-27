<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bandeja de mensajes: distingue contacto, cotizaciones y soporte a tiendas, y guarda la respuesta de Atlantia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->string('tipo', 20)->default('contacto')->after('user_id')->index()->comment('contacto, cotizacion o soporte.');
            $table->string('categoria', 30)->nullable()->after('tipo')->comment('Tema del soporte: pagos, pedidos, repartidores, cuenta, productos u otro.');
            $table->text('respuesta')->nullable()->after('mensaje')->comment('Respuesta que dio Atlantia al cerrar el mensaje.');
        });

        DB::table('contact_messages')->where('asunto', 'like', 'Cotización plan%')->update(['tipo' => 'cotizacion']);
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropIndex(['tipo']);
            $table->dropColumn(['tipo', 'categoria', 'respuesta']);
        });
    }
};
