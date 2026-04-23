<?php

use App\Http\Controllers\Empleado\DashboardController;
use App\Http\Controllers\Empleado\MensajeContactoController;
use App\Http\Controllers\Empleado\ModeracionResenaController;
use App\Http\Controllers\Empleado\ValidacionTransferenciaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Empleado
|--------------------------------------------------------------------------
|
| Operacion interna: transferencias, mensajes de contacto y moderacion.
|
*/

Route::prefix('empleado')
    ->as('empleado.')
    ->middleware(['auth', 'verified', 'role:empleado', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/transferencias', [ValidacionTransferenciaController::class, 'index'])
            ->name('transferencias.index');
        Route::patch('/transferencias/{payment}', [ValidacionTransferenciaController::class, 'update'])
            ->name('transferencias.update');

        Route::get('/mensajes-contacto', [MensajeContactoController::class, 'index'])->name('mensajes.index');
        Route::post('/mensajes-contacto/{message}/responder', [MensajeContactoController::class, 'respond'])
            ->name('mensajes.respond');

        Route::get('/resenas', [ModeracionResenaController::class, 'index'])->name('resenas.index');
        Route::patch('/resenas/{resena:uuid}', [ModeracionResenaController::class, 'update'])
            ->name('resenas.update');
    });
