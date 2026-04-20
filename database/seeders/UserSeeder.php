<?php

namespace Database\Seeders;

use App\Models\Empleado;
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
     * Password conocido para desarrollo local.
     */
    private const DEV_PASSWORD = 'Atlantia2026!';

    /**
     * Ejecuta el seeder de usuarios.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Gabriel Picon',
                'email' => 'admin@atlantia.test',
                'role' => 'admin',
                'phone' => '+502 7948-1001',
                'two_factor_enabled' => true,
            ],
            [
                'name' => 'Cuenta Maestra Atlantia',
                'email' => 'respaldo@atlantia.test',
                'role' => 'admin',
                'phone' => '+502 7948-1002',
                'two_factor_enabled' => true,
                'is_system_user' => true,
            ],
            [
                'name' => 'Ioni Rodas',
                'email' => 'empleado@atlantia.test',
                'role' => 'empleado',
                'phone' => '+502 7948-1003',
            ],
            [
                'name' => 'Henry Diaz',
                'email' => 'repartidor@atlantia.test',
                'role' => 'repartidor',
                'phone' => '+502 7948-1004',
            ],
            [
                'name' => 'Mariela Castillo',
                'email' => 'cliente@atlantia.test',
                'role' => 'cliente',
                'phone' => '+502 7948-1005',
            ],
            [
                'name' => 'Carlos Mendez',
                'email' => 'vendedor@atlantia.test',
                'role' => 'vendedor',
                'phone' => '+502 7948-1006',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $userData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::DEV_PASSWORD),
                    'phone' => $userData['phone'],
                    'status' => 'active',
                    'is_system_user' => $userData['is_system_user'] ?? false,
                    'two_factor_enabled' => $userData['two_factor_enabled'] ?? false,
                    'two_factor_confirmed_at' => ($userData['two_factor_enabled'] ?? false) ? now() : null,
                ]
            );

            $user->update([
                'name' => $userData['name'],
                'phone' => $userData['phone'],
                'status' => 'active',
                'is_system_user' => $userData['is_system_user'] ?? false,
                'two_factor_enabled' => $userData['two_factor_enabled'] ?? false,
                'two_factor_confirmed_at' => ($userData['two_factor_enabled'] ?? false)
                    ? ($user->two_factor_confirmed_at ?? now())
                    : null,
            ]);

            $user->assignRole($userData['role']);
        }

        if (config('atlantia.super_admin.enabled')) {
            $superAdmin = User::query()->firstOrCreate(
                ['email' => (string) config('atlantia.super_admin.email')],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Super Administrador Atlantia',
                    'email_verified_at' => now(),
                    'password' => Hash::make((string) env('ATLANTIA_SUPER_ADMIN_PASSWORD', self::DEV_PASSWORD)),
                    'phone' => '+502 7948-1999',
                    'status' => 'active',
                    'is_system_user' => true,
                    'two_factor_enabled' => true,
                    'two_factor_confirmed_at' => now(),
                ]
            );

            $superAdmin->update([
                'status' => 'active',
                'is_system_user' => true,
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => $superAdmin->two_factor_confirmed_at ?? now(),
            ]);
            $superAdmin->assignRole('super_admin');
        }

        $empleadoUser = User::query()->where('email', 'empleado@atlantia.test')->first();
        $supervisorUser = User::query()->where('email', 'admin@atlantia.test')->first();

        if ($empleadoUser !== null) {
            $empleado = Empleado::query()->firstOrNew(['user_id' => $empleadoUser->id]);
            $empleado->fill([
                'uuid' => $empleado->uuid ?? (string) Str::uuid(),
                'codigo_empleado' => 'ATL-EMP-001',
                'departamento' => 'soporte_cliente',
                'puesto' => 'Analista de soporte y moderacion',
                'telefono_interno' => '+502 7948-1101',
                'fecha_contratacion' => now()->subMonths(3)->toDateString(),
                'status' => 'active',
                'supervisor_id' => $supervisorUser?->empleado?->id,
                'permisos_operativos' => [
                    'validar_transferencias',
                    'atender_contacto',
                    'moderar_resenas',
                ],
            ]);
            $empleado->save();
        }
    }
}
