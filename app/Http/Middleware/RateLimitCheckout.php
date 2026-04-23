<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica limite especifico para finalizacion de compra.
 */
class RateLimitCheckout
{
    /**
     * Maximo de intentos por minuto.
     */
    private const MAX_ATTEMPTS = 10;

    /**
     * Crea una instancia del middleware.
     */
    public function __construct(private readonly RateLimiter $limiter)
    {
    }

    /**
     * Limita intentos de checkout por usuario o IP.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->key($request);

        if ($this->limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = $this->limiter->availableIn($key);

            return response()->json([
                'message' => 'Demasiados intentos de checkout. Intenta nuevamente pronto.',
                'retry_after' => $seconds,
            ], 429)->withHeaders([
                'Retry-After' => (string) $seconds,
                'X-RateLimit-Limit' => (string) self::MAX_ATTEMPTS,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        $this->limiter->hit($key, 60);
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) self::MAX_ATTEMPTS);
        $response->headers->set(
            'X-RateLimit-Remaining',
            (string) $this->limiter->remaining($key, self::MAX_ATTEMPTS)
        );

        return $response;
    }

    /**
     * Construye la llave de rate limit.
     *
     * @param Request $request
     * @return string
     */
    private function key(Request $request): string
    {
        return 'checkout:' . ($request->user()?->id ?? $request->ip());
    }
}
