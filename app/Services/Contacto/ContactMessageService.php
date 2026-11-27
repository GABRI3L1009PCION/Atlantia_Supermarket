<?php

namespace App\Services\Contacto;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\MensajeRespondidoNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de mensajes de contacto.
 */
class ContactMessageService
{
    /**
     * Pagina mensajes de contacto.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when(isset($filters['atendido']) && $filters['atendido'] !== '', fn ($query) => $query->where('atendido', (bool) $filters['atendido']))
            ->when(in_array($filters['tipo'] ?? null, ['contacto', 'cotizacion', 'soporte'], true), fn ($query) => $query->where('tipo', $filters['tipo']))
            ->with(['user', 'atendidoPor'])
            ->orderBy('atendido')
            ->orderByRaw("case prioridad when 'alta' then 0 when 'normal' then 1 else 2 end")
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Marca mensaje como atendido.
     *
     * @param  array<string, mixed>  $data
     */
    public function respond(ContactMessage $message, array $data, User $user): ContactMessage
    {
        $respuesta = trim((string) ($data['respuesta'] ?? ''));
        $message->update([
            'atendido' => true,
            'atendido_por' => $user->id,
            'atendido_at' => now(),
            'respuesta' => $respuesta !== '' ? $respuesta : null,
        ]);

        /* Si quien escribió tiene cuenta, le avisamos dentro de su panel (no se envía correo). */
        if ($message->user !== null && $respuesta !== '') {
            try {
                $message->user->notify(new MensajeRespondidoNotification($message));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $message->refresh();
    }
}
