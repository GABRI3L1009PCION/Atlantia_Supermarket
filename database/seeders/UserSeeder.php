<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder de usuarios principales por actor.
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de usuarios.
     */
    public function run(): void
    {
        $email = env('ATLANTIA_SUPER_ADMIN_EMAIL');
        $password = env('ATLANTIA_SUPER_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn(
                'No se creo super admin. Define ATLANTIA_SUPER_ADMIN_EMAIL y ATLANTIA_SUPER_ADMIN_PASSWORD, '
                . 'o ejecuta php artisan atlantia:create-super-admin.'
            );

            return;
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => (string) $email],
            [
                'uuid' => User::query()->where('email', $email)->value('uuid') ?? (string) Str::uuid(),
                'name' => env('ATLANTIA_SUPER_ADMIN_NAME', 'Super Administrador Atlantia'),
                'email_verified_at' => now(),
                'password' => Hash::make((string) $password),
                'phone' => env('ATLANTIA_SUPER_ADMIN_PHONE'),
                'status' => 'active',
                'is_system_user' => true,
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super_admin']);
    }
}
