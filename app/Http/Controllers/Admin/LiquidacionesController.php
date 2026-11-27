<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LiquidacionVendedor;
use App\Services\Vendedores\LiquidacionesVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Solicitudes de pago de vendedores: Atlantia transfiere y registra la referencia.
 */
class LiquidacionesController extends Controller
{
    public function __construct(private readonly LiquidacionesVendedorService $liquidaciones) {}

    public function index(Request $request): View
    {
        $estado = in_array($request->query('estado'), ['solicitada', 'pagada', 'rechazada'], true) ? $request->query('estado') : 'solicitada';

        return view('admin.liquidaciones.index', [
            'estado' => $estado,
            'solicitudes' => LiquidacionVendedor::query()->with('vendor')->where('estado', $estado)
                ->when($estado === 'solicitada', fn ($q) => $q->orderByRaw('pagar_antes_de is null')->orderBy('pagar_antes_de'))
                ->latest('id')->paginate(30)->withQueryString(),
            'porPagar' => (float) LiquidacionVendedor::query()->where('estado', 'solicitada')->sum('monto_neto'),
        ]);
    }

    public function show(LiquidacionVendedor $liquidacion): View
    {
        return view('admin.liquidaciones.show', [
            'liquidacion' => $liquidacion->load('vendor.fiscalProfile', 'splits.pedido', 'splits.payment', 'pagadaPor'),
            'servicio' => $this->liquidaciones,
        ]);
    }

    public function pagar(Request $request, LiquidacionVendedor $liquidacion): RedirectResponse
    {
        $data = $request->validate(['referencia_pago' => ['required', 'string', 'max:120']]);
        $this->liquidaciones->marcarPagada($liquidacion, $data['referencia_pago'], $request->user());

        return redirect()->route('admin.liquidaciones.show', $liquidacion)->with('success', 'Pago registrado. El saldo del vendedor se actualizó.');
    }

    public function rechazar(Request $request, LiquidacionVendedor $liquidacion): RedirectResponse
    {
        $data = $request->validate(['notas' => ['required', 'string', 'max:500']]);
        $this->liquidaciones->rechazar($liquidacion, $data['notas']);

        return redirect()->route('admin.liquidaciones.index')->with('success', 'Solicitud rechazada. Las ventas regresaron al saldo del vendedor.');
    }
}
