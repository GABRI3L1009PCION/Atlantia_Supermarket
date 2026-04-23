<?php

use App\Exceptions\AtlantiaDomainException;
use App\Http\Middleware\AuditoriaRequest;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\RateLimitCheckout;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VendedorAprobado;
use App\Http\Middleware\VerificarMlServiceToken;
use App\Http\Middleware\VerificarOwnership;
use App\Http\Middleware\VerificarWebhookHmac;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            require base_path('routes/ml.php');
            require base_path('routes/webhooks.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
        $middleware->api(append: [
            SecurityHeaders::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/pasarela-pago',
            'webhooks/certificador-fel',
            'webhooks/courier-externo',
            'webhooks/ml-service',
        ]);

        $middleware->alias([
            'audit.request' => AuditoriaRequest::class,
            'checkout.rate' => RateLimitCheckout::class,
            'force.https' => ForceHttps::class,
            'ml.token' => VerificarMlServiceToken::class,
            'ownership' => VerificarOwnership::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'security.headers' => SecurityHeaders::class,
            'vendedor.aprobado' => VendedorAprobado::class,
            'verify.webhook.hmac' => VerificarWebhookHmac::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AtlantiaDomainException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->publicMessage(),
                ], $exception->statusCode());
            }

            return back()
                ->withInput()
                ->with('error', $exception->publicMessage())
                ->with('error_type', class_basename($exception));
        });
    })->create();
