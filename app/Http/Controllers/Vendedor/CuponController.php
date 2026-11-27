<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cupones de la tienda: el vendedor los crea, los administra y los paga (solo descuentan sus productos).
 */
class CuponController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);

        return view('vendedor.cupones.index', [
            'vendor' => $vendor,
            'cupones' => Cupon::query()->where('vendor_id', $vendor->id)->latest()->get(),
            /* Lo que te ha costado cada cupón: descuento en tus pedidos donde se usó (sin cancelados). */
            'descontado' => \Illuminate\Support\Facades\DB::table('cupon_uso')
                ->join('cupones', 'cupones.id', '=', 'cupon_uso.cupon_id')
                ->join('pedidos', 'pedidos.pedido_padre_id', '=', 'cupon_uso.pedido_id')
                ->where('cupones.vendor_id', $vendor->id)
                ->where('pedidos.vendor_id', $vendor->id)
                ->whereNotIn('pedidos.estado', ['cancelado', 'rechazado'])
                ->groupBy('cupon_uso.cupon_id')
                ->selectRaw('cupon_uso.cupon_id, SUM(pedidos.descuento) as total')
                ->pluck('total', 'cupon_uso.cupon_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $this->validar($request);

        Cupon::query()->create([...$data, 'uuid' => (string) Str::uuid(), 'vendor_id' => $vendor->id, 'activo' => true]);

        return back()->with('success', 'Cupón '.$data['codigo'].' creado. Compártelo con tus clientes.');
    }

    public function update(Request $request, Cupon $cupon): RedirectResponse
    {
        $this->autorizar($request, $cupon);
        $cupon->update($this->validar($request, $cupon));

        return back()->with('success', 'Cupón '.$cupon->codigo.' actualizado.');
    }

    public function toggle(Request $request, Cupon $cupon): RedirectResponse
    {
        $this->autorizar($request, $cupon);
        $cupon->update(['activo' => ! $cupon->activo]);

        return back()->with('success', $cupon->activo ? 'Cupón activado.' : 'Cupón pausado: ya no se puede usar.');
    }

    public function destroy(Request $request, Cupon $cupon): RedirectResponse
    {
        $this->autorizar($request, $cupon);
        $cupon->delete();

        return back()->with('success', 'Cupón eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Cupon $cupon = null): array
    {
        $request->merge(['codigo' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('codigo')))]);

        $data = $request->validate([
            'codigo' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[A-Z0-9\-]+$/', Rule::unique('cupones', 'codigo')->ignore($cupon?->id)],
            'tipo' => ['required', Rule::in(['porcentaje', 'monto_fijo'])],
            'valor' => ['required', 'numeric', 'min:1', $request->input('tipo') === 'porcentaje' ? 'max:90' : 'max:5000'],
            'minimo_compra' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'maximo_descuento' => ['nullable', 'numeric', 'min:1', 'max:100000'],
            'usos_maximos' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio', 'after_or_equal:today'],
            'solo_primera_compra' => ['sometimes', 'boolean'],
            'un_uso_por_cliente' => ['sometimes', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ], [
            'codigo.required' => 'Escribe el código que usarán tus clientes.',
            'codigo.min' => 'El código debe tener al menos 4 letras o números.',
            'codigo.regex' => 'El código solo puede tener letras, números y guiones (sin espacios).',
            'codigo.unique' => 'Ese código ya existe. Prueba con otro, por ejemplo con el nombre de tu tienda.',
            'valor.required' => 'Escribe cuánto descuenta el cupón.',
            'valor.max' => $request->input('tipo') === 'porcentaje' ? 'El descuento no puede pasar del 90 %.' : 'El descuento es demasiado alto.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser antes de hoy ni antes del inicio.',
        ]);

        $data['minimo_compra'] = (float) ($data['minimo_compra'] ?? 0);
        $data['solo_primera_compra'] = $request->boolean('solo_primera_compra');
        $data['un_uso_por_cliente'] = $request->boolean('un_uso_por_cliente');
        if (filled($data['fecha_fin'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['fecha_fin']) === 1) {
            $data['fecha_fin'] = Carbon::createFromFormat('Y-m-d', $data['fecha_fin'])->endOfDay();
        }

        return $data;
    }

    private function autorizar(Request $request, Cupon $cupon): void
    {
        abort_unless((int) $cupon->vendor_id === (int) $this->vendor($request)->id, 403);
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
