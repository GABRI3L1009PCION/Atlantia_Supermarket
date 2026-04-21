<?php

namespace App\Services\Auditoria;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method))
            ->when($filters['request_id'] ?? null, fn ($query, $requestId) => $query->where('request_id', $requestId))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('event', 'like', '%' . $search . '%')
                        ->orWhere('url', 'like', '%' . $search . '%')
                        ->orWhere('request_id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Devuelve detalle de auditoria.
     */
    public function detail(AuditLog $auditLog): AuditLog
    {
        return $auditLog->load(['user', 'auditable']);
    }

    /**
     * Resume actividad del panel de auditoria.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $logs = AuditLog::query()
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        return [
            'eventos_24h' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
            'usuarios_activos_24h' => AuditLog::query()->where('created_at', '>=', now()->subDay())->distinct('user_id')->count('user_id'),
            'requests_unicos_24h' => AuditLog::query()->where('created_at', '>=', now()->subDay())->whereNotNull('request_id')->distinct('request_id')->count('request_id'),
            'eventos_top' => $logs->groupBy('event')->map->count()->sortDesc()->take(6),
            'metodos_http' => $logs->groupBy(fn (AuditLog $log) => $log->method ?: 'N/A')->map->count()->sortDesc(),
        ];
    }

    /**
     * Usuarios disponibles para filtrar auditoria.
     *
     * @return Collection<int, User>
     */
    public function filterUsers(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }
}
