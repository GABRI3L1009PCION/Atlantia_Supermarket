<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return Empleado::query()->with(['user', 'supervisor'])->latest()->paginate(25)->withQueryString();
    }

    /**
     * Crea empleado.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Empleado
    {
        return Empleado::query()->create(['uuid' => (string) Str::uuid(), ...$data]);
    }

    /**
     * Actualiza empleado.
     *
     * @param array<string, mixed> $data
     */
    public function update(Empleado $empleado, array $data): Empleado
    {
        $empleado->update($data);

        return $empleado->refresh();
    }
}

