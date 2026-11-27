<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\Pedidos\ComprobanteCompraPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Descarga del comprobante de compra (no fiscal) de la parte de una tienda en un pedido.
 * Lo pueden abrir el cliente, la tienda, Atlantia y el invitado que hizo el pedido en este navegador.
 */
class ComprobanteCompraController extends Controller
{
    public function __invoke(Request $request, Pedido $pedido, ComprobanteCompraPdf $comprobante): Response
    {
        $padre = $pedido->pedido_padre_id ? $pedido->pedidoPadre : $pedido;

        if ($request->user() !== null) {
            $this->authorize('view', $pedido);
        } else {
            abort_unless(in_array($padre?->uuid, (array) $request->session()->get('guest_order_uuids', []), true), 403);
        }

        $nombre = 'comprobante-'.$padre?->numero_pedido.($pedido->vendor?->slug ? '-'.$pedido->vendor->slug : '').'.pdf';

        return response($comprobante->generar($pedido), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('descargar') ? 'attachment' : 'inline').'; filename="'.$nombre.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
