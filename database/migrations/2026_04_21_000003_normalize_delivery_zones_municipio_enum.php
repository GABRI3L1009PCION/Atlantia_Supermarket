<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Normaliza municipios de zonas de entrega a valores ASCII.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE delivery_zones
            SET municipio = 'Santo Tomas'
            WHERE municipio LIKE 'Santo Tom%'
        ");

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE delivery_zones
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
     * Mantiene rollback compatible con datos ASCII.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE delivery_zones
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
