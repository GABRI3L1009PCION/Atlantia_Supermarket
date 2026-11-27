<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\LiquidacionVendedor;
use App\Models\Vendor;
use App\Services\Vendedores\LiquidacionesVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mis ventas y pagos": saldo por cobrar, detalle venta por venta y solicitudes de pago.
 */
class PagosController extends Controller
{
    public function __construct(private readonly LiquidacionesVendedorService $liquidaciones) {}

    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);
        $perfil = $vendor->fiscalProfile;

        return view('vendedor.pagos.index', [
            'vendor' => $vendor,
            'resumen' => $this->liquidaciones->resumen($vendor),
            'ventas' => $this->liquidaciones->disponiblesQuery($vendor)->get(),
            'enCamino' => $this->liquidaciones->enCaminoQuery($vendor)->get(),
            'solicitudes' => LiquidacionVendedor::query()->where('vendor_id', $vendor->id)->latest('id')->limit(30)->get(),
            'tieneCuenta' => filled($perfil?->cuenta_bancaria) && filled($perfil?->banco_nombre),
            'pasarela' => (array) config('monetizacion.pasarela', []),
            'servicio' => $this->liquidaciones,
        ]);
    }

    /**
     * La tienda elige cada cuánto recibir su dinero (sin costo). Atlantia transfiere a mano en ese plazo.
     */
    public function frecuencia(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);
        $data = $request->validate([
            'frecuencia' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Services\Vendedores\LiquidacionesVendedorService::FRECUENCIAS))],
        ]);

        $vendor->forceFill(['payment_frequency' => $data['frecuencia']])->save();

        return back()->with('success', 'Listo: te pagaremos '.mb_strtolower(\App\Services\Vendedores\LiquidacionesVendedorService::FRECUENCIAS[$data['frecuencia']]).'. No tiene ningún costo.');
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $perfil = $vendor->fiscalProfile;

        if (blank($perfil?->cuenta_bancaria) || blank($perfil?->banco_nombre)) {
            return back()->with('error', 'Agrega tu banco y número de cuenta en tu perfil fiscal para poder recibir tu pago.');
        }

        $liquidacion = $this->liquidaciones->solicitar($vendor);

        return redirect()->route('vendedor.pagos.show', $liquidacion)
            ->with('success', 'Solicitud de pago enviada por Q '.number_format((float) $liquidacion->monto_neto, 2).'.');
    }

    public function show(Request $request, LiquidacionVendedor $liquidacion): View
    {
        abort_unless((int) $liquidacion->vendor_id === (int) $this->vendor($request)->id, 403);

        return view('vendedor.pagos.show', [
            'liquidacion' => $liquidacion->load('splits.pedido', 'splits.payment'),
            'servicio' => $this->liquidaciones,
        ]);
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
