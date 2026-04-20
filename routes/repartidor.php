<?php

use App\Http\Controllers\Repartidor\DashboardController;
use App\Http\Controllers\Repartidor\GeolocalizacionController;
use App\Http\Controllers\Repartidor\IncidenciaController;
use App\Http\Controllers\Repartidor\PedidoController;
use App\Http\Controllers\Repartidor\RutaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Repartidor
|--------------------------------------------------------------------------
|
| Entregas asignadas, rutas, actualizacion GPS e incidencias operativas.
|
*/

Route::prefix('repartidor')
    ->as('repartidor.')
    ->middleware(['auth', 'verified', 'role:repartidor', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::patch('/pedidos/{pedido:uuid}/estado', [PedidoController::class, 'updateEstado'])
            ->name('pedidos.estado');

        Route::get('/rutas', [RutaController::class, 'index'])->name('rutas.index');
        Route::get('/rutas/{route}', [RutaController::class, 'show'])->name('rutas.show');
        Route::patch('/rutas/{route}/completar', [RutaController::class, 'complete'])->name('rutas.complete');

        Route::post('/gps', [GeolocalizacionController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('gps.store');

        Route::post('/pedidos/{pedido:uuid}/incidencias', [IncidenciaController::class, 'store'])
            ->name('incidencias.store');
    });
