<?php

use App\Http\Controllers\Empleado\CourierFinanceController;
use App\Http\Controllers\Empleado\CourierSupportController;
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
    ->middleware(['auth', 'verified', 'role:empleado|bodeguero|soporte|contabilidad_finanzas|supervisor_logistica', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/transferencias', [ValidacionTransferenciaController::class, 'index'])
            ->name('transferencias.index');
        Route::patch('/transferencias/{payment}', [ValidacionTransferenciaController::class, 'update'])
            ->name('transferencias.update');

        Route::get('/repartidores/finanzas', [CourierFinanceController::class, 'index'])
            ->name('finanzas-repartidores.index');
        Route::patch('/repartidores/finanzas/cuentas/{courierProfile}', [CourierFinanceController::class, 'verifyBankAccount'])
            ->name('finanzas-repartidores.bank-accounts.update');
        Route::patch('/repartidores/finanzas/retiros/{withdrawal:uuid}', [CourierFinanceController::class, 'updateWithdrawal'])
            ->name('finanzas-repartidores.withdrawals.update');
        Route::patch('/repartidores/finanzas/liquidaciones/{settlement:uuid}', [CourierFinanceController::class, 'updateCashSettlement'])
            ->name('finanzas-repartidores.cash-settlements.update');

        Route::get('/soporte-repartidores', [CourierSupportController::class, 'index'])
            ->name('soporte-repartidores.index');
        Route::patch('/soporte-repartidores/{ticket:uuid}/asignar', [CourierSupportController::class, 'assign'])
            ->name('soporte-repartidores.assign');
        Route::post('/soporte-repartidores/{ticket:uuid}/responder', [CourierSupportController::class, 'reply'])
            ->name('soporte-repartidores.reply');
        Route::patch('/soporte-repartidores/{ticket:uuid}/estado', [CourierSupportController::class, 'updateStatus'])
            ->name('soporte-repartidores.status');

        Route::get('/mensajes-contacto', [MensajeContactoController::class, 'index'])->name('mensajes.index');
        Route::post('/mensajes-contacto/{message}/responder', [MensajeContactoController::class, 'respond'])
            ->name('mensajes.respond');

        Route::get('/resenas', [ModeracionResenaController::class, 'index'])->name('resenas.index');
        Route::patch('/resenas/{resena:uuid}', [ModeracionResenaController::class, 'update'])
            ->name('resenas.update');
    });
