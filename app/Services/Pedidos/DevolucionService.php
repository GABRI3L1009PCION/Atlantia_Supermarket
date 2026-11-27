<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Events\DevolucionAprobada;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Devolucion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Inventario\StockService;
use App\Services\Pagos\PasarelaPagoService;
use App\ValueObjects\Dinero;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de devoluciones y reembolsos.
 */
class DevolucionService
{
    /**
     * Crea una instancia del servicio.
     */
    public function __construct(
        private readonly StockService $stockService,
        private readonly PasarelaPagoService $pasarelaPagoService
    ) {}

    /**
     * Lista devoluciones pendientes para administracion.
     */
    public function pendientes(): LengthAwarePaginator
    {
        return Devolucion::query()
            ->with(['pedido.cliente', 'pedido.items.producto', 'user'])
            ->pendientes()
            ->latest()
            ->paginate(20);
    }

    /**
     * Devoluciones ya resueltas (aprobadas o rechazadas), las mas recientes primero.
     */
    public function resueltas(): LengthAwarePaginator
    {
        return Devolucion::query()
            ->with(['pedido', 'user'])
            ->whereIn('estado', ['aprobada', 'rechazada'])
            ->latest('resuelta_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Indicadores del panel: pendientes y lo resuelto en el mes en curso.
     *
     * @return array<string, int|float>
     */
    public function resumen(): array
    {
        $delMes = Devolucion::query()
            ->whereIn('estado', ['aprobada', 'rechazada'])
            ->where('resuelta_at', '>=', now()->startOfMonth())
            ->get(['estado', 'monto_reembolso']);

        return [
            'pendientes' => Devolucion::query()->pendientes()->count(),
            'aprobadas_mes' => $delMes->where('estado', 'aprobada')->count(),
            'rechazadas_mes' => $delMes->where('estado', 'rechazada')->count(),
            'reembolsado_mes' => round((float) $delMes->where('estado', 'aprobada')->sum('monto_reembolso'), 2),
            'resueltas' => Devolucion::query()->whereIn('estado', ['aprobada', 'rechazada'])->count(),
        ];
    }

    /**
     * Crea una solicitud de devolucion del cliente.
     *
     * @param  array<string, mixed>  $data
     */
    public function solicitar(Pedido $pedido, User $cliente, array $data): Devolucion
    {
        return DB::transaction(function () use ($pedido, $cliente, $data): Devolucion {
            $pedido = Pedido::query()
                ->with('devoluciones')
                ->whereKey($pedido->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pedido->devoluciones()->whereIn('estado', ['solicitada', 'aprobada'])->exists()) {
                throw new TransaccionFallidaException('Este pedido ya tiene una devolucion en revision.');
            }

            $fotoPath = null;

            if (($data['foto_evidencia'] ?? null) !== null) {
                $fotoPath = $data['foto_evidencia']->store('devoluciones', 'public');
            }

            $devolucion = Devolucion::query()->create([
                'pedido_id' => $pedido->id,
                'user_id' => $cliente->id,
                'motivo' => $data['motivo'],
                'descripcion' => $data['descripcion'],
                'foto_evidencia' => $fotoPath,
                'monto_reembolso' => 0,
            ]);

            DB::afterCommit(fn () => $this->avisarTiendas($devolucion, 'solicitada'));

            return $devolucion;
        });
    }

    /**
     * Pedidos de tienda que cubre una devolución: el mismo pedido si es de una tienda, o sus hijos.
     *
     * @return \Illuminate\Support\Collection<int, Pedido>
     */
    public static function pedidosDeTienda(Pedido $pedido): \Illuminate\Support\Collection
    {
        return $pedido->vendor_id !== null
            ? collect([$pedido])
            : Pedido::query()->where('pedido_padre_id', $pedido->id)->get();
    }

    /**
     * Devoluciones que tocan pedidos de una tienda.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Devolucion>
     */
    public static function paraTienda(int $vendorId): \Illuminate\Database\Eloquent\Builder
    {
        return Devolucion::query()->whereHas('pedido', fn ($q) => $q->where(fn ($w) => $w->where('vendor_id', $vendorId)
            ->orWhereHas('pedidosHijos', fn ($h) => $h->where('vendor_id', $vendorId))));
    }

    /**
     * Respuesta del vendedor: si está de acuerdo y por qué. Solo mientras Atlantia no decide.
     */
    public function responderVendedor(Devolucion $devolucion, bool $deAcuerdo, ?string $comentario): Devolucion
    {
        if ($devolucion->estado !== 'solicitada') {
            throw ValidationException::withMessages(['respuesta' => 'Atlantia ya decidió esta devolución.']);
        }

        $devolucion->update([
            'vendedor_de_acuerdo' => $deAcuerdo,
            'respuesta_vendedor' => filled($comentario) ? trim((string) $comentario) : null,
            'vendedor_respondio_at' => now(),
        ]);

        return $devolucion->refresh();
    }

    private function avisarTiendas(Devolucion $devolucion, string $momento): void
    {
        try {
            $devolucion->loadMissing('pedido');
            $usuarios = self::pedidosDeTienda($devolucion->pedido)
                ->load('vendor.user')
                ->map(fn (Pedido $p) => $p->vendor?->user)
                ->filter()
                ->unique('id');

            \Illuminate\Support\Facades\Notification::send($usuarios, new \App\Notifications\DevolucionVendedorNotification($devolucion, $momento));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Resuelve una devolucion desde administracion.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolver(Devolucion $devolucion, User $admin, array $data): Devolucion
    {
        return DB::transaction(function () use ($devolucion, $admin, $data): Devolucion {
            $devolucion = Devolucion::query()
                ->with(['pedido.items.producto.inventario', 'pedido.payments'])
                ->whereKey($devolucion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($devolucion->estado !== 'solicitada') {
                throw new TransaccionFallidaException('La devolucion ya fue resuelta anteriormente.');
            }

            if ($data['decision'] === 'rechazada') {
                $devolucion->update([
                    'estado' => 'rechazada',
                    'notas_admin' => $data['notas_admin'] ?? null,
                    'resuelta_por' => $admin->id,
                    'resuelta_at' => now(),
                ]);
                DB::afterCommit(fn () => $this->avisarTiendas($devolucion, 'rechazada'));

                return $devolucion->refresh();
            }

            $monto = round((float) $data['monto_reembolso'], 2);
            $pedido = Pedido::query()->whereKey($devolucion->pedido_id)->lockForUpdate()->firstOrFail();
            $payment = $pedido->payments()->latest('id')->lockForUpdate()->first();

            if ($pedido->devoluciones()->where('id', '!=', $devolucion->id)->where('estado', 'aprobada')->exists()) {
                throw ValidationException::withMessages(['monto_reembolso' => 'Este pedido ya tiene una devolucion aprobada.']);
            }

            if ($payment === null || ! in_array($payment->estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
                throw ValidationException::withMessages(['monto_reembolso' => 'El pedido no tiene un pago acreditado disponible para reembolso.']);
            }

            if (! is_finite($monto) || Dinero::from($monto)->toCents() <= 0 || Dinero::from($monto)->toCents() > Dinero::from($pedido->total)->toCents()) {
                throw ValidationException::withMessages(['monto_reembolso' => 'El reembolso no puede superar el total del pedido.']);
            }

            $this->pasarelaPagoService->reembolsar($payment, $monto);

            $this->stockService->restoreForPedido($pedido);
            $pedido->update([
                'estado' => EstadoPedido::Cancelado->value,
                'estado_pago' => EstadoPago::Reembolsado->value,
                'cancelado_at' => now(),
            ]);
            $pedido->estados()->create([
                'estado' => EstadoPedido::Cancelado->value,
                'notas' => 'Devolucion aprobada por administracion.',
                'usuario_id' => $admin->id,
            ]);

            /* Los pedidos de cada tienda también se cancelan: esa venta ya no se le paga al vendedor. */
            foreach (self::pedidosDeTienda($pedido) as $pedidoTienda) {
                if ($pedidoTienda->id !== $pedido->id) {
                    $pedidoTienda->update(['estado' => EstadoPedido::Cancelado->value, 'estado_pago' => EstadoPago::Reembolsado->value, 'cancelado_at' => now()]);
                    $pedidoTienda->estados()->create(['estado' => EstadoPedido::Cancelado->value, 'notas' => 'Devolucion aprobada por administracion.', 'usuario_id' => $admin->id]);
                }
                \App\Models\PaymentSplit::query()
                    ->where('pedido_id', $pedidoTienda->id)
                    ->whereNull('liquidacion_id')
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'reversado']);
            }

            $devolucion->update([
                'estado' => 'aprobada',
                'monto_reembolso' => $monto,
                'notas_admin' => $data['notas_admin'] ?? null,
                'resuelta_por' => $admin->id,
                'resuelta_at' => now(),
            ]);

            DevolucionAprobada::dispatch($devolucion->fresh(['pedido.cliente', 'user']));
            DB::afterCommit(fn () => $this->avisarTiendas($devolucion, 'aprobada'));

            return $devolucion->refresh();
        });
    }
}
