<?php

use App\Jobs\LimpiarCarritosAbandonados;
use App\Jobs\LimpiarTokensExpirados;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('atlantia:create-super-admin {--name=} {--email=} {--phone=}', function (): int {
    $name = (string) ($this->option('name') ?: $this->ask('Nombre completo del super admin'));
    $email = (string) ($this->option('email') ?: $this->ask('Correo del super admin'));
    $phone = $this->option('phone') ?: $this->ask('Telefono del super admin (opcional)', null);
    $password = (string) $this->secret('Contrasena segura');
    $passwordConfirmation = (string) $this->secret('Confirma la contrasena');

    if ($password === '' || $password !== $passwordConfirmation) {
        $this->error('La contrasena no coincide o esta vacia.');

        return 1;
    }

    if (strlen($password) < 12) {
        $this->error('La contrasena debe tener al menos 12 caracteres.');

        return 1;
    }

    $user = User::query()->updateOrCreate(
        ['email' => $email],
        [
            'uuid' => User::query()->where('email', $email)->value('uuid') ?? (string) Str::uuid(),
            'name' => $name,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'phone' => $phone,
            'status' => 'active',
            'is_system_user' => true,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]
    );

    $user->syncRoles(['super_admin']);

    $this->info('Super admin real creado o actualizado correctamente.');

    return 0;
})->purpose('Crear el primer super administrador real de Atlantia');

Schedule::job(new LimpiarCarritosAbandonados())->daily();
Schedule::job(new LimpiarTokensExpirados())->daily();
Schedule::command('queue:prune-failed --hours=720')->weekly();

if (config('session.driver') === 'database') {
    Schedule::command('session:gc')->hourly();
}

Schedule::command('scout:sync-index-settings')->weekly();
