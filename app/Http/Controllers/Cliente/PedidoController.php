<?php

namespace App\Http\Controllers\Cliente;

use App\Exceptions\PagoRechazadoException;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoRecurrente;
use App\Services\Pedidos\CancelacionService;
use App\Services\Pedidos\PedidoClienteService;
use App\Services\Pedidos\RepetirPedidoService;
use App\Services\Repartidores\CalificacionRepartidorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador de pedidos del cliente.
 */
class PedidoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly PedidoClienteService $pedidoClienteService) {}

    /**
     * Muestra el historial de pedidos. Si el usuario es invitado,
     * se presenta una pantalla publica de acceso restringido.
     */
    public function index(Request $request): View
    {
        if ($request->user() === null) {
            return view('cliente.pedidos.index', [
                'pedidos' => collect(),
                'summary' => [
                    'total' => 0,
                    'active' => 0,
                    'closed' => 0,
                    'cancelled' => 0,
                ],
            ]);
        }

        $this->authorize('viewOwnOrders', Pedido::class);

        return view('cliente.pedidos.index', [
            'pedidos' => collect(),
            'summary' => [],
        ]);
    }

    /**
     * Muestra el detalle de un pedido.
     */
    public function show(Pedido $pedido): View
    {
        $this->authorize('view', $pedido);

        return view('cliente.pedidos.show', ['pedido' => $this->pedidoClienteService->detail($pedido)]);
    }

    /**
     * El cliente cancela su pedido mientras ninguna tienda lo haya aceptado.
     */
    public function cancelar(Request $request, Pedido $pedido, CancelacionService $cancelacion): RedirectResponse
    {
        $this->authorize('cancel', $pedido);
        abort_if($pedido->pedido_padre_id !== null, 404);

        $data = $request->validate([
            'motivo' => ['required', Rule::in(array_keys(self::MOTIVOS_CANCELACION))],
        ], ['motivo.required' => 'Cuéntanos por qué lo cancelas.']);

        try {
            $resultado = $cancelacion->cancelarPorCliente($pedido, $request->user(), self::MOTIVOS_CANCELACION[$data['motivo']]);
        } catch (PagoRechazadoException $exception) {
            report($exception);

            return back()->with('error', 'No pudimos devolver el pago con tarjeta en este momento. Intenta de nuevo en unos minutos o escríbenos.');
        }

        return redirect()->route('cliente.pedidos.show', $pedido)->with('success', $resultado['reembolso'] > 0
            ? 'Cancelamos tu pedido. Te devolveremos Q'.number_format($resultado['reembolso'], 2).' a tu tarjeta en un plazo '.CancelacionService::PLAZO_DEVOLUCION.'.'
            : 'Cancelamos tu pedido.');
    }

    /**
     * El cliente califica al repartidor que le entregó el pedido.
     */
    public function calificarRepartidor(Request $request, Pedido $pedido, CalificacionRepartidorService $calificaciones): RedirectResponse
    {
        $this->authorize('view', $pedido);
        $data = $request->validate([
            'estrellas' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:300'],
        ], ['estrellas.required' => 'Elige de 1 a 5 estrellas.']);

        $calificaciones->calificar($pedido, $request->user(), (int) $data['estrellas'], $data['comentario'] ?? null);

        return back()->with('success', '¡Gracias! Tu calificación ayuda a que los mejores repartidores lleven más pedidos.');
    }

    /** Motivos que el cliente elige al cancelar (las tiendas los ven en el historial). */
    public const MOTIVOS_CANCELACION = [
        'me_equivoque' => 'Me equivoqué en el pedido',
        'tarda' => 'Tarda mucho en ser aceptado',
        'otro_lugar' => 'Lo compré en otro lugar',
        'ya_no' => 'Ya no lo necesito',
        'otro' => 'Otro motivo',
    ];

    /**
     * Muestra la confirmacion de un pedido creado como invitado en esta sesion.
     */
    public function guestShow(Request $request, Pedido $pedido): View
    {
        /* El enlace firmado (WhatsApp) da acceso al pedido en este navegador. */
        if ($request->hasValidSignature()) {
            $request->session()->push('guest_order_uuids', $pedido->uuid);
        }

        abort_unless(
            in_array($pedido->uuid, $request->session()->get('guest_order_uuids', []), true),
            403
        );

        return view('cliente.pedidos.show', [
            'pedido' => $this->pedidoClienteService->detail($pedido),
            'guestContactEmail' => $request->session()->get("guest_order_email.{$pedido->uuid}"),
        ]);
    }

    /**
     * Vuelve a poner en el carrito los productos de un pedido anterior.
     * Los productos sin existencia o ya no publicados se omiten y se avisan.
     */
    public function repetir(Request $request, Pedido $pedido, RepetirPedidoService $repetirPedido): RedirectResponse
    {
        abort_unless((int) $pedido->cliente_id === (int) $request->user()?->id, 403);

        $resultado = $repetirPedido->alCarrito($request, $pedido);

        return redirect()->route('cliente.carrito.index')
            ->with($resultado['agregados'] === 0 ? 'error' : 'success', RepetirPedidoService::mensaje($resultado));
    }

    /**
     * Programa o cancela "repetir este pedido cada semana".
     */
    public function recurrente(Request $request, Pedido $pedido): RedirectResponse
    {
        abort_unless((int) $pedido->cliente_id === (int) $request->user()?->id, 403);
        $data = $request->validate([
            'dia_semana' => ['nullable', 'integer', 'between:0,6'],
        ]);

        if (! isset($data['dia_semana'])) {
            PedidoRecurrente::query()->where('pedido_origen_id', $pedido->id)->update(['activo' => false]);

            return back()->with('success', 'Ya no te recordaremos este pedido.');
        }

        PedidoRecurrente::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'pedido_origen_id' => $pedido->id],
            ['uuid' => (string) Str::uuid(), 'dia_semana' => (int) $data['dia_semana'], 'activo' => true]
        );

        return back()->with('success', 'Listo: cada '.PedidoRecurrente::DIAS[(int) $data['dia_semana']].' por la mañana te dejamos este pedido listo en el carrito y te avisamos.');
    }

    /**
     * Enlace del aviso semanal: carga el pedido recurrente en el carrito.
     */
    public function cargarRecurrente(Request $request, PedidoRecurrente $recurrente, RepetirPedidoService $repetirPedido): RedirectResponse
    {
        abort_unless((int) $recurrente->user_id === (int) $request->user()?->id, 403);

        $resultado = $repetirPedido->alCarrito($request, $recurrente->pedidoOrigen);

        return redirect()->route('cliente.carrito.index')
            ->with($resultado['agregados'] === 0 ? 'error' : 'success', RepetirPedidoService::mensaje($resultado));
    }
}
