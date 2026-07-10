<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repartidor\RejectDeliveryOfferRequest;
use App\Models\DeliveryOffer;
use App\Services\Repartidores\DeliveryOfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de ofertas de entregas.
 */
class OfertaController extends Controller
{
    public function __construct(private readonly DeliveryOfferService $offerService) {}

    /**
     * Acepta oferta.
     */
    public function accept(Request $request, DeliveryOffer $offer): RedirectResponse
    {
        $offer = $this->offerService->accept($offer, $request->user());

        if ($offer->pedido !== null) {
            return redirect()->route('repartidor.pedidos.show', $offer->pedido)->with('success', 'Oferta aceptada.');
        }

        if ($offer->externalOrder !== null) {
            return redirect()->route('repartidor.externas.show', $offer->externalOrder)->with('success', 'Oferta externa aceptada.');
        }

        return redirect()->route('repartidor.dashboard')->with('success', 'Oferta aceptada.');
    }

    /**
     * Rechaza oferta.
     */
    public function reject(RejectDeliveryOfferRequest $request, DeliveryOffer $offer): RedirectResponse
    {
        $this->offerService->reject($offer, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Oferta rechazada.');
    }
}
