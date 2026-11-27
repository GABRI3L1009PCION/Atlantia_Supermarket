<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cupon\StoreCuponRequest;
use App\Http\Requests\Admin\Cupon\UpdateCuponRequest;
use App\Models\Cupon;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controlador administrativo de cupones.
 */
class CuponController extends Controller
{
    /**
     * Lista cupones y metricas de uso.
     */
    public function index(): View
    {
        return view('admin.cupones.index', [
            'cupones' => Cupon::query()->with('vendor:id,business_name')
                ->when(request('tienda') === 'atlantia', fn ($q) => $q->where('vendor_id', self::atlantiaId()))
                ->when(request('tienda') === 'vendedores', fn ($q) => $q->where('vendor_id', '!=', self::atlantiaId()))
                ->latest()->paginate(20)->withQueryString(),
            'cuponMetrics' => [
                'activos' => Cupon::query()->where('activo', true)->count(),
                'usos' => (int) Cupon::query()->sum('usos_actuales'),
                'primera_compra' => Cupon::query()->where('solo_primera_compra', true)->count(),
                'vigentes' => Cupon::query()->vigentes()->count(),
            ],
        ]);
    }

    /**
     * Crea un nuevo cupon.
     */
    public function store(StoreCuponRequest $request): RedirectResponse
    {
        /* Lo que crea la administración es de Atlantia Store: solo descuenta sus productos y lo paga Atlantia. */
        Cupon::query()->create([
            ...$this->conFinDeDia($request->validated()),
            'uuid' => (string) Str::uuid(),
            'vendor_id' => self::atlantiaId(),
        ]);

        return back()->with('success', 'Cupon creado correctamente.');
    }

    /**
     * Actualiza un cupon existente.
     */
    public function update(UpdateCuponRequest $request, Cupon $cupon): RedirectResponse
    {
        $cupon->update($this->conFinDeDia($request->validated()));

        return back()->with('success', 'Cupon actualizado correctamente.');
    }

    /**
     * Elimina un cupon.
     */
    public function destroy(Cupon $cupon): RedirectResponse
    {
        $cupon->delete();

        return back()->with('success', 'Cupon eliminado correctamente.');
    }

    public static function atlantiaId(): ?int
    {
        $id = Vendor::query()->where('slug', config('atlantia.vendor_propio_slug', 'atlantia-supermarket'))->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Un cupon valido "hasta" una fecha debe durar todo ese dia, no vencer a las 00:00.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function conFinDeDia(array $datos): array
    {
        $fin = $datos['fecha_fin'] ?? null;

        if (is_string($fin) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) === 1) {
            $datos['fecha_fin'] = Carbon::createFromFormat('Y-m-d', $fin)->endOfDay();
        }

        return $datos;
    }
}
