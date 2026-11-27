<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Exceptions\TransaccionFallidaException;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\User;
use App\Notifications\PedidoCanceladoNotification;
use App\Notifications\PedidoCanceladoPorClienteNotification;
use App\Services\Inventario\StockService;
use App\Services\Pagos\PasarelaPagoService;
use App\ValueObjects\Dinero;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Todo lo que pasa cuando se cancela un pedido, lo cancele quien lo cancele (cliente, tienda o Atlantia):
 * vuelve el inventario, se anula lo que se iba a pagar a la tienda, se devuelve el saldo y el cupón
 * y, si el cliente pagó con tarjeta, se pide la devolución a la pasarela.
 */
class CancelacionService
{
    /** Plazo que se le comunica al cliente para ver la devolución en su tarjeta. */
    public const PLAZO_DEVOLUCION = 'de 30 minutos a 1 hora';

    public function __construct(
        private readonly EstadoPedidoService $estadoPedidoService,
        private readonly StockService $stockService
    ) {}

    public function cancelar(Pedido $pedido, User $usuario, string $motivo): Pedido
    {
        try {
            return DB::transaction(function () use ($pedido, $usuario, $motivo): Pedido {
                if (in_array($pedido->estado, [EstadoPedido::Entregado, EstadoPedido::Cancelado], true)) {
                    throw new TransaccionFallidaException('El pedido no puede cancelarse en su estado actual.');
                }

                $pedido = $this->estadoPedidoService->registrar($pedido, EstadoPedido::Cancelado, $motivo, $usuario);
                $this->alCancelarse($pedido, $usuario, 'atlantia');

                return $pedido->refresh();
            });
        } catch (TransaccionFallidaException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new TransaccionFallidaException('No fue posible cancelar el pedido.', previous: $exception);
        }
    }

    /**
     * Por qué el cliente no puede cancelar este pedido él mismo (null = sí puede).
     * Solo se cancela mientras ninguna tienda lo haya aceptado. Una transferencia ya
     * validada se devuelve a mano, así que ese caso pasa por soporte.
     */
    public static function motivoNoCancelable(Pedido $pedido): ?string
    {
        $pedido->loadMissing('pedidosHijos');
        $partes = $pedido->pedidosHijos->isNotEmpty() ? $pedido->pedidosHijos : collect([$pedido]);

        if ($pedido->estado === EstadoPedido::Cancelado) {
            return 'Este pedido ya está cancelado.';
        }
        if ($partes->contains(fn (Pedido $p): bool => $p->estado !== EstadoPedido::Pendiente)) {
            return 'La tienda ya aceptó tu pedido y lo está preparando. Si necesitas cancelarlo, escríbenos.';
        }

        $pago = $pedido->latestPayment()->first();
        if (self::metodo($pago) === 'transferencia' && in_array(self::estadoPago($pago), [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
            return 'Tu transferencia ya fue validada. Escríbenos para cancelar y devolverte el dinero.';
        }

        return null;
    }

    /**
     * El cliente cancela su pedido completo antes de que una tienda lo acepte.
     *
     * @return array{pedido: Pedido, reembolso: float}
     */
    public function cancelarPorCliente(Pedido $pedido, User $cliente, string $motivo): array
    {
        abort_unless((int) $pedido->cliente_id === (int) $cliente->id && $pedido->pedido_padre_id === null, 403);

        return DB::transaction(function () use ($pedido, $cliente, $motivo): array {
            $pedido = Pedido::query()->whereKey($pedido->id)->lockForUpdate()->firstOrFail();

            if (($razon = self::motivoNoCancelable($pedido)) !== null) {
                throw ValidationException::withMessages(['pedido' => $razon]);
            }

            $pedido = $this->estadoPedidoService->registrar($pedido, EstadoPedido::Cancelado, 'Cancelado por el cliente: '.$motivo, $cliente);
            $reembolso = $this->alCancelarse($pedido, $cliente, 'cliente');

            return ['pedido' => $pedido->refresh(), 'reembolso' => $reembolso];
        });
    }

    /**
     * Aplica los efectos de una cancelación. Se llama después de pasar un pedido (principal o de una tienda)
     * a "cancelado". Si es el principal, se cancelan también sus pedidos por tienda; si es el de una tienda,
     * solo esa parte (y si ya no queda ninguna, el pedido completo). Devuelve lo que se pidió reembolsar.
     *
     * @param  string  $origen  cliente, tienda o atlantia
     */
    public function alCancelarse(Pedido $pedido, ?User $usuario, string $origen): float
    {
        return DB::transaction(function () use ($pedido, $usuario, $origen): float {
            $padre = $pedido->pedido_padre_id !== null
                ? Pedido::query()->whereKey($pedido->pedido_padre_id)->lockForUpdate()->firstOrFail()
                : Pedido::query()->whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            $hijos = $padre->pedidosHijos()->with(['items.producto', 'vendor.user'])->get();
            $nota = $origen === 'tienda' ? 'Cancelado por la tienda.' : ($origen === 'cliente' ? 'Cancelado por el cliente.' : 'Cancelado por Atlantia.');

            /* Partes que se cancelan ahora (las que ya tenían su cancelación aplicada se saltan). */
            if ($pedido->id === $padre->id) {
                $partes = $hijos->isNotEmpty() ? $hijos : collect([$padre->load('items.producto')]);
                foreach ($hijos as $hijo) {
                    if ($hijo->estado !== EstadoPedido::Cancelado) {
                        $this->estadoPedidoService->registrar($hijo, EstadoPedido::Cancelado, $nota, $usuario);
                    }
                }
            } else {
                $partes = $hijos->where('id', $pedido->id);
            }
            $partes = $partes->filter(fn (Pedido $p): bool => $p->fresh()->cancelacion_aplicada_at === null)->values();

            if ($partes->isEmpty()) {
                return 0.0;
            }

            /* ¿Queda alguna tienda que sí vaya a entregar? */
            $quedanActivas = $hijos->isNotEmpty() && $hijos->contains(
                fn (Pedido $h): bool => ! $partes->contains('id', $h->id) && $h->fresh()->estado !== EstadoPedido::Cancelado
            );

            foreach ($partes as $parte) {
                $this->stockService->releaseForPedido($parte);
                $parte->forceFill(['cancelacion_aplicada_at' => now()])->save();
            }
            PaymentSplit::query()->whereIn('pedido_id', $partes->pluck('id'))->where('estado', 'pendiente')->update(['estado' => 'reversado']);

            if (! $quedanActivas) {
                /* Se cayó el pedido completo: el principal también queda cancelado (devuelve el saldo a favor). */
                if ($padre->fresh()->estado !== EstadoPedido::Cancelado) {
                    $this->estadoPedidoService->registrar($padre, EstadoPedido::Cancelado, $nota, $usuario);
                }
                $padre->forceFill(['cancelacion_aplicada_at' => now()])->save();
                $this->devolverCupon($padre);
            }

            $reembolso = $this->reembolsar($padre, $partes, ! $quedanActivas);

            $this->avisar($padre, $partes, $origen, $reembolso, ! $quedanActivas);

            return $reembolso;
        });
    }

    /**
     * Devolución a la tarjeta: el pedido completo, o solo los productos de la tienda que canceló
     * (el envío se mantiene porque las otras tiendas sí se entregan).
     *
     * @param  Collection<int, Pedido>  $partes
     */
    private function reembolsar(Pedido $padre, Collection $partes, bool $todo): float
    {
        $pago = $padre->payments()->latest('id')->first();
        if ($pago === null) {
            return 0.0;
        }

        $estado = self::estadoPago($pago);
        if (self::metodo($pago) !== 'tarjeta' || ! in_array($estado, [EstadoPago::Aprobado, EstadoPago::Pagado], true)) {
            /* Un pago que no llegó a cobrarse ya no se espera. */
            if ($todo && in_array($estado, [EstadoPago::Pendiente, EstadoPago::Validando], true)) {
                $pago->update(['estado' => EstadoPago::Anulado->value]);
            }

            return 0.0;
        }

        $restante = Dinero::from($pago->monto)->toFloat() - (float) (($pago->pasarela_payload ?? [])['refund_total'] ?? 0);
        $monto = $todo
            ? $restante
            : min($restante, (float) $partes->sum(fn (Pedido $p): float => max(0, (float) $p->total - (float) $p->envio)));
        $monto = round($monto, 2);

        if ($monto <= 0) {
            return 0.0;
        }

        app(PasarelaPagoService::class)->reembolsar($pago, $monto, porPartes: true);

        return $monto;
    }

    private function devolverCupon(Pedido $padre): void
    {
        CuponUso::query()->where('pedido_id', $padre->id)->get()->each(function (CuponUso $uso): void {
            Cupon::query()->whereKey($uso->cupon_id)->where('usos_actuales', '>', 0)->decrement('usos_actuales');
            $uso->delete();
        });
    }

    /**
     * @param  Collection<int, Pedido>  $partes
     */
    private function avisar(Pedido $padre, Collection $partes, string $origen, float $reembolso, bool $todo): void
    {
        DB::afterCommit(function () use ($padre, $partes, $origen, $reembolso, $todo): void {
            try {
                /* Al cliente: siempre que no haya sido él quien canceló. */
                if ($origen !== 'cliente' && $padre->cliente !== null) {
                    $padre->cliente->notify(new PedidoCanceladoNotification($padre, $partes->pluck('vendor.business_name')->filter()->values()->all(), $reembolso, $todo, $origen));
                }
                /* A las tiendas: cuando el que canceló no fueron ellas. */
                if ($origen !== 'tienda') {
                    foreach ($partes as $parte) {
                        $parte->vendor?->user?->notify(new PedidoCanceladoPorClienteNotification($parte, $origen));
                    }
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    private static function metodo(mixed $pago): string
    {
        return (string) ($pago?->metodo?->value ?? $pago?->metodo);
    }

    private static function estadoPago(mixed $pago): ?EstadoPago
    {
        return $pago?->estado instanceof EstadoPago ? $pago->estado : EstadoPago::tryFrom((string) $pago?->estado);
    }
}
