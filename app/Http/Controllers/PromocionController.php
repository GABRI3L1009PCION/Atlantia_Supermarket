<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Vendor;
use App\Services\Monetizacion\PlanesVendedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Promociones automaticas: cada comercio gestiona las suyas; administracion gestiona todas.
 */
class PromocionController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);
        $esAdmin = $vendor === null;

        return view('promociones.index', [
            'esAdmin' => $esAdmin,
            'prefijo' => $esAdmin ? 'admin' : 'vendedor',
            'promociones' => Promocion::query()
                ->with(['producto:id,nombre', 'categoria:id,nombre', 'vendor:id,business_name'])
                ->when(! $esAdmin, fn ($q) => $q->where('vendor_id', $vendor->id))
                ->latest('id')
                ->paginate(20),
            'productos' => Producto::query()
                ->where('vendor_id', $esAdmin ? \App\Http\Controllers\Admin\CuponController::atlantiaId() : $vendor->id)
                ->where('is_active', true)
                ->orderBy('nombre')
                ->limit(1000)
                ->get(['id', 'nombre', 'vendor_id']),
            /* El vendedor solo ve las categorías donde tiene productos. */
            'categorias' => Categoria::query()->where('is_active', true)
                ->when(! $esAdmin, fn ($q) => $q->whereHas('productos', fn ($p) => $p->where('vendor_id', $vendor->id)))
                ->orderBy('nombre')->get(['id', 'nombre']),
            'vendors' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'tipo' => ['required', Rule::in(['2x1', '3x2', 'porcentaje'])],
            'porcentaje' => ['required_if:tipo,porcentaje', 'nullable', 'numeric', 'min:1', 'max:90'],
            'producto_id' => ['nullable', 'integer', Rule::exists('productos', 'id')->where('vendor_id', $vendor?->id ?? \App\Http\Controllers\Admin\CuponController::atlantiaId())],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'inicia_at' => ['nullable', 'date'],
            'termina_at' => ['nullable', 'date', 'after:inicia_at'],
            'hora_inicio' => ['nullable', 'date_format:H:i', 'required_with:hora_fin'],
            'hora_fin' => ['nullable', 'date_format:H:i', 'required_with:hora_inicio', 'after:hora_inicio'],
        ], [
            'porcentaje.required_if' => 'Indica el porcentaje de descuento.',
            'hora_fin.after' => 'La hora de fin debe ser después del inicio.',
        ]);

        if ($vendor !== null) {
            try {
                app(PlanesVendedorService::class)->assertPuedeCrearPromocion($vendor);
            } catch (\RuntimeException $exception) {
                return back()->withInput()->with('error', $exception->getMessage());
            }
        }

        [$lleva, $paga] = match ($data['tipo']) {
            '2x1' => [2, 1],
            '3x2' => [3, 2],
            default => [null, null],
        };

        Promocion::query()->create([
            'uuid' => (string) Str::uuid(),
            /* Lo que crea la administración es de Atlantia Store: aplica a sus productos y lo paga Atlantia. */
            'vendor_id' => $vendor?->id ?? \App\Http\Controllers\Admin\CuponController::atlantiaId(),
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'] === 'porcentaje' ? 'porcentaje' : 'nxm',
            'lleva' => $lleva,
            'paga' => $paga,
            'porcentaje' => $data['tipo'] === 'porcentaje' ? $data['porcentaje'] : null,
            'producto_id' => $data['producto_id'] ?? null,
            'categoria_id' => ($data['producto_id'] ?? null) ? null : ($data['categoria_id'] ?? null),
            'inicia_at' => $data['inicia_at'] ?? null,
            'termina_at' => $data['termina_at'] ?? null,
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'activa' => true,
        ]);

        return back()->with('success', 'Promoción creada. Se aplica sola en el carrito.');
    }

    public function toggle(Request $request, Promocion $promocion): RedirectResponse
    {
        $this->autorizar($request, $promocion);
        $promocion->update(['activa' => ! $promocion->activa]);

        return back()->with('success', $promocion->activa ? 'Promoción activada.' : 'Promoción pausada.');
    }

    public function destroy(Request $request, Promocion $promocion): RedirectResponse
    {
        $this->autorizar($request, $promocion);
        $promocion->delete();

        return back()->with('success', 'Promoción eliminada.');
    }

    /**
     * Vendor del usuario en el panel de vendedor; null en administracion.
     */
    private function vendor(Request $request): ?Vendor
    {
        if ($request->routeIs('admin.*')) {
            return null;
        }

        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }

    private function autorizar(Request $request, Promocion $promocion): void
    {
        $vendor = $this->vendor($request);
        abort_if($vendor !== null && (int) $promocion->vendor_id !== (int) $vendor->id, 403);
    }
}
