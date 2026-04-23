<?php

namespace App\Services\Antifraude;

use App\Enums\EstadoPago;
use App\Enums\MetodoPago;
use App\Models\Ml\FraudAlert;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de deteccion de patrones sospechosos en pedidos.
 */
class DeteccionPatronesService
{
    /**
     * Lista alertas antifraude con filtros administrativos.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return FraudAlert::query()
            ->with(['pedido', 'user', 'revisadaPor'])
            ->when($filters['tipo'] ?? null, fn ($query, $tipo) => $query->where('tipo', $tipo))
            ->when(isset($filters['revisada']), fn ($query) => $query->where('revisada', (bool) $filters['revisada']))
            ->when(isset($filters['resuelta']), fn ($query) => $query->where('resuelta', (bool) $filters['resuelta']))
            ->when($filters['riesgo_min'] ?? null, fn ($query, $score) => $query->where('score_riesgo', '>=', $score))
            ->latest()
            ->paginate(50);
    }

    /**
     * Carga detalle completo de una alerta.
     */
    public function detail(FraudAlert $fraudAlert): FraudAlert
    {
        return $fraudAlert->load(['pedido.cliente', 'pedido.vendor', 'pedido.payments', 'user', 'revisadaPor', 'modeloVersion']);
    }

    /**
     * Resume indicadores para el panel antifraude.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $alertas = FraudAlert::query()->get();

        return [
            'pendientes' => $alertas->where('revisada', false)->count(),
            'resueltas' => $alertas->where('resuelta', true)->count(),
            'alto_riesgo' => $alertas->filter(fn (FraudAlert $alert) => (float) $alert->score_riesgo >= 0.8)->count(),
            'tipos' => $alertas->groupBy('tipo')->map->count(),
        ];
    }

    /**
     * Evalua un pedido y genera alerta si supera el umbral.
     *
     * @param Pedido $pedido
     * @return FraudAlert|null
     */
    public function evaluarPedido(Pedido $pedido): ?FraudAlert
    {
        $pedido->loadMissing(['cliente', 'items', 'payments', 'direccion']);
        $detalle = $this->calcularRiesgoPedido($pedido);

        if ($detalle['score_riesgo'] < 0.65) {
            return null;
        }

        return FraudAlert::query()->create([
            'uuid' => (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'user_id' => $pedido->cliente_id,
            'tipo' => $detalle['tipo'],
            'score_riesgo' => $detalle['score_riesgo'],
            'detalle' => $detalle,
            'revisada' => false,
            'resuelta' => false,
        ]);
    }

    /**
     * Resuelve una alerta antifraude.
     *
     * @param FraudAlert $fraudAlert
     * @param array<string, mixed> $data
     * @param User $user
     * @return FraudAlert
     */
    public function resolve(FraudAlert $fraudAlert, array $data, User $user): FraudAlert
    {
        return DB::transaction(function () use ($fraudAlert, $data, $user): FraudAlert {
            $fraudAlert->update([
                'revisada' => true,
                'resuelta' => (bool) ($data['resuelta'] ?? true),
                'revisada_por' => $user->id,
                'revisada_at' => now(),
                'detalle' => array_merge($fraudAlert->detalle ?? [], [
                    'resolucion' => [
                        'accion' => $data['accion'] ?? 'revision_manual',
                        'notas' => $data['notas'] ?? null,
                    ],
                ]),
            ]);

            return $fraudAlert->refresh();
        });
    }

    /**
     * Resuelve varias alertas antifraude por lote.
     *
     * @param array<int, string> $uuids
     */
    public function resolveBatch(array $uuids, string $accion, ?string $notas, User $user): int
    {
        return DB::transaction(function () use ($uuids, $accion, $notas, $user): int {
            $alertas = FraudAlert::query()
                ->whereIn('uuid', $uuids)
                ->where('resuelta', false)
                ->get();

            foreach ($alertas as $alerta) {
                $this->resolve($alerta, [
                    'resuelta' => true,
                    'accion' => $accion,
                    'notas' => $notas,
                ], $user);
            }

            return $alertas->count();
        });
    }

    /**
     * Calcula score de riesgo por reglas conservadoras.
     *
     * @param Pedido $pedido
     * @return array<string, mixed>
     */
    private function calcularRiesgoPedido(Pedido $pedido): array
    {
        $score = 0.0;
        $razones = [];

        if ((float) $pedido->total >= 2500.00) {
            $score += 0.35;
            $razones[] = 'monto_alto';
        }

        if ($pedido->metodo_pago === MetodoPago::Tarjeta && $pedido->estado_pago !== EstadoPago::Pagado) {
            $score += 0.25;
            $razones[] = 'tarjeta_no_confirmada';
        }

        $pedidosRecientes = Pedido::query()
            ->where('cliente_id', $pedido->cliente_id)
            ->where('created_at', '>=', now()->subHours(2))
            ->count();

        if ($pedidosRecientes >= 4) {
            $score += 0.3;
            $razones[] = 'frecuencia_alta';
        }

        if ($pedido->direccion?->municipio === 'Livingston' && (float) $pedido->total >= 1000.00) {
            $score += 0.15;
            $razones[] = 'zona_remota_monto_alto';
        }

        return [
            'tipo' => $razones[0] ?? 'riesgo_operativo',
            'score_riesgo' => min(1.0, round($score, 6)),
            'razones' => $razones,
            'pedido_total' => (float) $pedido->total,
            'metodo_pago' => $pedido->metodoPagoValor(),
            'pedidos_recientes_cliente' => $pedidosRecientes,
        ];
    }
}
