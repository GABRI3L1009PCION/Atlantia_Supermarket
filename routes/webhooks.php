<?php

use App\Http\Controllers\Webhook\CertificadorFelController;
use App\Http\Controllers\Webhook\CourierExternoController;
use App\Http\Controllers\Webhook\MlServiceWebhookController;
use App\Http\Controllers\Webhook\PasarelaPagoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Webhook
|--------------------------------------------------------------------------
|
| Callbacks externos con verificacion HMAC. No usan sesion de usuario.
|
*/

Route::prefix('webhooks')
    ->as('webhooks.')
    ->middleware(['api', 'throttle:60,1', 'verify.webhook.hmac'])
    ->group(function (): void {
        Route::post('/pasarela-pago', PasarelaPagoController::class)->name('pasarela-pago');
        Route::post('/certificador-fel', CertificadorFelController::class)->name('certificador-fel');
        Route::post('/courier-externo', CourierExternoController::class)->name('courier-externo');
        Route::post('/ml-service', MlServiceWebhookController::class)->name('ml-service');
    });
