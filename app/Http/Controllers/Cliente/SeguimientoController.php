<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Geolocalizacion\SeguimientoPedidoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de seguimiento de pedidos.
 */
class SeguimientoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly SeguimientoPedidoService $seguimientoPedidoService) {}

    /**
     * Muestra mapa y estado de seguimiento.
     */
    public function show(Request $request, Pedido $pedido): View
    {
        $this->authorize('track', $pedido);
        $entrega = $this->entregaSeguida($request, $pedido);

        return view('cliente.seguimiento.show', [
            'seguimiento' => $this->seguimientoPedidoService->detail($entrega),
            'liveUrl' => route('cliente.pedidos.seguimiento.live', [$pedido, 'entrega' => $entrega->uuid]),
            'detalleUrl' => route('cliente.pedidos.show', $pedido),
            ...$this->entregas($pedido, $entrega, fn (Pedido $hijo) => route('cliente.pedidos.seguimiento', [$pedido, 'entrega' => $hijo->uuid])),
        ]);
    }

    /**
     * Devuelve datos vivos para actualizar el mapa sin recargar la pagina.
     */
    public function live(Request $request, Pedido $pedido): JsonResponse
    {
        $this->authorize('track', $pedido);

        return $this->liveResponse($this->entregaSeguida($request, $pedido));
    }

    /**
     * Seguimiento para invitados. El enlace firmado de WhatsApp habilita este navegador.
     */
    public function guestShow(Request $request, Pedido $pedido): View
    {
        $this->autorizarInvitado($request, $pedido);
        $entrega = $this->entregaSeguida($request, $pedido);

        return view('cliente.seguimiento.show', [
            'seguimiento' => $this->seguimientoPedidoService->detail($entrega),
            'liveUrl' => route('cliente.pedidos.guest-seguimiento.live', [$pedido, 'entrega' => $entrega->uuid]),
            'detalleUrl' => route('cliente.pedidos.guest-show', $pedido),
            ...$this->entregas($pedido, $entrega, fn (Pedido $hijo) => route('cliente.pedidos.guest-seguimiento', [$pedido, 'entrega' => $hijo->uuid])),
        ]);
    }

    /**
     * Datos vivos del seguimiento para invitados.
     */
    public function guestLive(Request $request, Pedido $pedido): JsonResponse
    {
        $this->autorizarInvitado($request, $pedido);

        return $this->liveResponse($this->entregaSeguida($request, $pedido));
    }

    /**
     * Estilo DiDi: un pedido de varias tiendas tiene una entrega (y un repartidor) por tienda.
     * Se sigue la entrega elegida con ?entrega=uuid, o la primera.
     */
    private function entregaSeguida(Request $request, Pedido $pedido): Pedido
    {
        $hijos = $pedido->pedidosHijos()->with('vendor')->orderBy('id')->get();

        if ($hijos->isEmpty()) {
            return $pedido;
        }

        return $hijos->firstWhere('uuid', (string) $request->query('entrega')) ?? $hijos->first();
    }

    /**
     * @return array{entregas: array<int, array{nombre: string, url: string, actual: bool, estado: string}>}
     */
    private function entregas(Pedido $pedido, Pedido $actual, \Closure $url): array
    {
        $hijos = $pedido->pedidosHijos()->with('vendor')->orderBy('id')->get();

        return ['entregas' => $hijos->count() < 2 ? [] : $hijos->map(fn (Pedido $hijo): array => [
            'nombre' => $hijo->vendor?->business_name ?? 'Atlantia Store',
            'url' => $url($hijo),
            'actual' => $hijo->is($actual),
            'estado' => $hijo->estado instanceof \BackedEnum ? $hijo->estado->value : (string) $hijo->estado,
        ])->values()->all()];
    }

    private function autorizarInvitado(Request $request, Pedido $pedido): void
    {
        if ($request->hasValidSignature()) {
            $request->session()->push('guest_order_uuids', $pedido->uuid);
        }

        abort_unless(in_array($pedido->uuid, $request->session()->get('guest_order_uuids', []), true), 403);
    }

    private function liveResponse(Pedido $pedido): JsonResponse
    {
        $seguimiento = $this->seguimientoPedidoService->detail($pedido);

        return response()->json([
            'message' => 'Seguimiento actualizado.',
            'data' => [
                'pedido' => [
                    'uuid' => $pedido->uuid,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $seguimiento['estado'],
                ],
                'destino' => $seguimiento['destino'],
                'repartidor' => $seguimiento['repartidor'],
                'eta_minutos' => $seguimiento['eta_minutos'],
                'actualizado_at' => $seguimiento['actualizado_at'],
            ],
        ]);
    }
}
