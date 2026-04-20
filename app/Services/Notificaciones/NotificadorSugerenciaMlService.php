<?php

namespace App\Services\Notificaciones;

use App\Models\Ml\RestockSuggestion;
use App\Models\SentEmail;
use Illuminate\Support\Str;

/**
 * Servicio de notificaciones sobre sugerencias ML.
 */
class NotificadorSugerenciaMlService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * Notifica una sugerencia de reabastecimiento generada por ML.
     *
     * @param RestockSuggestion $suggestion
     * @return void
     */
    public function sugerenciaReabasto(RestockSuggestion $suggestion): void
    {
        $suggestion->loadMissing(['producto', 'vendor.user']);
        $user = $suggestion->vendor?->user;

        if ($user === null) {
            return;
        }

        $data = [
            'titulo' => 'Sugerencia de reabastecimiento',
            'mensaje' => "Se sugiere reabastecer {$suggestion->producto->nombre}.",
            'producto_uuid' => $suggestion->producto->uuid,
            'stock_actual' => $suggestion->stock_actual,
            'stock_sugerido' => $suggestion->stock_sugerido,
            'urgencia' => $suggestion->urgencia,
        ];

        $this->notificationService->create($user, 'ml.sugerencia_reabasto', $data);

        SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => 'Sugerencia de reabastecimiento Atlantia',
            'template' => 'ml.sugerencia_reabasto',
            'status' => 'queued',
            'metadata' => $data,
        ]);
    }

    /**
     * Notifica varias sugerencias urgentes.
     *
     * @param iterable<int, RestockSuggestion> $suggestions
     * @return int
     */
    public function sugerenciasMasivas(iterable $suggestions): int
    {
        $enviadas = 0;

        foreach ($suggestions as $suggestion) {
            $this->sugerenciaReabasto($suggestion);
            $enviadas++;
        }

        return $enviadas;
    }
}
