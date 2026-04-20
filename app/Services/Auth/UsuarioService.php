<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Servicio administrativo de usuarios.
 */
class UsuarioService
{
    /**
     * Pagina usuarios visibles para administracion operativa.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->visibleToOperationalAdmin()
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(fn ($builder) => $builder
                    ->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%'));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Devuelve detalle del usuario.
     */
    public function detail(User $user): User
    {
        return $user->load(['roles', 'vendor', 'empleado', 'clienteDetalle']);
    }

    /**
     * Actualiza datos operativos de usuario.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User
    {
        $user->fill(collect($data)->only(['name', 'phone', 'status'])->all());
        $user->save();

        if (isset($data['roles']) && ! $user->isSuperAdmin()) {
            $user->syncRoles($data['roles']);
        }

        return $user->refresh();
    }
}

