<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\NominaDetalle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Generacion y conciliacion de planillas internas.
 */
class NominaService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Nomina::query()
            ->withCount('detalles')
            ->when($filters['estado'] ?? null, fn ($query, string $estado) => $query->where('estado', $estado))
            ->latest('periodo_fin')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return array<string, int|float>
     */
    public function dashboard(): array
    {
        return [
            'empleados_activos' => Empleado::query()->active()->count(),
            'salarios_configurados' => Empleado::query()->active()->where('salario_base', '>', 0)->count(),
            'borradores' => Nomina::query()->where('estado', 'borrador')->count(),
            'pagadas' => Nomina::query()->where('estado', 'pagada')->count(),
            'pendiente_pago' => (float) Nomina::query()->where('estado', 'borrador')->sum('total_neto'),
        ];
    }

    public function detail(Nomina $nomina): Nomina
    {
        return $nomina->load([
            'detalles.empleado.user',
            'generadaPor',
            'pagadaPor',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function generate(array $data, User $user): Nomina
    {
        return DB::transaction(function () use ($data, $user): Nomina {
            $exists = Nomina::query()
                ->where('periodo_inicio', $data['periodo_inicio'])
                ->where('periodo_fin', $data['periodo_fin'])
                ->where('tipo_periodo', $data['tipo_periodo'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'periodo_inicio' => 'Ya existe una nomina para ese periodo y tipo de pago.',
                ]);
            }

            $empleados = Empleado::query()
                ->active()
                ->where('salario_base', '>', 0)
                ->orderBy('codigo_empleado')
                ->get();

            if ($empleados->isEmpty()) {
                throw ValidationException::withMessages([
                    'periodo_inicio' => 'Configura el salario base de al menos un empleado activo antes de generar la nomina.',
                ]);
            }

            $nomina = Nomina::query()->create([
                'uuid' => (string) Str::uuid(),
                'periodo_inicio' => $data['periodo_inicio'],
                'periodo_fin' => $data['periodo_fin'],
                'tipo_periodo' => $data['tipo_periodo'],
                'estado' => 'borrador',
                'generada_por' => $user->id,
                'notas' => $data['notas'] ?? null,
            ]);

            foreach ($empleados as $empleado) {
                $salarioBase = (float) $empleado->salario_base;

                $nomina->detalles()->create([
                    'empleado_id' => $empleado->id,
                    'salario_base' => $salarioBase,
                    'bonificaciones' => 0,
                    'descuentos' => 0,
                    'total_neto' => $salarioBase,
                ]);
            }

            return $this->recalculate($nomina);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDetail(Nomina $nomina, NominaDetalle $detalle, array $data): Nomina
    {
        $this->ensureEditable($nomina);

        if ((int) $detalle->nomina_id !== (int) $nomina->id) {
            abort(404);
        }

        return DB::transaction(function () use ($nomina, $detalle, $data): Nomina {
            $neto = max(0, (float) $detalle->salario_base + (float) $data['bonificaciones'] - (float) $data['descuentos']);

            $detalle->update([
                'bonificaciones' => $data['bonificaciones'],
                'descuentos' => $data['descuentos'],
                'total_neto' => $neto,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            return $this->recalculate($nomina);
        });
    }

    public function markAsPaid(Nomina $nomina, User $user): Nomina
    {
        $this->ensureEditable($nomina);

        return DB::transaction(function () use ($nomina, $user): Nomina {
            $this->recalculate($nomina);

            $nomina->update([
                'estado' => 'pagada',
                'pagada_por' => $user->id,
                'pagada_at' => now(),
            ]);

            return $nomina->refresh();
        });
    }

    private function recalculate(Nomina $nomina): Nomina
    {
        $totals = NominaDetalle::query()
            ->where('nomina_id', $nomina->id)
            ->selectRaw('COALESCE(SUM(salario_base), 0) as bruto')
            ->selectRaw('COALESCE(SUM(bonificaciones), 0) as bonificaciones')
            ->selectRaw('COALESCE(SUM(descuentos), 0) as descuentos')
            ->selectRaw('COALESCE(SUM(total_neto), 0) as neto')
            ->firstOrFail();

        $nomina->update([
            'total_bruto' => $totals->bruto,
            'total_bonificaciones' => $totals->bonificaciones,
            'total_descuentos' => $totals->descuentos,
            'total_neto' => $totals->neto,
        ]);

        return $nomina->refresh();
    }

    private function ensureEditable(Nomina $nomina): void
    {
        if ($nomina->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'nomina' => 'La nomina ya fue cerrada y no admite modificaciones.',
            ]);
        }
    }
}
