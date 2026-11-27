<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DenunciaTienda;
use App\Models\Vendor;
use App\Services\Vendedores\DenunciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Denuncias de clientes contra tiendas: revisarlas, y reactivar o suspender la tienda.
 */
class DenunciaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = in_array($request->query('estado'), ['pendiente', 'confirmada', 'descartada', 'todas'], true) ? $request->query('estado') : 'pendiente';

        return view('admin.denuncias.index', [
            'estado' => $estado,
            'denuncias' => DenunciaTienda::query()->with(['vendor', 'cliente', 'pedido.pedidoPadre', 'revisor'])
                ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
                ->latest('id')->paginate(25)->withQueryString(),
            'pendientes' => DenunciaTienda::query()->where('estado', 'pendiente')->count(),
            /* Tiendas pausadas automáticamente que esperan revisión. */
            'enRevision' => Vendor::query()->whereNotNull('pagos_retenidos_at')->withCount(['denuncias as denuncias_pendientes' => fn ($q) => $q->where('estado', 'pendiente')])->get(),
        ]);
    }

    public function resolver(Request $request, DenunciaTienda $denuncia, DenunciaService $denuncias): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(['confirmada', 'descartada'])],
            'resolucion' => ['nullable', 'string', 'max:1000'],
        ]);

        $denuncias->resolver($denuncia, $request->user(), $data['estado'], $data['resolucion'] ?? null);

        return back()->with('success', $data['estado'] === 'confirmada'
            ? 'Denuncia confirmada. Si la tienda no cumple, suspéndela desde Vendedores.'
            : 'Denuncia descartada.');
    }

    public function reactivar(Vendor $vendor, DenunciaService $denuncias): RedirectResponse
    {
        $denuncias->reactivar($vendor);

        return back()->with('success', $vendor->business_name.' vuelve a recibir pedidos y sus pagos se liberan.');
    }
}
