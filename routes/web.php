<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Cliente\CatalogoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
|
| Punto de entrada web de Atlantia Supermarket. Este archivo mantiene la
| composicion general y delega las rutas por actor a archivos dedicados.
|
*/

Route::middleware(['web', 'security.headers'])->group(function (): void {
    Route::get('/', [CatalogoController::class, 'index'])->name('home');
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/admin/salir-impersonacion', [ImpersonationController::class, 'stop'])
        ->middleware('auth')
        ->name('admin.impersonation.stop');

    $routeFiles = [
        __DIR__ . '/auth.php',
        __DIR__ . '/cliente.php',
        __DIR__ . '/admin.php',
        __DIR__ . '/vendedor.php',
        __DIR__ . '/repartidor.php',
        __DIR__ . '/empleado.php',
    ];

    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }
});
