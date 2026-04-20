<?php

use App\Http\Controllers\Api\PrediccionApiController;
use App\Http\Controllers\Api\RecomendacionApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas ML
|--------------------------------------------------------------------------
|
| Endpoints internos de consumo ML separados del API general para controlar
| throttling, auditoria y autenticacion tecnica de integraciones.
|
*/

Route::prefix('api/ml')
    ->as('ml.')
    ->middleware(['api', 'auth:api', 'security.headers', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/recomendaciones', [RecomendacionApiController::class, 'index'])->name('recomendaciones.index');
        Route::get('/predicciones/{producto:uuid}', [PrediccionApiController::class, 'show'])
            ->name('predicciones.show');
    });
