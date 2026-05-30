<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Cliente\CatalogoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\Vendedor\SolicitudVendedorController;
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
    Route::view('/contacto', 'cliente.contacto')->name('contacto');
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/vendedor/solicitar', [SolicitudVendedorController::class, 'create'])
        ->name('vendedor.solicitar.create');
    Route::post('/vendedor/solicitar', [SolicitudVendedorController::class, 'store'])
        ->middleware('throttle:3,1440')
        ->name('vendedor.solicitar.store');
    Route::get('/vendedor/solicitar/validar-email', [SolicitudVendedorController::class, 'checkEmail'])
        ->name('vendedor.solicitar.check-email');
    Route::get('/vendedor/solicitar/validar-documento', [SolicitudVendedorController::class, 'checkDocument'])
        ->name('vendedor.solicitar.check-document');
    Route::get('/vendedor/solicitud/{codigo}', [SolicitudVendedorController::class, 'show'])
        ->name('vendedor.solicitud.show');
    Route::get('/admin/salir-impersonacion', [ImpersonationController::class, 'stop'])
        ->middleware('auth')
        ->name('admin.impersonation.stop');
    Route::middleware('auth')->group(function (): void {
        Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
        Route::get('/notificaciones/{notification}/abrir', [NotificacionController::class, 'open'])
            ->name('notificaciones.open');
        Route::post('/notificaciones/{notification}/leer', [NotificacionController::class, 'markAsRead'])
            ->name('notificaciones.read');
        Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'markAllAsRead'])
            ->name('notificaciones.read-all');
    });

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
