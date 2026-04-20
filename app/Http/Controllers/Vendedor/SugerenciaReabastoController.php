<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\SugerenciaReabasto\AcceptRestockSuggestionRequest;
use App\Models\Ml\RestockSuggestion;
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
    public function __construct(private readonly ReabastoInteligenteService $reabastoInteligenteService)
    {
    }

    /**
     * Lista sugerencias para el vendedor.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewOwnRestockSuggestions', RestockSuggestion::class);

        return view('vendedor.reabasto.index', [
            'sugerencias' => $this->reabastoInteligenteService->forVendor($request->user()),
        ]);
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
