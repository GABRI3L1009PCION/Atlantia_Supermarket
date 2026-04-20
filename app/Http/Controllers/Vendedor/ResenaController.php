<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Services\Resenas\ResenaVendedorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de resenas visibles para vendedor.
 */
class ResenaController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly ResenaVendedorService $resenaVendedorService)
    {
    }

    /**
     * Lista resenas de productos propios.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewVendorReviews', $request->user());

        return view('vendedor.resenas.index', ['resenas' => $this->resenaVendedorService->paginate($request->user())]);
    }
}
