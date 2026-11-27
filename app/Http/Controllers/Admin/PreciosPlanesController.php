<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\PruebaAperturaService;
use App\Support\AjustesMonetizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Precios de planes y publicidad: el administrador los cambia sin tocar archivos.
 */
class PreciosPlanesController extends Controller
{
    public function index(): View
    {
        $planes = PlanesVendedorService::todos();
        $tiendas = Vendor::query()->where('slug', '!=', config('atlantia.vendor_propio_slug', 'atlantia-supermarket'))
            ->selectRaw("coalesce(plan, ?) as plan, count(*) as total", [PlanesVendedorService::base()])
            ->groupBy('plan')->pluck('total', 'plan');

        return view('admin.precios-planes.index', [
            'planes' => $planes,
            'base' => PlanesVendedorService::base(),
            'fabrica' => AjustesMonetizacion::fabrica('planes'),
            'cambiados' => array_keys(AjustesMonetizacion::guardado('planes')),
            'tiendas' => $tiendas,
            'banners' => (array) config('monetizacion.banners.posiciones', []),
            'destacado' => (float) config('monetizacion.destacado.semana'),
            'patrocinado' => (float) config('monetizacion.patrocinado.semana'),
            'apertura' => (array) config('monetizacion.apertura', []),
            'aperturaVigente' => PruebaAperturaService::vigente(),
            'enPrueba' => Vendor::query()->whereNotNull('prueba_usada_at')->orderByDesc('prueba_usada_at')->limit(50)
                ->get(['id', 'uuid', 'business_name', 'slug', 'plan', 'plan_hasta', 'prueba_plan', 'prueba_hasta', 'prueba_usada_at']),
        ]);
    }

    /**
     * Promoción de apertura: días gratis para tiendas nuevas, hasta una fecha.
     */
    public function actualizarApertura(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('apertura', [
            'activa' => ['nullable', 'boolean'],
            'dias' => ['required', 'integer', 'min:1', 'max:90'],
            'hasta' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'dias.required' => 'Escribe cuántos días gratis.',
            'dias.max' => 'La prueba no puede pasar de 90 días.',
            'hasta.after_or_equal' => 'La fecha límite no puede estar en el pasado.',
        ]);

        $valor = [
            'activa' => $request->boolean('activa'),
            'dias' => (int) $data['dias'],
            'hasta' => filled($data['hasta'] ?? null) ? \Illuminate\Support\Carbon::parse($data['hasta'])->toDateString() : null,
        ];
        AjustesMonetizacion::guardar('apertura', $valor, $request->user()->id);
        $this->registrar($request, 'precios.apertura_actualizada', $valor);

        return redirect()->route('admin.precios-planes.index')->withFragment('apertura')->with('success', $valor['activa']
            ? 'Promoción de apertura activa: '.$valor['dias'].' días gratis'.($valor['hasta'] ? ' para tiendas que entren hasta el '.\Illuminate\Support\Carbon::parse($valor['hasta'])->format('d/m/Y') : '').'.'
            : 'Promoción de apertura desactivada. Las tiendas que ya la tienen la terminan normalmente.');
    }

    public function actualizarPlan(Request $request, string $plan): RedirectResponse
    {
        $planes = PlanesVendedorService::todos();
        abort_unless(isset($planes[$plan]), 404);

        $esBase = $plan === PlanesVendedorService::base();
        $cotizado = (bool) ($planes[$plan]['cotizacion'] ?? false);

        $data = $request->validateWithBag('plan_'.$plan, [
            'precio' => $esBase || $cotizado ? ['prohibited'] : ['required', 'numeric', 'min:1', 'max:100000'],
            'comision' => ['required', 'numeric', 'min:0', 'max:50'],
            'max_productos' => $cotizado ? ['prohibited'] : ['required', 'integer', 'min:1', 'max:100000'],
            'max_promociones' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'carga_masiva' => ['nullable', 'boolean'],
            'destacados_incluidos' => ['required', 'integer', 'min:0', 'max:4'],
            'banners_incluidos' => ['required', 'integer', 'min:0', 'max:4'],
            'resumen' => ['required', 'string', 'max:200'],
            'beneficios' => ['required', 'string', 'max:1500'],
        ], [
            'precio.required' => 'Escribe el precio mensual.',
            'precio.min' => 'Un plan con mensualidad debe costar al menos Q1.',
            'comision.max' => 'La comisión no puede pasar del 50 %.',
            'max_productos.required' => 'Escribe cuántos productos permite.',
            'resumen.required' => 'Escribe una frase corta que explique el plan.',
            'beneficios.required' => 'Escribe al menos un beneficio.',
        ]);

        $cambios = [
            'comision' => round((float) $data['comision'], 2),
            'max_promociones' => filled($data['max_promociones'] ?? null) ? (int) $data['max_promociones'] : null,
            'carga_masiva' => $request->boolean('carga_masiva'),
            'destacados_incluidos' => (int) $data['destacados_incluidos'],
            'banners_incluidos' => (int) $data['banners_incluidos'],
            'resumen' => trim($data['resumen']),
        ];
        if (! $esBase && ! $cotizado) {
            $cambios['precio'] = round((float) $data['precio'], 2);
        }
        if (! $cotizado) {
            $cambios['max_productos'] = (int) $data['max_productos'];
        }

        /* Si la frase menciona la comisión anterior ("solo pagas el 7 %"), se actualiza sola. */
        $viejo = rtrim(rtrim(number_format((float) ($planes[$plan]['comision'] ?? 0), 2), '0'), '.');
        $nuevo = rtrim(rtrim(number_format($cambios['comision'], 2), '0'), '.');
        if ($viejo !== $nuevo && (float) $viejo > 0) {
            $cambios['resumen'] = preg_replace('/(?<![\d.])'.preg_quote($viejo, '/').' ?%/u', $nuevo.' %', $cambios['resumen']);
        }

        $lineas = array_values(array_filter(array_map('trim', preg_split('/\R/u', $data['beneficios']) ?: []), fn (string $l): bool => $l !== ''));
        $cambios['beneficios'] = AjustesMonetizacion::sincronizarBeneficios(array_slice($lineas, 0, 10), array_replace($planes[$plan], $cambios));

        $guardado = AjustesMonetizacion::guardado('planes');
        $antes = $planes[$plan];
        $guardado[$plan] = $cambios;
        AjustesMonetizacion::guardar('planes', $guardado, $request->user()->id);

        $this->registrar($request, 'precios.plan_actualizado', [
            'plan' => $plan,
            'precio_antes' => $antes['precio'] ?? null,
            'precio_nuevo' => $cambios['precio'] ?? ($antes['precio'] ?? null),
            'comision_antes' => $antes['comision'] ?? null,
            'comision_nueva' => $cambios['comision'],
        ]);

        return redirect()->route('admin.precios-planes.index')->withFragment('plan-'.$plan)
            ->with('success', 'Guardamos el plan '.$planes[$plan]['nombre'].'. Las tiendas que ya lo tienen pagan el precio nuevo desde su próxima mensualidad.');
    }

    public function restaurarPlan(Request $request, string $plan): RedirectResponse
    {
        $guardado = AjustesMonetizacion::guardado('planes');
        abort_unless(isset($guardado[$plan]), 404);

        unset($guardado[$plan]);
        $guardado === []
            ? AjustesMonetizacion::borrar('planes')
            : AjustesMonetizacion::guardar('planes', $guardado, $request->user()->id);

        $this->registrar($request, 'precios.plan_restaurado', ['plan' => $plan]);

        return redirect()->route('admin.precios-planes.index')->withFragment('plan-'.$plan)->with('success', 'El plan volvió a sus valores originales.');
    }

    public function actualizarPublicidad(Request $request): RedirectResponse
    {
        $posiciones = array_keys((array) (AjustesMonetizacion::fabrica('banners')['posiciones'] ?? []));
        $reglas = [
            'destacado_semana' => ['required', 'numeric', 'min:1', 'max:100000'],
            'patrocinado_semana' => ['required', 'numeric', 'min:1', 'max:100000'],
        ];
        foreach ($posiciones as $posicion) {
            $reglas["banners.$posicion.semana"] = ['required', 'numeric', 'min:1', 'max:100000'];
            $reglas["banners.$posicion.mes"] = ['required', 'numeric', 'min:1', 'max:100000'];
        }
        $data = $request->validateWithBag('publicidad', $reglas, [
            '*.required' => 'Escribe todos los precios.',
            'banners.*.*.required' => 'Escribe el precio de cada posición.',
            'banners.*.*.min' => 'Los precios deben ser de al menos Q1.',
        ]);

        $valor = [
            'destacado_semana' => round((float) $data['destacado_semana'], 2),
            'patrocinado_semana' => round((float) $data['patrocinado_semana'], 2),
            'banners' => collect($posiciones)->mapWithKeys(fn ($p): array => [$p => [
                'semana' => round((float) $data['banners'][$p]['semana'], 2),
                'mes' => round((float) $data['banners'][$p]['mes'], 2),
            ]])->all(),
        ];
        AjustesMonetizacion::guardar('publicidad', $valor, $request->user()->id);
        $this->registrar($request, 'precios.publicidad_actualizada', $valor);

        return redirect()->route('admin.precios-planes.index')->withFragment('publicidad')->with('success', 'Guardamos los precios de publicidad. Aplican a las compras nuevas.');
    }

    /**
     * @param  array<string, mixed>  $detalle
     */
    private function registrar(Request $request, string $evento, array $detalle): void
    {
        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'event' => $evento,
            'auditable_type' => null,
            'auditable_id' => null,
            'metadata' => $detalle,
            'method' => 'SERVICE',
        ]);
    }
}
