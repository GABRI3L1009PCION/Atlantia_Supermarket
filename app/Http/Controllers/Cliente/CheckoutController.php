<?php

namespace App\Http\Controllers\Cliente;

use App\Exceptions\PagoRechazadoException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\CheckoutRequest;
use App\Models\Pedido;
use App\Services\Pedidos\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de finalizacion de compra.
 */
class CheckoutController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CheckoutService $checkoutService)
    {
    }

    /**
     * Muestra la pantalla de checkout.
     */
    public function create(Request $request): View
    {
        $this->authorize('checkout', Pedido::class);

        return view('cliente.checkout.create', ['checkout' => $this->checkoutService->summary($request)]);
    }

    /**
     * Procesa la compra.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        try {
            $pedido = $this->checkoutService->checkout($request->user(), $request->validated());

            return redirect()->route('cliente.pedidos.show', $pedido)->with('success', 'Pedido creado correctamente.');
        } catch (StockInsuficienteException|PagoRechazadoException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->publicMessage())
                ->with('error_type', class_basename($exception));
        }
    }
}
