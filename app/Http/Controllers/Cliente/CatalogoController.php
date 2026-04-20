<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Services\Catalogo\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador del catalogo publico para clientes.
 */
class CatalogoController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly CatalogoService $catalogoService)
    {
    }

    /**
     * Muestra el catalogo navegable.
     */
    public function index(Request $request): View
    {
        return view('cliente.catalogo.index', ['catalogo' => $this->catalogoService->catalogo($request->all())]);
    }
}
