<?php

use App\Exceptions\AtlantiaDomainException;
use App\Http\Middleware\AuditoriaRequest;
use App\Http\Middleware\BloquearPorIntentos;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\LimitarPorRuta;
use App\Http\Middleware\PrivateNetworkAccess;
use App\Http\Middleware\RateLimitCheckout;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VendedorAprobado;
use App\Http\Middleware\VerificarFirmaRecurrente;
use App\Http\Middleware\VerificarMlServiceToken;
use App\Http\Middleware\VerificarOwnership;
use App\Http\Middleware\VerificarWebhookHmac;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
        /* En produccion, TRUSTED_PROXIES debe apuntar al balanceador/CDN. */
        $trustedProxies = (string) env('TRUSTED_PROXIES', '');
        $trustedProxies = $trustedProxies === ''
            ? null
            : ($trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)));

        $middleware->trustProxies(at: $trustedProxies, headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->prepend(PrivateNetworkAccess::class);

        $middleware->web(append: [
            ForceHttps::class,
            SecurityHeaders::class,
            /* Permite "cerrar sesión en los demás dispositivos" al cambiar la contraseña. */
            \App\Http\Middleware\AutenticarSesion::class,
        ]);
        $middleware->api(append: [
            ForceHttps::class,
            SecurityHeaders::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/pasarela-pago',
            'webhooks/certificador-fel',
            'webhooks/courier-externo',
            'webhooks/ml-service',
            'webhooks/recurrente',
        ]);

        $middleware->alias([
            'audit.request' => AuditoriaRequest::class,
            'checkout.rate' => RateLimitCheckout::class,
            'force.https' => ForceHttps::class,
            'login.lockout' => BloquearPorIntentos::class,
            'ml.token' => VerificarMlServiceToken::class,
            'ownership' => VerificarOwnership::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'security.headers' => SecurityHeaders::class,
            /* Contador de intentos independiente por ruta (ver LimitarPorRuta). */
            'throttle' => LimitarPorRuta::class,
            'vendedor.aprobado' => VendedorAprobado::class,
            'verify.webhook.hmac' => VerificarWebhookHmac::class,
            'verify.webhook.recurrente' => VerificarFirmaRecurrente::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Demasiados intentos al enviar un formulario: se regresa al mismo formulario
         * con un aviso claro y lo escrito, en vez de dejar a la persona en una página
         * de error. Solo las peticiones GET o JSON muestran la página 429.
         */
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            /* Solo si se sabe de qué formulario vino; si no, se muestra la página de espera. */
            $anterior = (string) ($request->headers->get('referer') ?: ($request->hasSession() ? $request->session()->previousUrl() : ''));

            if ($request->expectsJson() || $request->isMethod('GET') || $anterior === '' || ! str_starts_with($anterior, url('/')) || $anterior === $request->fullUrl()) {
                return null;
            }

            $segundos = max(1, (int) ($exception->getHeaders()['Retry-After'] ?? 60));
            $espera = match (true) {
                $segundos < 60 => 'unos segundos',
                $segundos < 120 => '1 minuto',
                $segundos < 3600 => (int) ceil($segundos / 60).' minutos',
                $segundos < 7200 => '1 hora',
                default => (int) ceil($segundos / 3600).' horas',
            };

            return redirect()->to($anterior)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', 'code', '_token']))
                ->with('error', "Hiciste varios intentos seguidos. Por seguridad, espera {$espera} y vuelve a intentarlo.");
        });

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
