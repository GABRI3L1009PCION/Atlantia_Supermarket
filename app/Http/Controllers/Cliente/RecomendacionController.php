<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Services\Ml\RecomendacionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de recomendaciones para clientes.
 */
class RecomendacionController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly RecomendacionService $recomendacionService)
    {
    }

    /**
     * Muestra recomendaciones personalizadas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewRecommendations', $request->user());

        return view('cliente.recomendaciones.index', [
            'recomendaciones' => $this->recomendacionService->forCustomer($request->user()),
        ]);
    }
}
