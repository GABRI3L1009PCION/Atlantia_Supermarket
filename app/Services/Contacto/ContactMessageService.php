<?php

namespace App\Services\Contacto;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de mensajes de contacto.
 */
class ContactMessageService
{
    /**
     * Pagina mensajes de contacto.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when(isset($filters['atendido']), fn ($query) => $query->where('atendido', (bool) $filters['atendido']))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Marca mensaje como atendido.
     *
     * @param array<string, mixed> $data
     */
    public function respond(ContactMessage $message, array $data, User $user): ContactMessage
    {
        $message->update([
            'atendido' => true,
            'atendido_por' => $user->id,
            'atendido_at' => now(),
        ]);

        return $message->refresh();
    }
}

