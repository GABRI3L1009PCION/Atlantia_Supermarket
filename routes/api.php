<?php

use App\Http\Controllers\Api\BusquedaController;
use App\Http\Controllers\Api\CarritoApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PrediccionApiController;
use App\Http\Controllers\Api\RecomendacionApiController;
use App\Http\Controllers\Api\RutaApiController;
use App\Http\Controllers\Api\StockApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API
|--------------------------------------------------------------------------
|
| Endpoints JSON para Livewire/JS interno, Passport y operaciones asincronas
| del frontend sin convertir Atlantia en SPA.
|
*/

Route::as('api.')
    ->middleware(['api', 'security.headers'])
    ->group(function (): void {
        Route::get('/buscar', BusquedaController::class)->middleware('throttle:120,1')->name('buscar');
        Route::get('/stock/{producto:uuid}', [StockApiController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('stock.show');

        Route::middleware(['auth:api', 'throttle:120,1'])->group(function (): void {
            Route::get('/carrito', [CarritoApiController::class, 'show'])->name('carrito.show');
            Route::put('/carrito', [CarritoApiController::class, 'sync'])->name('carrito.sync');

            Route::get('/rutas/{pedido:uuid}', [RutaApiController::class, 'show'])->name('rutas.show');
            Route::post('/rutas/preview', [RutaApiController::class, 'preview'])->name('rutas.preview');

            Route::get('/notificaciones', [NotificacionApiController::class, 'index'])->name('notificaciones.index');
            Route::patch('/notificaciones/read', [NotificacionApiController::class, 'markAsRead'])
                ->name('notificaciones.read');

            Route::get('/recomendaciones', [RecomendacionApiController::class, 'index'])
                ->name('recomendaciones.index');
            Route::get('/predicciones/{producto:uuid}', [PrediccionApiController::class, 'show'])
                ->name('predicciones.show');
        });
    });
