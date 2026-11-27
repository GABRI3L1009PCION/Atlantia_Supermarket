<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Resena;
use App\Services\Resenas\ResenaVendedorService;
use Illuminate\Http\RedirectResponse;
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
    public function __construct(private readonly ResenaVendedorService $resenaVendedorService) {}

    /**
     * Lista resenas de productos propios.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewVendorReviews', $request->user());

        return view('vendedor.resenas.index', [
            'resenas' => $this->resenaVendedorService->paginate($request->user(), $request->all()),
            'dashboard' => $this->resenaVendedorService->dashboard($request->user()),
        ]);
    }

    /**
     * Respuesta pública del vendedor a una reseña publicada de su producto.
     */
    public function responder(Request $request, Resena $resena): RedirectResponse
    {
        $this->autorizarResena($request, $resena);
        $data = $request->validate(
            ['respuesta' => ['required', 'string', 'min:3', 'max:600']],
            ['respuesta.required' => 'Escribe tu respuesta.', 'respuesta.min' => 'Tu respuesta es muy corta.', 'respuesta.max' => 'Tu respuesta puede tener hasta 600 caracteres.'],
        );

        $resena->update(['respuesta_vendedor' => trim($data['respuesta']), 'respondida_at' => now()]);

        return back()->with('success', 'Tu respuesta ya se ve en la página del producto.');
    }

    public function quitarRespuesta(Request $request, Resena $resena): RedirectResponse
    {
        $this->autorizarResena($request, $resena);
        $resena->update(['respuesta_vendedor' => null, 'respondida_at' => null]);

        return back()->with('success', 'Quitamos tu respuesta.');
    }

    /** Solo el dueño del producto, y solo reseñas ya publicadas. */
    private function autorizarResena(Request $request, Resena $resena): void
    {
        $this->authorize('viewVendorReviews', $request->user());
        $resena->loadMissing('producto');
        abort_unless((int) $resena->producto?->vendor_id === (int) $request->user()->vendor?->id, 403);
        abort_unless((bool) $resena->aprobada, 422, 'Solo puedes responder reseñas publicadas.');
    }
}
