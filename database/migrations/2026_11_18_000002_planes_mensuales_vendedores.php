<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Planes mensuales: inicial, esencial, profesional, avanzado, corporativo y personalizado.
     */
    public function up(): void
    {
        foreach (['socio' => 'inicial', 'puesto' => 'esencial', 'local' => 'profesional', 'ancla' => 'corporativo'] as $antes => $ahora) {
            DB::table('vendors')->where('plan', $antes)->update(['plan' => $ahora]);
        }

        Schema::table('vendors', function (Blueprint $table): void {
            $table->string('plan', 20)->default('inicial')->change();
            $table->unsignedInteger('limite_productos')->nullable()->after('plan_suscripcion_id')
                ->comment('Solo para el plan Personalizado: cantidad de productos acordada.');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropColumn('limite_productos');
            $table->string('plan', 20)->default('socio')->change();
        });
    }
};
