<?php

use App\Http\Controllers\Repartidor\DashboardController;
use App\Http\Controllers\Repartidor\EstadoController;
use App\Http\Controllers\Repartidor\ExternalDeliveryController;
use App\Http\Controllers\Repartidor\GananciasController;
use App\Http\Controllers\Repartidor\GeolocalizacionController;
use App\Http\Controllers\Repartidor\HistorialController;
use App\Http\Controllers\Repartidor\IncidenciaController;
use App\Http\Controllers\Repartidor\OfertaController;
use App\Http\Controllers\Repartidor\PedidoController;
use App\Http\Controllers\Repartidor\RutaController;
use App\Http\Controllers\Repartidor\SoporteController;
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
    ->middleware(['auth', 'verified', 'role:repartidor', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::patch('/estado-disponibilidad', [EstadoController::class, 'availability'])->name('estado.disponibilidad');
        Route::patch('/auto-aceptacion', [EstadoController::class, 'autoAcceptance'])->name('estado.auto-aceptacion');

        Route::get('/ganancias', GananciasController::class)->name('ganancias.index');
        Route::patch('/ganancias/cuenta-bancaria', [GananciasController::class, 'updateBankAccount'])->name('ganancias.bank-account.update');
        Route::post('/ganancias/retiros', [GananciasController::class, 'requestWithdrawal'])->name('ganancias.withdrawals.store');
        Route::post('/ganancias/liquidaciones-efectivo', [GananciasController::class, 'requestCashSettlement'])->name('ganancias.cash-settlements.store');
        Route::get('/historial', HistorialController::class)->name('historial.index');
        Route::get('/soporte', [SoporteController::class, 'index'])->name('soporte.index');
        Route::post('/soporte/tickets', [SoporteController::class, 'store'])->name('soporte.tickets.store');
        Route::post('/soporte/tickets/{ticket:uuid}/responder', [SoporteController::class, 'reply'])->name('soporte.tickets.reply');
        Route::patch('/soporte/tickets/{ticket:uuid}/estado', [SoporteController::class, 'updateStatus'])->name('soporte.tickets.status');
        Route::post('/emergencia', [SoporteController::class, 'emergency'])
            ->middleware('throttle:10,1')
            ->name('emergencia.store');

        Route::patch('/ofertas/{offer:uuid}/aceptar', [OfertaController::class, 'accept'])->name('ofertas.accept');
        Route::patch('/ofertas/{offer:uuid}/rechazar', [OfertaController::class, 'reject'])->name('ofertas.reject');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::patch('/pedidos/{pedido:uuid}/aceptar', [PedidoController::class, 'accept'])->name('pedidos.accept');
        Route::patch('/pedidos/{pedido:uuid}/rechazar', [PedidoController::class, 'reject'])->name('pedidos.reject');
        Route::patch('/pedidos/{pedido:uuid}/llegue-comercio', [PedidoController::class, 'arrivedPickup'])->name('pedidos.arrived-pickup');
        Route::patch('/pedidos/{pedido:uuid}/no-listo', [PedidoController::class, 'pickupNotReady'])->name('pedidos.pickup-not-ready');
        Route::patch('/pedidos/{pedido:uuid}/recoger', [PedidoController::class, 'pickup'])->name('pedidos.pickup');
        Route::patch('/pedidos/{pedido:uuid}/llegue-cliente', [PedidoController::class, 'arrivedCustomer'])->name('pedidos.arrived-customer');
        Route::patch('/pedidos/{pedido:uuid}/entregar', [PedidoController::class, 'deliver'])->name('pedidos.deliver');
        Route::patch('/pedidos/{pedido:uuid}/estado', [PedidoController::class, 'updateEstado'])
            ->name('pedidos.estado');

        Route::get('/entregas-externas', [ExternalDeliveryController::class, 'index'])->name('externas.index');
        Route::get('/entregas-externas/{externalDeliveryOrder:uuid}', [ExternalDeliveryController::class, 'show'])->name('externas.show');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/llegue-tienda', [ExternalDeliveryController::class, 'arrivedPickup'])->name('externas.arrived-pickup');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/no-listo', [ExternalDeliveryController::class, 'pickupNotReady'])->name('externas.pickup-not-ready');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/recoger', [ExternalDeliveryController::class, 'pickedUp'])->name('externas.picked-up');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/llegue-cliente', [ExternalDeliveryController::class, 'arrivedCustomer'])->name('externas.arrived-customer');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/efectivo', [ExternalDeliveryController::class, 'cashIssue'])->name('externas.cash-issue');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/entregar', [ExternalDeliveryController::class, 'deliver'])->name('externas.deliver');

        Route::get('/rutas', [RutaController::class, 'index'])->name('rutas.index');
        Route::get('/rutas/{route}', [RutaController::class, 'show'])->name('rutas.show');
        Route::patch('/rutas/{route}/completar', [RutaController::class, 'complete'])->name('rutas.complete');

        Route::post('/gps', [GeolocalizacionController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('gps.store');

        Route::post('/pedidos/{pedido:uuid}/incidencias', [IncidenciaController::class, 'store'])
            ->name('incidencias.store');
    });
