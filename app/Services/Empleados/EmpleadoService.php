<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Servicio administrativo de empleados.
 */
class EmpleadoService
{
    /**
     * Pagina empleados.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Empleado::query()
            ->with(['user', 'supervisor.user'])
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('codigo_empleado', 'like', '%' . $search . '%')
                        ->orWhere('departamento', 'like', '%' . $search . '%')
                        ->orWhere('puesto', 'like', '%' . $search . '%')
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
     * Carga detalle operativo del empleado.
     */
    public function detail(Empleado $empleado): Empleado
    {
        return $empleado->load(['user.roles', 'supervisor.user', 'supervisados.user']);
    }

    /**
     * Crea empleado.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Empleado
    {
        return DB::transaction(function () use ($data): Empleado {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'email_verified_at' => now(),
                'is_system_user' => true,
                'two_factor_enabled' => false,
            ]);

            $user->assignRole('empleado');

            return Empleado::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'codigo_empleado' => $data['codigo_empleado'],
                'departamento' => $data['departamento'],
                'puesto' => $data['puesto'],
                'telefono_interno' => $data['telefono_interno'] ?? null,
                'fecha_contratacion' => $data['fecha_contratacion'],
                'status' => $data['status'],
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'permisos_operativos' => $data['permisos_operativos'] ?? [],
            ]);
        });
    }

    /**
     * Actualiza empleado.
     *
     * @param array<string, mixed> $data
     */
    public function update(Empleado $empleado, array $data): Empleado
    {
        return DB::transaction(function () use ($empleado, $data): Empleado {
            $empleado->user?->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
                'is_system_user' => true,
            ]);

            if (! empty($data['password']) && $empleado->user !== null) {
                $empleado->user->update(['password' => Hash::make((string) $data['password'])]);
            }

            $empleado->update([
                'codigo_empleado' => $data['codigo_empleado'],
                'departamento' => $data['departamento'],
                'puesto' => $data['puesto'],
                'telefono_interno' => $data['telefono_interno'] ?? null,
                'fecha_contratacion' => $data['fecha_contratacion'],
                'status' => $data['status'],
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'permisos_operativos' => $data['permisos_operativos'] ?? [],
            ]);

            if ($empleado->user !== null && ! $empleado->user->hasRole('empleado')) {
                $empleado->user->syncRoles(['empleado']);
            }

            return $empleado->refresh()->load(['user.roles', 'supervisor.user']);
        });
    }

    /**
     * Elimina logicamente un empleado.
     */
    public function delete(Empleado $empleado): void
    {
        DB::transaction(function () use ($empleado): void {
            $empleado->update(['status' => 'inactive']);
            $empleado->user?->update(['status' => 'inactive']);
            $empleado->delete();
        });
    }
}
