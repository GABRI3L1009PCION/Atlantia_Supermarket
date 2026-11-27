<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Vendor;
use App\Services\Catalogo\ImportadorProductosService;
use App\Services\Monetizacion\PlanesVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Carga masiva de productos para vendedores (su tienda) y administracion (cualquier tienda).
 */
class ImportarProductosController extends Controller
{
    public function __construct(private readonly ImportadorProductosService $importador) {}

    public function create(Request $request): View
    {
        $esAdmin = $request->routeIs('admin.*');
        if (! $esAdmin) {
            $this->authorize('create', Producto::class);
        }

        return view('productos.importar', [
            'esAdmin' => $esAdmin,
            'accion' => $esAdmin ? route('admin.productos.importar.store') : route('vendedor.productos.importar.store'),
            'plantilla' => $esAdmin ? route('admin.productos.importar.plantilla') : route('vendedor.productos.importar.plantilla'),
            'vendors' => $esAdmin ? Vendor::query()->where('is_approved', true)->orderBy('business_name')->get(['id', 'business_name']) : collect(),
            'columnas' => ImportadorProductosService::COLUMNAS,
            'resultado' => session('resultado_importacion'),
            /* El vendedor sabe desde el inicio si su plan incluye la carga masiva. */
            'permitido' => $esAdmin || ($request->user()->vendor !== null && app(PlanesVendedorService::class)->permiteCargaMasiva($request->user()->vendor)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $esAdmin = $request->routeIs('admin.*');
        $data = $request->validate([
            'archivo' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
            'vendor_id' => [$esAdmin ? 'required' : 'prohibited', 'integer', 'exists:vendors,id'],
        ], [
            'archivo.mimes' => 'Sube un archivo .csv. En Excel usa "Guardar como" y elige CSV UTF-8.',
            'archivo.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);

        if ($esAdmin) {
            $vendor = Vendor::query()->findOrFail($data['vendor_id']);
        } else {
            $this->authorize('create', Producto::class);
            $vendor = $request->user()->vendor;
            abort_if($vendor === null, 403);

            if (! app(PlanesVendedorService::class)->permiteCargaMasiva($vendor)) {
                return back()->with('error', 'La carga masiva está incluida desde el plan Profesional.');
            }
        }

        $resultado = $this->importador->importar($vendor, $request->file('archivo')->getRealPath());

        return back()->with('resultado_importacion', $resultado + ['tienda' => $vendor->business_name]);
    }

    public function plantilla(): Response
    {
        return response(ImportadorProductosService::plantilla(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-productos-atlantia.csv"',
        ]);
    }
}
