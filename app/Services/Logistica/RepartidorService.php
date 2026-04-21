<?php

namespace App\Services\Logistica;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio administrativo de repartidores.
 */
class RepartidorService
{
    /**
     * Pagina repartidores.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return User::role('repartidor')
            ->with(['roles'])
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Detalle operativo del repartidor.
     */
    public function detail(User $repartidor): User
    {
        return $repartidor->load(['roles']);
    }

    /**
     * Crea un repartidor operativo.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function create(array $data): User
    {
        $repartidor = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
            'email_verified_at' => now(),
            'is_system_user' => false,
            'two_factor_enabled' => false,
        ]);

        $repartidor->assignRole('repartidor');

        return $repartidor->fresh(['roles']);
    }

    /**
     * Actualiza un repartidor.
     *
     * @param array<string, mixed> $data
     * @return User
     */
    public function update(User $repartidor, array $data): User
    {
        $repartidor->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $repartidor->password = Hash::make((string) $data['password']);
        }

        $repartidor->save();

        if (! $repartidor->hasRole('repartidor')) {
            $repartidor->syncRoles(['repartidor']);
        }

        return $repartidor->fresh(['roles']);
    }

    /**
     * Elimina logicamente un repartidor.
     */
    public function delete(User $repartidor): void
    {
        $repartidor->status = 'inactive';
        $repartidor->save();
        $repartidor->delete();
    }
}
