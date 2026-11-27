<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promoción de apertura: cada tienda puede usar un plan gratis por unos días, una sola vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('prueba_plan', 30)->nullable()->after('limite_productos')->comment('Plan que usó en su prueba gratis.');
            $table->timestamp('prueba_hasta')->nullable()->after('prueba_plan')->index()->comment('Fin de la prueba gratis.');
            $table->timestamp('prueba_usada_at')->nullable()->after('prueba_hasta')->comment('Cuándo empezó su prueba (solo una vez por tienda).');
            $table->timestamp('prueba_aviso_previo_at')->nullable()->after('prueba_usada_at');
            $table->timestamp('prueba_aviso_fin_at')->nullable()->after('prueba_aviso_previo_at');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropIndex(['prueba_hasta']);
            $table->dropColumn(['prueba_plan', 'prueba_hasta', 'prueba_usada_at', 'prueba_aviso_previo_at', 'prueba_aviso_fin_at']);
        });
    }
};
