<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Devolucion;
use App\Models\Pedido;
use App\Models\Vendor;
use App\Services\Pedidos\DevolucionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Devoluciones de los pedidos de la tienda: el vendedor las ve y da su versión; Atlantia decide.
 */
class DevolucionController extends Controller
{
    public function __construct(private readonly DevolucionService $devoluciones) {}

    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);
        $filtro = in_array($request->query('estado'), ['solicitada', 'aprobada', 'rechazada'], true) ? $request->query('estado') : null;

        $lista = DevolucionService::paraTienda($vendor->id)
            ->with(['pedido.items', 'pedido.pedidosHijos.items', 'user:id,name'])
            ->when($filtro, fn ($q) => $q->where('estado', $filtro))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        /* De cada devolución, solo lo que es de esta tienda. */
        $lista->getCollection()->transform(function (Devolucion $devolucion) use ($vendor): Devolucion {
            $suyo = DevolucionService::pedidosDeTienda($devolucion->pedido)->firstWhere('vendor_id', $vendor->id);
            $devolucion->setAttribute('pedido_tienda', $suyo instanceof Pedido ? $suyo->loadMissing('items') : null);

            return $devolucion;
        });

        return view('vendedor.devoluciones.index', [
            'devoluciones' => $lista,
            'filtro' => $filtro,
            'conteos' => DevolucionService::paraTienda($vendor->id)->selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado'),
        ]);
    }

    public function responder(Request $request, Devolucion $devolucion): RedirectResponse
    {
        $vendor = $this->vendor($request);
        abort_unless(DevolucionService::paraTienda($vendor->id)->whereKey($devolucion->id)->exists(), 403);

        $data = $request->validate([
            'de_acuerdo' => ['required', 'boolean'],
            'comentario' => ['nullable', 'required_if:de_acuerdo,0', 'string', 'max:800'],
        ], [
            'comentario.required_if' => 'Cuéntanos por qué no estás de acuerdo, así Atlantia puede revisarlo.',
        ]);

        $this->devoluciones->responderVendedor($devolucion, (bool) $data['de_acuerdo'], $data['comentario'] ?? null);

        return back()->with('success', 'Enviamos tu respuesta a Atlantia. Te avisaremos cuando decida.');
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
