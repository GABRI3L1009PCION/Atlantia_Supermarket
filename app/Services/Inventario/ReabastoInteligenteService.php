<?php

namespace App\Services\Inventario;

use App\Exceptions\TransaccionFallidaException;
use App\Models\AuditLog;
use App\Models\Inventario;
use App\Models\Ml\RestockSuggestion;
use App\Models\Ml\SalesPrediction;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Servicio de sugerencias de reabastecimiento basadas en demanda prevista.
 */
class ReabastoInteligenteService
{
    /**
     * Lista sugerencias de reabastecimiento para el vendedor autenticado.
     *
     * @param User $user
     * @return LengthAwarePaginator
     */
    public function forVendor(User $user): LengthAwarePaginator
    {
        return RestockSuggestion::query()
            ->with(['producto.inventario', 'producto.categoria'])
            ->where('vendor_id', $user->vendor?->id)
            ->latest()
            ->paginate(25);
    }

    /**
     * Genera sugerencias para todos los productos activos de un vendedor.
     *
     * @param Vendor $vendor
     * @return EloquentCollection<int, RestockSuggestion>
     */
    public function generarParaVendor(Vendor $vendor): EloquentCollection
    {
        $sugerencias = new EloquentCollection();

        $vendor->productos()
            ->with(['inventario'])
            ->active()
            ->chunkById(100, function ($productos) use ($sugerencias): void {
                foreach ($productos as $producto) {
                    $sugerencia = $this->generarParaProducto($producto);

                    if ($sugerencia !== null) {
                        $sugerencias->push($sugerencia);
                    }
                }
            });

        return $sugerencias;
    }

    /**
     * Genera o actualiza una sugerencia para un producto.
     *
     * @param Producto $producto
     * @return RestockSuggestion|null
     */
    public function generarParaProducto(Producto $producto): ?RestockSuggestion
    {
        $producto->loadMissing(['inventario']);

        if ($producto->inventario === null) {
            return null;
        }

        $calculo = $this->calcularSugerencia($producto, $producto->inventario);

        if ($calculo['stock_sugerido'] <= 0 && $calculo['urgencia'] === 'baja') {
            return null;
        }

        return RestockSuggestion::query()->updateOrCreate(
            [
                'producto_id' => $producto->id,
                'vendor_id' => $producto->vendor_id,
                'aceptada' => false,
            ],
            [
                'stock_actual' => $producto->inventario->stock_actual,
                'stock_sugerido' => $calculo['stock_sugerido'],
                'dias_hasta_quiebre' => $calculo['dias_hasta_quiebre'],
                'urgencia' => $calculo['urgencia'],
                'modelo_version_id' => $calculo['modelo_version_id'],
            ]
        );
    }

    /**
     * Acepta una sugerencia y actualiza stock fisico recibido.
     *
     * @param RestockSuggestion $suggestion
     * @param array<string, mixed> $data
     * @param User $user
     * @return RestockSuggestion
     *
     * @throws TransaccionFallidaException
     */
    public function accept(RestockSuggestion $suggestion, array $data, User $user): RestockSuggestion
    {
        try {
            return DB::transaction(function () use ($suggestion, $data, $user): RestockSuggestion {
                $suggestion->loadMissing('producto.inventario');
                $cantidad = max(1, (int) ($data['cantidad_recibida'] ?? $suggestion->stock_sugerido));
                $inventario = $this->lockedInventario($suggestion->producto);
                $oldValues = $inventario->only(['stock_actual', 'stock_minimo', 'stock_maximo']);

                $inventario->update([
                    'stock_actual' => $inventario->stock_actual + $cantidad,
                    'ultima_actualizacion' => now(),
                ]);

                $suggestion->update([
                    'aceptada' => true,
                    'aceptada_at' => now(),
                ]);

                $this->audit($inventario, $user, $oldValues, $inventario->fresh()->toArray(), $suggestion->id);

                return $suggestion->refresh();
            });
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible aceptar la sugerencia de reabastecimiento.', previous: $exception);
        }
    }

    /**
     * Calcula cantidad sugerida usando prediccion ML o regla conservadora.
     *
     * @param Producto $producto
     * @param Inventario $inventario
     * @return array<string, mixed>
     */
    private function calcularSugerencia(Producto $producto, Inventario $inventario): array
    {
        $prediccion = SalesPrediction::query()
            ->where('producto_id', $producto->id)
            ->whereIn('horizonte_dias', [7, 14, 30])
            ->latest('fecha_prediccion')
            ->first();

        $demandaPeriodo = $prediccion === null
            ? max(1, $inventario->stock_minimo)
            : (float) $prediccion->valor_predicho;
        $horizonte = $prediccion?->horizonte_dias ?? 7;
        $demandaDiaria = max(0.1, $demandaPeriodo / max(1, $horizonte));
        $stockDisponible = max(0, $inventario->stock_actual - $inventario->stock_reservado);
        $diasHastaQuiebre = (int) floor($stockDisponible / $demandaDiaria);
        $stockObjetivo = (int) ceil(($demandaDiaria * 14) + $inventario->stock_minimo);
        $stockSugerido = max(0, $stockObjetivo - $stockDisponible);

        return [
            'stock_sugerido' => $stockSugerido,
            'dias_hasta_quiebre' => $diasHastaQuiebre,
            'urgencia' => $this->urgencia($diasHastaQuiebre, $stockDisponible, $inventario->stock_minimo),
            'modelo_version_id' => $prediccion?->modelo_version_id,
        ];
    }

    /**
     * Define urgencia segun dias de cobertura.
     *
     * @param int $diasHastaQuiebre
     * @param int $stockDisponible
     * @param int $stockMinimo
     * @return string
     */
    private function urgencia(int $diasHastaQuiebre, int $stockDisponible, int $stockMinimo): string
    {
        if ($stockDisponible <= 0 || $diasHastaQuiebre <= 1) {
            return 'critica';
        }

        if ($diasHastaQuiebre <= 3 || $stockDisponible <= $stockMinimo) {
            return 'alta';
        }

        if ($diasHastaQuiebre <= 7) {
            return 'media';
        }

        return 'baja';
    }

    /**
     * Obtiene inventario bloqueado para aceptar reabastecimiento.
     *
     * @param Producto $producto
     * @return Inventario
     */
    private function lockedInventario(Producto $producto): Inventario
    {
        return Inventario::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Registra auditoria append-only del reabastecimiento.
     *
     * @param Inventario $inventario
     * @param User $user
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param int $suggestionId
     * @return void
     */
    private function audit(
        Inventario $inventario,
        User $user,
        array $oldValues,
        array $newValues,
        int $suggestionId
    ): void {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'event' => 'inventario.reabasto_aceptado',
            'auditable_type' => Inventario::class,
            'auditable_id' => $inventario->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'producto_id' => $inventario->producto_id,
                'restock_suggestion_id' => $suggestionId,
            ],
        ]);
    }
}
