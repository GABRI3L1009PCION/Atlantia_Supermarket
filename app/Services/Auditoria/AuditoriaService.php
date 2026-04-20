<?php

namespace App\Services\Auditoria;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio de consulta de auditoria append-only.
 */
class AuditoriaService
{
    /**
     * Pagina registros de auditoria.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user')
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Devuelve detalle de auditoria.
     */
    public function detail(AuditLog $auditLog): AuditLog
    {
        return $auditLog->load('user');
    }
}

