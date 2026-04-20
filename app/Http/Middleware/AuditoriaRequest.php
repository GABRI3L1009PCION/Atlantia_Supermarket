<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra solicitudes criticas en auditoria append-only.
 */
class AuditoriaRequest
{
    /**
     * Campos que nunca deben guardarse en auditoria.
     *
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'card_number',
        'cvv',
        'xml_dte',
    ];

    /**
     * Registra contexto minimo de la solicitud.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);
        $response = $next($request);

        if ($this->shouldAudit($request)) {
            $this->storeAuditLog($request, $response, $requestId);
        }

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    /**
     * Determina si la solicitud debe auditarse.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldAudit(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $request->is('admin/*')
            || $request->is('vendedor/*')
            || $request->is('webhooks/*');
    }

    /**
     * Persiste el evento de auditoria sin interrumpir la respuesta principal.
     *
     * @param Request $request
     * @param Response $response
     * @param string $requestId
     * @return void
     */
    private function storeAuditLog(Request $request, Response $response, string $requestId): void
    {
        try {
            AuditLog::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $request->user()?->id,
                'event' => $this->eventName($request),
                'metadata' => [
                    'route' => $request->route()?->getName(),
                    'status' => $response->getStatusCode(),
                    'input' => $this->safeInput($request),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'request_id' => $requestId,
                'url' => mb_substr($request->fullUrl(), 0, 500),
                'method' => $request->method(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Genera nombre estable del evento.
     *
     * @param Request $request
     * @return string
     */
    private function eventName(Request $request): string
    {
        $event = 'request.' . mb_strtolower($request->method()) . '.';
        $event .= $request->route()?->getName() ?? 'anon';

        return mb_substr($event, 0, 120);
    }

    /**
     * Remueve datos sensibles del input.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    private function safeInput(Request $request): array
    {
        return collect($request->except($this->sensitiveKeys))
            ->map(fn (mixed $value) => is_string($value) ? mb_substr($value, 0, 250) : $value)
            ->all();
    }
}
