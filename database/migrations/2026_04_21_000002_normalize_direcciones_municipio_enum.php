<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Normaliza los municipios de entrega a valores ASCII consistentes.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE direcciones
            SET municipio = 'Santo Tomas'
            WHERE municipio LIKE 'Santo Tom%'
        ");

        DB::statement("
            ALTER TABLE direcciones
            MODIFY municipio ENUM(
                'Puerto Barrios',
                'Santo Tomas',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor'
            ) NOT NULL
        ");
    }

    /**
     * Restaura el enum anterior compatible con instalaciones previas.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE direcciones
            MODIFY municipio ENUM(
                'Puerto Barrios',
                'Santo Tomas',
                'Morales',
                'Los Amates',
                'Livingston',
                'El Estor'
            ) NOT NULL
        ");
    }
};
