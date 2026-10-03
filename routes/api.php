<?php

use App\Http\Controllers\Api\BusquedaController;
use App\Http\Controllers\Api\CarritoApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PrediccionApiController;
use App\Http\Controllers\Api\RecomendacionApiController;
use App\Http\Controllers\Api\Repartidor\AuthController as RepartidorAuthController;
use App\Http\Controllers\Api\Repartidor\DashboardController as RepartidorDashboardController;
use App\Http\Controllers\Api\Repartidor\EstadoController as RepartidorEstadoController;
use App\Http\Controllers\Api\Repartidor\ExternalDeliveryController as RepartidorExternalDeliveryController;
use App\Http\Controllers\Api\Repartidor\FinanceController as RepartidorFinanceController;
use App\Http\Controllers\Api\Repartidor\GpsController as RepartidorGpsController;
use App\Http\Controllers\Api\Repartidor\HistoryController as RepartidorHistoryController;
use App\Http\Controllers\Api\Repartidor\OfferController as RepartidorOfferController;
use App\Http\Controllers\Api\Repartidor\PedidoController as RepartidorPedidoController;
use App\Http\Controllers\Api\Repartidor\SupportController as RepartidorSupportController;
use App\Http\Controllers\Api\Repartidor\WalletController as RepartidorWalletController;
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
        Route::options('/{any}', fn () => response('', 204)->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
            'Access-Control-Allow-Private-Network' => 'true',
        ]))->where('any', '.*')->name('options');

        Route::get('/buscar', BusquedaController::class)->middleware('throttle:busqueda')->name('buscar');
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

        Route::prefix('repartidor')
            ->as('repartidor.')
            ->middleware('throttle:60,1')
            ->group(function (): void {
                Route::get('/ping', fn () => response()->json([
                    'data' => [
                        'service' => 'atlantia-repartidor-api',
                        'status' => 'ok',
                        'server_time' => now()->toIso8601String(),
                    ],
                ]))->name('ping');

                Route::post('/login', [RepartidorAuthController::class, 'login'])->name('login');

                Route::middleware(['auth:api', 'role:repartidor'])->group(function (): void {
                    Route::get('/me', [RepartidorAuthController::class, 'me'])->name('me');
                    Route::post('/logout', [RepartidorAuthController::class, 'logout'])->name('logout');
                    Route::post('/device', [RepartidorAuthController::class, 'registerDevice'])->name('device.store');

                    Route::get('/dashboard', RepartidorDashboardController::class)->name('dashboard');
                    Route::get('/wallet', RepartidorWalletController::class)->name('wallet');
                    Route::patch('/wallet/bank-account', [RepartidorFinanceController::class, 'updateBankAccount'])->name('wallet.bank-account.update');
                    Route::post('/wallet/withdrawals', [RepartidorFinanceController::class, 'storeWithdrawal'])->name('wallet.withdrawals.store');
                    Route::post('/wallet/cash-settlements', [RepartidorFinanceController::class, 'storeCashSettlement'])->name('wallet.cash-settlements.store');
                    Route::get('/history', RepartidorHistoryController::class)->name('history');

                    Route::patch('/availability', [RepartidorEstadoController::class, 'availability'])->name('availability');
                    Route::patch('/auto-acceptance', [RepartidorEstadoController::class, 'autoAcceptance'])->name('auto-acceptance');
                    Route::post('/gps', [RepartidorGpsController::class, 'store'])->middleware('throttle:120,1')->name('gps.store');

                    Route::get('/offers', [RepartidorOfferController::class, 'index'])->name('offers.index');
                    Route::patch('/offers/{offer:uuid}/accept', [RepartidorOfferController::class, 'accept'])->name('offers.accept');
                    Route::patch('/offers/{offer:uuid}/reject', [RepartidorOfferController::class, 'reject'])->name('offers.reject');

                    Route::get('/orders', [RepartidorPedidoController::class, 'index'])->name('orders.index');
                    Route::get('/orders/{pedido:uuid}', [RepartidorPedidoController::class, 'show'])->name('orders.show');
                    Route::patch('/orders/{pedido:uuid}/accept', [RepartidorPedidoController::class, 'accept'])->name('orders.accept');
                    Route::patch('/orders/{pedido:uuid}/reject', [RepartidorPedidoController::class, 'reject'])->name('orders.reject');
                    Route::patch('/orders/{pedido:uuid}/arrived-pickup', [RepartidorPedidoController::class, 'arrivedPickup'])->name('orders.arrived-pickup');
                    Route::patch('/orders/{pedido:uuid}/pickup-not-ready', [RepartidorPedidoController::class, 'pickupNotReady'])->name('orders.pickup-not-ready');
                    Route::patch('/orders/{pedido:uuid}/pickup', [RepartidorPedidoController::class, 'pickup'])->name('orders.pickup');
                    Route::patch('/orders/{pedido:uuid}/arrived-customer', [RepartidorPedidoController::class, 'arrivedCustomer'])->name('orders.arrived-customer');
                    Route::patch('/orders/{pedido:uuid}/verify-code', [RepartidorPedidoController::class, 'verifyCode'])
                        ->middleware('throttle:6,1')
                        ->name('orders.verify-code');
                    Route::patch('/orders/{pedido:uuid}/deliver', [RepartidorPedidoController::class, 'deliver'])->name('orders.deliver');
                    Route::patch('/orders/{pedido:uuid}/ack-completion', [RepartidorPedidoController::class, 'acknowledgeCompletion'])->name('orders.ack-completion');

                    Route::get('/external-orders', [RepartidorExternalDeliveryController::class, 'index'])->name('external-orders.index');
                    Route::get('/external-orders/{externalDeliveryOrder:uuid}', [RepartidorExternalDeliveryController::class, 'show'])->name('external-orders.show');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/arrived-pickup', [RepartidorExternalDeliveryController::class, 'arrivedPickup'])->name('external-orders.arrived-pickup');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/pickup-not-ready', [RepartidorExternalDeliveryController::class, 'pickupNotReady'])->name('external-orders.pickup-not-ready');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/pickup', [RepartidorExternalDeliveryController::class, 'pickedUp'])->name('external-orders.pickup');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/arrived-customer', [RepartidorExternalDeliveryController::class, 'arrivedCustomer'])->name('external-orders.arrived-customer');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/verify-code', [RepartidorExternalDeliveryController::class, 'verifyCode'])
                        ->middleware('throttle:6,1')
                        ->name('external-orders.verify-code');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/cash-issue', [RepartidorExternalDeliveryController::class, 'cashIssue'])->name('external-orders.cash-issue');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/deliver', [RepartidorExternalDeliveryController::class, 'deliver'])->name('external-orders.deliver');
                    Route::patch('/external-orders/{externalDeliveryOrder:uuid}/ack-completion', [RepartidorExternalDeliveryController::class, 'acknowledgeCompletion'])->name('external-orders.ack-completion');

                    Route::get('/support', [RepartidorSupportController::class, 'index'])->name('support.index');
                    Route::post('/support/tickets', [RepartidorSupportController::class, 'store'])->name('support.tickets.store');
                    Route::post('/support/tickets/{ticket:uuid}/reply', [RepartidorSupportController::class, 'reply'])->name('support.tickets.reply');
                    Route::patch('/support/tickets/{ticket:uuid}/status', [RepartidorSupportController::class, 'updateStatus'])->name('support.tickets.status');
                    Route::post('/support/emergency', [RepartidorSupportController::class, 'emergency'])
                        ->middleware('throttle:10,1')
                        ->name('support.emergency');
                });
            });
    });
