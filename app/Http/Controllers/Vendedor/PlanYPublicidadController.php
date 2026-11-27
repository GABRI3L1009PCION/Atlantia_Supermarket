<?php

namespace App\Http\Controllers\Vendedor;

use App\Exceptions\PagoRechazadoException;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\HeroBanner;
use App\Models\Producto;
use App\Models\ServicioContratado;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PruebaPlanNotification;
use App\Notifications\SolicitudCotizacionPlanNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\PruebaAperturaService;
use App\Services\Monetizacion\ServiciosPagadosService;
use App\Services\Pagos\Recurrente\RecurrenteClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Panel del vendedor: su plan y la compra de publicidad (banner, oferta destacada, patrocinado).
 */
class PlanYPublicidadController extends Controller
{
    public function __construct(
        private readonly PlanesVendedorService $planes,
        private readonly ServiciosPagadosService $servicios,
    ) {}

    public function index(Request $request): View
    {
        $vendor = $this->vendor($request);

        return view('vendedor.plan.index', [
            'vendor' => $vendor,
            'planActual' => $this->planes->planActual($vendor),
            'planes' => PlanesVendedorService::todos(),
            'productos' => Producto::query()->where('vendor_id', $vendor->id)->where('is_active', true)->orderBy('nombre')->get(['id', 'nombre']),
            'servicios' => ServicioContratado::query()->where('vendor_id', $vendor->id)->latest('id')->limit(30)->get(),
            'precios' => config('monetizacion'),
            'pagosEnLinea' => RecurrenteClient::habilitado(),
            'cotizacionPendiente' => $this->cotizacionPendiente($request),
            'pruebaDisponible' => app(PruebaAperturaService::class)->disponiblePara($vendor),
        ]);
    }

    /**
     * Promoción de apertura: la tienda prueba gratis el plan que elija (una sola vez).
     */
    public function probarGratis(Request $request, PruebaAperturaService $apertura): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(PlanesVendedorService::contratables()))]]);

        if (! $apertura->disponiblePara($vendor)) {
            return back()->with('error', 'Tu tienda ya usó su prueba gratis o la promoción de apertura terminó.');
        }

        $vendor = $apertura->iniciar($vendor, $data['plan']);
        $request->user()->notify(new PruebaPlanNotification($vendor, 'iniciada'));
        $plan = PlanesVendedorService::datos($data['plan']);

        return back()->with('success', PlanesVendedorService::tieneMensualidad($data['plan'])
            ? 'Listo: usas el plan '.$plan['nombre'].' gratis hasta el '.$vendor->prueba_hasta->format('d/m/Y').'.'
            : 'Listo: vendes sin pagar la comisión de Atlantia hasta el '.$vendor->prueba_hasta->format('d/m/Y').'. La tarifa de la pasarela por pagos con tarjeta sí se cobra.');
    }

    /**
     * El vendedor pide precio del plan Personalizado sin salir de su panel.
     */
    public function pedirCotizacion(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);

        if ($this->cotizacionPendiente($request) !== null) {
            return back()->with('success', 'Ya recibimos tu solicitud. Te contactaremos pronto.');
        }

        $data = $request->validate([
            'productos' => ['required', 'integer', 'min:1', 'max:100000'],
            'telefono' => ['required', 'string', 'regex:/^[0-9+\s\-]{8,20}$/'],
            'horario' => ['nullable', Rule::in(['manana', 'tarde', 'cualquiera'])],
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ], [
            'productos.required' => 'Dinos cuántos productos quieres vender, aunque sea aproximado.',
            'telefono.required' => 'Déjanos un teléfono para llamarte.',
            'telefono.regex' => 'Escribe un teléfono válido, por ejemplo 5555 1234.',
        ]);

        $horarios = ['manana' => 'en la mañana', 'tarde' => 'en la tarde', 'cualquiera' => 'a cualquier hora'];
        ContactMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'tipo' => 'cotizacion',
            'nombre' => $vendor->business_name,
            'email' => $request->user()->email,
            'telefono' => $data['telefono'],
            'asunto' => 'Cotización plan Personalizado · '.$vendor->business_name,
            'mensaje' => 'Productos aproximados: '.number_format((int) $data['productos'])."\n"
                .'Prefiere que lo llamen '.($horarios[$data['horario'] ?? 'cualquiera'] ?? 'a cualquier hora').".\n"
                .(filled($data['mensaje'] ?? null) ? "\n".trim($data['mensaje']) : ''),
            'prioridad' => 'alta',
            'atendido' => false,
        ]);

        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();
            if ($roles !== []) {
                Notification::send(
                    User::query()->role($roles)->get(),
                    new SolicitudCotizacionPlanNotification($vendor, (int) $data['productos']),
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'Listo, recibimos tu solicitud. Te llamaremos para darte el precio de tu plan a la medida.');
    }

    private function cotizacionPendiente(Request $request): ?ContactMessage
    {
        return ContactMessage::query()
            ->where('user_id', $request->user()->id)
            ->where('asunto', 'like', 'Cotización plan Personalizado%')
            ->where('atendido', false)
            ->latest('id')
            ->first();
    }

    public function elegirPlan(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(PlanesVendedorService::contratables()))]]);
        $datos = PlanesVendedorService::datos($data['plan']);

        if ((float) $datos['precio'] <= 0) {
            $this->planes->aplicar($vendor, $data['plan']);

            return back()->with('success', 'Ahora estás en el plan '.$datos['nombre'].'.');
        }

        $servicio = $this->servicios->crear($vendor, $request->user(), 'plan', $vendor, (float) $datos['precio'], now(), now()->addMonthNoOverflow(), ['plan' => $data['plan']]);

        return $this->irAPagar($servicio);
    }

    public function comprarDestacado(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('vendor_id', $vendor->id)],
            'semana' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $inicio = Carbon::parse($data['semana'])->startOfDay();

        $ocupados = ServicioContratado::query()->where('tipo', 'destacado')->whereIn('estado', ['activo', 'pendiente_pago'])
            ->where('inicia_at', '<', $inicio->copy()->addWeek())->where('termina_at', '>', $inicio)->count();
        if ($ocupados >= (int) config('monetizacion.destacado.maximo_por_semana', 3)) {
            return back()->with('error', 'Esa semana ya tiene todos los espacios de oferta destacada vendidos. Elige otra.');
        }

        /* Avanzado, Corporativo y Personalizado incluyen semanas de oferta destacada cada mes. */
        $incluido = $this->planes->incluidosDisponibles($vendor, 'destacado') > 0;
        $servicio = $this->servicios->crear($vendor, $request->user(), 'destacado', Producto::query()->findOrFail($data['producto_id']),
            $incluido ? 0.0 : (float) config('monetizacion.destacado.semana'), $inicio, $inicio->copy()->addWeek(), ['incluido_en_plan' => $incluido]);

        if ($incluido) {
            $this->servicios->marcarPagado($servicio, 'manual');

            return back()->with('success', 'Oferta destacada reservada sin costo: está incluida en tu plan.');
        }

        return $this->irAPagar($servicio);
    }

    public function comprarPatrocinado(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')->where('vendor_id', $vendor->id)],
            'semanas' => ['required', 'integer', 'between:1,8'],
        ]);

        $servicio = $this->servicios->crear($vendor, $request->user(), 'patrocinado', Producto::query()->findOrFail($data['producto_id']),
            (float) config('monetizacion.patrocinado.semana') * (int) $data['semanas'], now(), now()->addWeeks((int) $data['semanas']));

        return $this->irAPagar($servicio);
    }

    public function comprarBanner(Request $request): RedirectResponse
    {
        $vendor = $this->vendor($request);
        $posiciones = (array) config('monetizacion.banners.posiciones');
        $data = $request->validate([
            'posicion' => ['required', Rule::in(array_keys($posiciones))],
            'periodo' => ['required', Rule::in(['semana', 'mes'])],
            'inicia' => ['required', 'date', 'after_or_equal:today'],
            'desktop_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $inicio = Carbon::parse($data['inicia'])->startOfDay();
        $fin = $data['periodo'] === 'mes' ? $inicio->copy()->addMonthNoOverflow() : $inicio->copy()->addWeek();

        /* El banner queda oculto hasta que se paga y administracion aprueba el arte. */
        $banner = HeroBanner::query()->create([
            'nombre' => 'Publicidad · '.$vendor->business_name,
            'anunciante' => $vendor->business_name,
            'url_destino' => route('comercios.show', $vendor->slug),
            'orden' => (int) $data['posicion'],
            'is_active' => false,
            'inicia_en' => $inicio,
            'termina_en' => $fin,
        ]);
        $banner->addMedia($request->file('desktop_image'))->toMediaCollection('hero_desktop');
        if ($request->hasFile('mobile_image')) {
            $banner->addMedia($request->file('mobile_image'))->toMediaCollection('hero_mobile');
        }

        /* Corporativo y Personalizado incluyen 1 semana al mes de banner en la posicion 3. */
        $incluido = (int) $data['posicion'] === 3 && $data['periodo'] === 'semana'
            && $this->planes->incluidosDisponibles($vendor, 'banner') > 0;
        $servicio = $this->servicios->crear($vendor, $request->user(), 'banner', $banner,
            $incluido ? 0.0 : (float) $posiciones[$data['posicion']][$data['periodo']], $inicio, $fin,
            ['posicion' => (int) $data['posicion'], 'periodo' => $data['periodo'], 'incluido_en_plan' => $incluido]);

        if ($incluido) {
            $this->servicios->marcarPagado($servicio, 'manual');

            return back()->with('success', 'Banner incluido en tu plan: lo revisaremos y se publicará en las fechas elegidas.');
        }

        return $this->irAPagar($servicio);
    }

    public function retorno(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        abort_unless((int) $servicio->vendor_id === (int) $this->vendor($request)->id, 403);

        try {
            $servicio = $this->servicios->verificarPago($servicio);
        } catch (PagoRechazadoException $exception) {
            return redirect()->route('vendedor.plan.index')->with('error', $exception->publicMessage());
        }

        return redirect()->route('vendedor.plan.index')->with(
            $servicio->estado === 'pendiente_pago' ? 'error' : 'success',
            match ($servicio->estado) {
                'pendiente_pago' => 'Todavía no recibimos el pago. Si ya pagaste, espera unos minutos.',
                'en_revision' => 'Pago recibido. Revisaremos tu banner y lo publicaremos en las fechas elegidas.',
                default => 'Pago recibido: '.$servicio->etiquetaTipo().' activo.',
            }
        );
    }

    private function irAPagar(ServicioContratado $servicio): RedirectResponse
    {
        if (! RecurrenteClient::habilitado()) {
            $servicio->update(['metodo_pago' => 'manual']);

            return back()->with('success', 'Solicitud registrada. Te contactaremos para el pago y lo activaremos en cuanto se confirme.');
        }

        try {
            return redirect()->away($this->servicios->enlaceDePago(
                $servicio,
                route('vendedor.servicios.retorno', $servicio),
                route('vendedor.plan.index')
            ));
        } catch (PagoRechazadoException $exception) {
            return back()->with('error', $exception->publicMessage());
        }
    }

    private function vendor(Request $request): Vendor
    {
        $vendor = $request->user()->vendor;
        abort_if($vendor === null, 403);

        return $vendor;
    }
}
