<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\Fel\FacturaManualService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * La tienda registra la factura que emitió en la SAT para un pedido (o la quita si se equivocó).
 */
class FacturaManualController extends Controller
{
    public function store(Request $request, Pedido $pedido, FacturaManualService $facturas): RedirectResponse
    {
        $this->authorize('viewVendorOrder', $pedido);
        abort_if($pedido->vendor_id !== $request->user()->vendor?->id, 403);

        $data = $request->validateWithBag('factura', [
            'serie' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/'],
            'numero' => ['required', 'string', 'max:20', 'regex:/^[0-9\-\s]+$/'],
            'autorizacion' => ['required', 'string', 'regex:/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/', Rule::unique('dte_facturas', 'uuid_sat')],
            'fecha' => ['required', 'date', 'before_or_equal:today', 'after:'.now()->subYear()->toDateString()],
            'monto_total' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'serie.required' => 'Escribe la serie de la factura.',
            'serie.regex' => 'La serie solo lleva letras y números (por ejemplo 8F2A1C3D).',
            'numero.required' => 'Escribe el número de la factura.',
            'numero.regex' => 'El número solo lleva dígitos.',
            'autorizacion.required' => 'Escribe el número de autorización que te dio la SAT.',
            'autorizacion.regex' => 'La autorización tiene este formato: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX (letras A-F y números).',
            'autorizacion.unique' => 'Esa autorización ya está registrada en otro pedido.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'pdf.mimes' => 'El archivo debe ser el PDF de la factura.',
            'pdf.max' => 'El PDF no puede pesar más de 5 MB.',
        ]);

        $facturas->registrar($pedido, $request->user(), $data, $request->file('pdf'));

        return back()->with('success', 'Factura registrada. Tu cliente ya la puede ver en su pedido.');
    }

    public function destroy(Request $request, Pedido $pedido, FacturaManualService $facturas): RedirectResponse
    {
        $this->authorize('viewVendorOrder', $pedido);
        abort_if($pedido->vendor_id !== $request->user()->vendor?->id, 403);

        $factura = $pedido->dteFacturas()->where('origen', 'manual')->latest('id')->firstOrFail();
        $facturas->quitar($factura);

        return back()->with('success', 'Quitamos la factura registrada. Puedes registrarla de nuevo con los datos correctos.');
    }
}
