<?php

use App\Jobs\LimpiarCarritosAbandonados;
use App\Jobs\LimpiarTokensExpirados;
use App\Jobs\ProcesarDespachoAutomatico;
use App\Models\User;
use App\Services\Auditoria\ProductionReadinessAuditService;
use App\Services\Observability\PlatformHealthService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'two_factor_confirmed_at' => null,
        ]
    );

    $user->syncRoles(['super_admin']);

    $this->info('Super admin real creado o actualizado correctamente.');

    return 0;
})->purpose('Crear el primer super administrador real de Atlantia');

Artisan::command('atlantia:audit-rbac {--json}', function (ProductionReadinessAuditService $auditService): int {
    $report = $auditService->rbacAudit();

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $report['status'] === 'error' ? 1 : 0;
    }

    $this->info('RBAC audit status: '.$report['status']);

    if ($report['missing_roles'] !== []) {
        $this->warn('Roles faltantes: '.implode(', ', $report['missing_roles']));
    }

    if ($report['missing_permissions'] !== []) {
        $this->warn('Permisos faltantes: '.implode(', ', $report['missing_permissions']));
    }

    if ($report['unexpected_roles'] !== []) {
        $this->warn('Roles no esperados: '.implode(', ', $report['unexpected_roles']));
    }

    if ($report['unexpected_permissions'] !== []) {
        $this->warn('Permisos no catalogados: '.implode(', ', $report['unexpected_permissions']));
    }

    foreach ($report['role_mismatches'] as $role => $diff) {
        $this->line(sprintf(
            '%s => faltan: [%s] extras: [%s]',
            $role,
            implode(', ', $diff['missing']),
            implode(', ', $diff['unexpected'])
        ));
    }

    foreach ($report['restricted_permission_violations'] as $violation) {
        $this->error(sprintf(
            'Permiso restringido %s asignado indebidamente a: %s',
            $violation['permission'],
            implode(', ', $violation['violating_roles'])
        ));
    }

    $this->table(
        ['Modulo', 'Permisos'],
        collect($report['module_summary'])->map(fn (array $module): array => [
            $module['module'],
            $module['permission_count'],
        ])->all()
    );

    return $report['status'] === 'error' ? 1 : 0;
})->purpose('Auditar roles y permisos finales por modulo');

Artisan::command('atlantia:audit-demo-data {--json}', function (ProductionReadinessAuditService $auditService): int {
    $report = $auditService->demoDataAudit();

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $report['status'] === 'error' ? 1 : 0;
    }

    $this->info('Demo data audit status: '.$report['status']);
    $this->line('Seeders de desarrollo habilitados en este entorno: '.($report['development_seeders_enabled'] ? 'si' : 'no'));

    $this->table(
        ['Coleccion', 'Coincidencias sospechosas'],
        collect($report['suspicious_totals'])->map(fn (int $total, string $key): array => [$key, $total])->all()
    );

    foreach ($report['samples'] as $collection => $rows) {
        if ($rows === []) {
            continue;
        }

        $this->warn('Muestras sospechosas en '.$collection.':');
        $this->line(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $report['status'] === 'error' ? 1 : 0;
})->purpose('Auditar datos demo, sandbox y semillas de desarrollo');

Artisan::command('atlantia:operational-readiness {--json}', function (ProductionReadinessAuditService $auditService): int {
    $report = $auditService->operationalReadiness();

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $report['status'] === 'error' ? 1 : 0;
    }

    $this->info('Operational readiness status: '.$report['status']);

    foreach ($report['actors'] as $actor => $data) {
        $this->line('');
        $this->line(strtoupper($actor).' => '.$data['status']);

        foreach ($data['checks'] as $label => $value) {
            $this->line(' - '.$label.': '.(is_bool($value) ? ($value ? 'si' : 'no') : $value));
        }

        $this->line(' Escenarios manuales obligatorios:');
        foreach ($data['manual_required'] as $scenario) {
            $this->line('   * '.$scenario);
        }
    }

    foreach ($report['notes'] as $note) {
        $this->warn($note);
    }

    return $report['status'] === 'error' ? 1 : 0;
})->purpose('Revisar alistamiento operativo de vendedor, cliente y administrador');

Artisan::command('atlantia:ops-snapshot {--json}', function (PlatformHealthService $platformHealthService): int {
    $snapshot = $platformHealthService->operationsSnapshot();

    if ($this->option('json')) {
        $this->line(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $snapshot['status'] === 'error' ? 1 : 0;
    }

    $this->info('Operations snapshot status: '.$snapshot['status']);

    $this->table(
        ['Check', 'Estado', 'Detalle'],
        collect($snapshot['checks'])->map(fn (array $check): array => [
            $check['label'],
            $check['status'],
            $check['detail'],
        ])->all()
    );

    if ($snapshot['status'] !== 'ok') {
        $fingerprint = md5(json_encode([
            'status' => $snapshot['status'],
            'checks' => collect($snapshot['checks'])->map(fn (array $check): array => [
                'key' => $check['key'],
                'status' => $check['status'],
                'detail' => $check['detail'],
            ])->all(),
        ], JSON_UNESCAPED_UNICODE));
        $cacheKey = 'atlantia:ops:last-alert-fingerprint';

        if (Cache::get($cacheKey) !== $fingerprint) {
            Log::channel((string) config('observability.alerts.channel', 'incidents'))
                ->critical('Atlantia operations snapshot requires attention', $snapshot);

            Cache::put(
                $cacheKey,
                $fingerprint,
                now()->addMinutes((int) config('observability.alerts.cooldown_minutes', 15))
            );
        }
    } else {
        Cache::forget('atlantia:ops:last-alert-fingerprint');
    }

    return $snapshot['status'] === 'error' ? 1 : 0;
})->purpose('Tomar una fotografia de salud operativa y disparar alertas controladas');

Schedule::job(new LimpiarCarritosAbandonados)->daily();
Schedule::job(new LimpiarTokensExpirados)->daily();
Schedule::command('passport:purge --expired --revoked --hours=24')->daily();
Schedule::command('auth:clear-resets')->daily();
Schedule::command('queue:prune-failed --hours=720')->weekly();

if (config('session.driver') === 'database') {
    Schedule::command('session:gc')->hourly();
}

Schedule::command('scout:sync-index-settings')->weekly();
Schedule::job(new ProcesarDespachoAutomatico)
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping();
Schedule::call(function (): void {
    $key = (string) config('observability.scheduler.heartbeat_key', 'atlantia:ops:scheduler-heartbeat');
    $ttlMinutes = max(10, ((int) config('observability.scheduler.stale_after_minutes', 3)) * 4);

    Cache::put($key, now()->toIso8601String(), now()->addMinutes($ttlMinutes));
})->name('atlantia:scheduler-heartbeat')->everyMinute()->onOneServer();
Schedule::command('atlantia:ops-snapshot')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping();
