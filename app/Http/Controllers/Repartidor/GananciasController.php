<?php

namespace App\Http\Controllers\Repartidor;

use App\Http\Controllers\Controller;
use App\Services\Repartidores\CourierWalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador de ganancias y billetera.
 */
class GananciasController extends Controller
{
    public function __construct(private readonly CourierWalletService $walletService) {}

    /**
     * Muestra billetera.
     */
    public function __invoke(Request $request): View
    {
        return view('repartidor.ganancias', [
            'summary' => $this->walletService->summary($request->user()),
        ]);
    }
}
