<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

/**
 * Registra eventos de dominio relevantes en auditoria.
 */
class RegistrarAuditoria implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Registra evento serializable.
     *
     * @param object $event
     * @return void
     */
    public function handle(object $event): void
    {
        try {
            $reflection = new ReflectionClass($event);
            $model = $this->firstModelProperty($event);

            AuditLog::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => null,
                'event' => 'event.' . Str::snake($reflection->getShortName()),
                'auditable_type' => $model ? $model::class : null,
                'auditable_id' => $model?->getKey(),
                'metadata' => ['event_class' => $event::class],
                'request_id' => request()?->headers->get('X-Request-Id'),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Obtiene el primer modelo publico del evento.
     *
     * @param object $event
     * @return mixed
     */
    private function firstModelProperty(object $event): mixed
    {
        foreach (get_object_vars($event) as $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                return $value;
            }
        }

        return null;
    }
}
