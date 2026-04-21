<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orquestador principal de seeders de Atlantia.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta todos los seeders en orden seguro para foreign keys.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
