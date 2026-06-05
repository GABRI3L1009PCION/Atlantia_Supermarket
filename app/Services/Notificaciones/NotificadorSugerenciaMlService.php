<?php

namespace App\Services\Notificaciones;

use App\Contracts\NotificacionContract;
use App\Models\Ml\RestockSuggestion;
use App\Models\SentEmail;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Illuminate\Support\Str;

/**
 * Servicio de notificaciones sobre sugerencias ML.
 */
class NotificadorSugerenciaMlService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(private readonly NotificacionContract $notificationService)
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

        $this->notificationService->enviar($user, 'ml.sugerencia_reabasto', $data);

        if ($this->wasRecentlySent($user->id, (string) $suggestion->producto->uuid)) {
            return;
        }

        $email = SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => 'Sugerencia de reabastecimiento Atlantia',
            'template' => 'ml.sugerencia_reabasto',
            'status' => 'queued',
            'metadata' => $data,
        ]);

        try {
            Mail::raw($this->emailBody($suggestion), function ($message) use ($user): void {
                $message
                    ->to($user->email, $user->name)
                    ->subject('Atlantia ML: reabastece un producto en riesgo');
            });

            $email->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $email->update(['status' => 'failed', 'error' => $exception->getMessage()]);
        }
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

    private function wasRecentlySent(int $userId, string $productoUuid): bool
    {
        return SentEmail::query()
            ->where('user_id', $userId)
            ->where('template', 'ml.sugerencia_reabasto')
            ->where('metadata', 'like', '%' . $productoUuid . '%')
            ->where('created_at', '>=', now()->subHours(12))
            ->exists();
    }

    private function emailBody(RestockSuggestion $suggestion): string
    {
        $producto = $suggestion->producto;

        return implode("\n", [
            'Atlantia Supermarket - Alerta ML de reabasto',
            '',
            "Producto: {$producto->nombre}",
            "Urgencia: {$suggestion->urgencia}",
            "Stock actual: {$suggestion->stock_actual}",
            "Cantidad sugerida: {$suggestion->stock_sugerido}",
            'Dias estimados hasta quiebre: ' . ($suggestion->dias_hasta_quiebre ?? 'sin dato'),
            '',
            'Recomendacion: revisa este producto en Reabasto ML y confirma la reposicion para evitar quiebres de stock.',
            route('vendedor.reabasto.index'),
        ]);
    }
}
