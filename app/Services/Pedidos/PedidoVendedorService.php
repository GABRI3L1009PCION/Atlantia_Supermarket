<?php

namespace App\Services\Pedidos;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Notificaciones\NotificadorPedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de pedidos recibidos por vendedor.
 */
class PedidoVendedorService
{
    /**
     * Pestañas de la lista: clave => [texto, estados que agrupa].
     *
     * @var array<string, array{0: string, 1: array<int, string>}>
     */
    public const PESTANAS = [
        'por_confirmar' => ['Por confirmar', ['pendiente', 'en_revision']],
        'confirmado' => ['Confirmados', ['confirmado']],
        'preparando' => ['En preparación', ['preparando']],
        'listo' => ['Listos', ['listo_para_entrega']],
        'en_ruta' => ['En camino', ['en_ruta']],
        'entregado' => ['Entregados', ['entregado']],
        'cancelado' => ['Cancelados', ['cancelado', 'rechazado']],
    ];

    /**
     * Pagina pedidos del vendedor autenticado.
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        $estados = isset(self::PESTANAS[$filters['estado'] ?? '']) ? self::PESTANAS[$filters['estado']][1] : null;

        return Pedido::query()
            ->with(['cliente', 'items', 'payments'])
            ->where('vendor_id', $user->vendor?->id)
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('numero_pedido', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
                });
            })
            ->when($estados, fn ($query, array $estados) => $query->whereIn('estado', $estados))
            ->when($filters['desde'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['hasta'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(min(50, max(5, (int) ($filters['per_page'] ?? 10))))
            ->withQueryString();
    }

    /**
     * Datos reales para el tablero de pedidos del vendedor.
     *
     * @return array{conteos: array<string, int>, total: int, hoy: array{pedidos: int, ventas: float}, tiempo_preparacion: ?int, cumplimiento: ?int, muestra: int}
     */
    public function dashboard(User $user): array
    {
        $vendorId = $user->vendor?->id;
        $porEstado = Pedido::query()
            ->where('vendor_id', $vendorId)
            ->selectRaw('estado, count(*) as n')
            ->groupBy('estado')
            ->pluck('n', 'estado');

        $conteos = [];
        foreach (self::PESTANAS as $clave => [, $estados]) {
            $conteos[$clave] = (int) collect($estados)->sum(fn (string $estado): int => (int) ($porEstado[$estado] ?? 0));
        }

        $hoy = Pedido::query()
            ->where('vendor_id', $vendorId)
            ->whereDate('created_at', today())
            ->whereNotIn('estado', ['cancelado', 'rechazado'])
            ->get(['total', 'envio', 'propina']);

        /* Últimos 30 días: minutos desde que entra el pedido hasta que queda listo. */
        $desde = now()->subDays(30);
        $tiempos = DB::table('pedidos')
            ->join('pedido_estados', 'pedido_estados.pedido_id', '=', 'pedidos.id')
            ->where('pedidos.vendor_id', $vendorId)
            ->where('pedidos.created_at', '>=', $desde)
            ->where('pedido_estados.estado', 'listo_para_entrega')
            ->groupBy('pedidos.id', 'pedidos.created_at')
            ->selectRaw('pedidos.created_at as creado, min(pedido_estados.created_at) as listo')
            ->get()
            ->map(fn (object $fila): float => max(0, Carbon::parse($fila->creado)->diffInMinutes(Carbon::parse($fila->listo))));

        $cerrados = Pedido::query()
            ->where('vendor_id', $vendorId)
            ->where('created_at', '>=', $desde)
            ->whereIn('estado', ['entregado', 'cancelado', 'rechazado'])
            ->selectRaw("sum(case when estado = 'entregado' then 1 else 0 end) as entregados, count(*) as total")
            ->first();

        return [
            'conteos' => $conteos,
            'total' => (int) $porEstado->sum(),
            'hoy' => [
                'pedidos' => $hoy->count(),
                'ventas' => round((float) $hoy->sum(fn (Pedido $p): float => (float) $p->total - (float) $p->envio - (float) $p->propina), 2),
            ],
            'tiempo_preparacion' => $tiempos->isEmpty() ? null : (int) round($tiempos->avg()),
            'cumplimiento' => (int) ($cerrados->total ?? 0) > 0 ? (int) round(((int) $cerrados->entregados / (int) $cerrados->total) * 100) : null,
            'muestra' => (int) ($cerrados->total ?? 0),
        ];
    }

    /**
     * Estados a los que el vendedor puede mover este pedido (valor => texto del botón).
     * El reparto a domicilio lo cierra el repartidor; el vendedor solo entrega lo que se recoge en tienda.
     *
     * @return array<string, string>
     */
    public static function siguientesEstados(Pedido $pedido): array
    {
        $actual = EstadoPedido::tryFrom($pedido->estadoValor());
        if ($actual === null) {
            return [];
        }

        $opciones = [
            'confirmado' => 'Confirmar pedido',
            'preparando' => 'Empezar a preparar',
            'listo_para_entrega' => $pedido->tipo_entrega === 'recoger' ? 'Listo para recoger' : 'Listo para el repartidor',
            'cancelado' => 'Cancelar pedido',
        ];

        $permitidos = array_filter(
            $opciones,
            fn (string $texto, string $estado): bool => $actual->puedeTransicionarA(EstadoPedido::from($estado)),
            ARRAY_FILTER_USE_BOTH,
        );

        /* Empezar a preparar un pedido nuevo también lo acepta: no obliga a confirmar primero. */
        if (in_array($actual, [EstadoPedido::Pendiente, EstadoPedido::EnRevision], true)) {
            $permitidos = array_slice($permitidos, 0, 1, true) + ['preparando' => $opciones['preparando']] + $permitidos;
        }

        if ($pedido->tipo_entrega === 'recoger' && $actual === EstadoPedido::ListoParaEntrega) {
            $permitidos = ['entregado' => 'Entregado al cliente'] + $permitidos;
        }

        return $permitidos;
    }

    /**
     * Detalle de pedido recibido.
     */
    public function detail(Pedido $pedido): Pedido
    {
        return $pedido->load(['cliente', 'direccion', 'items.producto', 'estados.usuario', 'payments']);
    }

    /**
     * Actualiza estado del pedido del vendedor.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEstado(Pedido $pedido, array $data, User $user): Pedido
    {
        $pedidoConPago = $pedido->pedidoPadre ?? $pedido;

        /* Mientras el checkout siga abierto, el vencimiento automatico cancela y libera stock. */
        if (CheckoutService::esperaPagoEnLinea($pedidoConPago->latestPayment()->first())) {
            throw ValidationException::withMessages([
                'estado' => 'El cliente aun no completa el pago con tarjeta en linea. Espera la confirmacion del pago.',
            ]);
        }

        if (! array_key_exists($data['estado'], self::siguientesEstados($pedido))) {
            throw ValidationException::withMessages([
                'estado' => 'Este pedido no puede pasar a ese estado ahora. Actualiza la página e inténtalo de nuevo.',
            ]);
        }

        DB::transaction(function () use ($pedido, $data, $user): void {
            $pedido->update(['estado' => $data['estado']]);
            $pedido->estados()->create([
                'estado' => $data['estado'],
                'notas' => $data['notas'] ?? null,
                'usuario_id' => $user->id,
            ]);

            /* Cancelar es automático: vuelve el inventario, se anula el pago a la tienda y se devuelve la tarjeta. */
            if ($pedido->estado === EstadoPedido::Cancelado) {
                app(CancelacionService::class)->alCancelarse($pedido, $user, 'tienda');
            }
        });

        if ($pedido->estado === EstadoPedido::ListoParaEntrega) {
            app(NotificadorPedidoService::class)->pedidoListoParaRecoger($pedido);
        }

        return $pedido->refresh();
    }
}
