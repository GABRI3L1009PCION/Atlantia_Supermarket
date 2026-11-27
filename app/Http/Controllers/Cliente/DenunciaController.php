<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\DenunciaTienda;
use App\Models\Vendor;
use App\Services\Vendedores\DenunciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * El cliente denuncia a una tienda (no entregó, fraude, productos ilegales, mal uso...).
 */
class DenunciaController extends Controller
{
    public function store(Request $request, Vendor $vendor, DenunciaService $denuncias): RedirectResponse
    {
        abort_unless($vendor->status === 'approved', 404);

        $data = $request->validateWithBag('denuncia', [
            'motivo' => ['required', Rule::in(array_keys(DenunciaTienda::MOTIVOS))],
            'descripcion' => ['required', 'string', 'min:15', 'max:1500'],
            'pedido_uuid' => ['nullable', 'string', 'max:36'],
        ], [
            'motivo.required' => 'Elige qué pasó.',
            'descripcion.required' => 'Cuéntanos qué pasó.',
            'descripcion.min' => 'Danos un poco más de detalle para poder revisarlo.',
        ]);

        try {
            $denuncias->crear($vendor, $request->user(), $data);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            /* El aviso se muestra dentro del formulario de denuncia. */
            throw $exception->errorBag('denuncia');
        }

        return redirect()->to(route('comercios.show', $vendor->slug).'#denunciar')
            ->with('success', 'Recibimos tu denuncia. Atlantia la revisará y tomará medidas si la tienda no cumple. Gracias por avisarnos.');
    }
}
