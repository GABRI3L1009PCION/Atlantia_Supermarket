<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarjeta guardada en Recurrente (solo el token, nunca los datos de la tarjeta)
     * y membresia de envio gratis.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('recurrente_customer_id', 64)->nullable()->after('referido_recompensado_at');
            $table->string('recurrente_metodo_pago_id', 64)->nullable()->after('recurrente_customer_id');
            $table->string('tarjeta_descripcion', 60)->nullable()->after('recurrente_metodo_pago_id');
            $table->timestamp('membresia_hasta')->nullable()->after('tarjeta_descripcion');
            $table->string('membresia_suscripcion_id', 64)->nullable()->after('membresia_hasta');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'recurrente_customer_id',
                'recurrente_metodo_pago_id',
                'tarjeta_descripcion',
                'membresia_hasta',
                'membresia_suscripcion_id',
            ]);
        });
    }
};
