<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\SugerenciaReabasto\AcceptRestockSuggestionRequest;
use App\Models\Ml\RestockSuggestion;
use App\Models\Producto;
use App\Services\Inventario\ReabastoInteligenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de sugerencias de reabastecimiento.
 */
class SugerenciaReabastoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ReabastoInteligenteService $reabastoInteligenteService) {}

    /**
     * Lista sugerencias para el vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnRestockSuggestions', RestockSuggestion::class);

        return view('vendedor.reabasto.index', [
            'lista' => $this->reabastoInteligenteService->listaCompra($request->user(), $request->all()),
        ]);
    }

    /**
     * "Ya lo recibí": suma al inventario lo que llegó del proveedor.
     */
    public function recibir(Request $request, Producto $producto): RedirectResponse
    {
        $this->authorize('viewOwnRestockSuggestions', RestockSuggestion::class);
        abort_unless((int) $producto->vendor_id === (int) $request->user()->vendor?->id, 403);
        $data = $request->validate(
            ['cantidad' => ['required', 'integer', 'min:1', 'max:100000']],
            ['cantidad.required' => 'Escribe cuántas unidades recibiste.', 'cantidad.min' => 'Escribe al menos 1 unidad.'],
        );

        $this->reabastoInteligenteService->recibir($producto, (int) $data['cantidad'], $request->user());

        return back()->with('success', 'Sumamos '.$data['cantidad'].' '.((int) $data['cantidad'] === 1 ? 'unidad' : 'unidades').' de '.$producto->nombre.' a tu inventario.');
    }

    /**
     * Acepta una sugerencia.
     */
    public function accept(AcceptRestockSuggestionRequest $request, RestockSuggestion $suggestion): RedirectResponse
    {
        $this->authorize('accept', $suggestion);
        $this->reabastoInteligenteService->accept($suggestion, $request->validated(), $request->user());

        return back()->with('success', 'Sugerencia de reabastecimiento aceptada.');
    }
}
