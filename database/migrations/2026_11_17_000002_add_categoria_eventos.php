<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Categoria para los combos de Atlantia Eventos.
     */
    public function up(): void
    {
        if (DB::table('categorias')->where('slug', 'eventos-y-fiestas')->exists()) {
            return;
        }

        DB::table('categorias')->insert([
            'nombre' => 'Eventos y fiestas',
            'slug' => 'eventos-y-fiestas',
            'descripcion' => 'Combos de bebidas, desechables, hielo y botanas para celebraciones.',
            'icon' => 'party',
            'orden' => 90,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('categorias')->where('slug', 'eventos-y-fiestas')->delete();
    }
};
