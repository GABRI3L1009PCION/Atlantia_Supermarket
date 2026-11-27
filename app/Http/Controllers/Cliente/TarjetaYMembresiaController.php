<?php

namespace App\Http\Controllers\Cliente;

use App\Exceptions\PagoRechazadoException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Clientes\MembresiaService;
use App\Services\Pagos\Recurrente\RecurrenteClient;
use App\ValueObjects\Dinero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Tarjeta guardada (pago con un toque) y membresia de envio gratis, ambas con Recurrente.
 * Atlantia nunca recibe los datos de la tarjeta: solo el identificador que entrega Recurrente.
 */
class TarjetaYMembresiaController extends Controller
{
    public function __construct(
        private readonly RecurrenteClient $recurrente,
        private readonly MembresiaService $membresias,
    ) {}

    public function index(Request $request): View
    {
        return view('cliente.membresia.index', [
            'user' => $request->user(),
            'activa' => $this->membresias->activa($request->user()),
            'membresiaHabilitada' => MembresiaService::habilitada(),
            'precio' => MembresiaService::precio(),
            'minimo' => MembresiaService::minimoEnvioGratis(),
            'pagosEnLinea' => RecurrenteClient::habilitado(),
        ]);
    }

    /* ---------------- Tarjeta guardada ---------------- */

    public function guardarTarjeta(Request $request): RedirectResponse
    {
        abort_unless(RecurrenteClient::habilitado(), 404);
        $user = $request->user();

        try {
            $checkout = $this->recurrente->crearCheckoutGuardarTarjeta(
                $this->customerId($user),
                route('cliente.tarjeta.retorno'),
                route('cliente.membresia.index'),
                'setup-'.$user->uuid.'-'.now()->timestamp
            );
        } catch (PagoRechazadoException $exception) {
            return back()->with('error', $exception->publicMessage());
        }

        $request->session()->put('recurrente_setup_checkout', $checkout['id'] ?? null);

        return redirect()->away((string) $checkout['checkout_url']);
    }

    public function retornoTarjeta(Request $request): RedirectResponse
    {
        $checkoutId = (string) $request->session()->pull('recurrente_setup_checkout', '');
        abort_if($checkoutId === '', 404);

        try {
            $checkout = $this->recurrente->obtenerCheckout($checkoutId);
        } catch (PagoRechazadoException $exception) {
            return redirect()->route('cliente.membresia.index')->with('error', $exception->publicMessage());
        }

        $metodoPagoId = (string) data_get($checkout, 'payment_method.id', '');
        if ($metodoPagoId === '') {
            return redirect()->route('cliente.membresia.index')->with('error', 'No se pudo guardar la tarjeta. Inténtalo de nuevo.');
        }

        $ultimos = (string) (data_get($checkout, 'payment_method.card.last4') ?? '');
        $marca = (string) (data_get($checkout, 'payment_method.card.network') ?? data_get($checkout, 'payment_method.card.brand') ?? 'Tarjeta');
        $request->user()->forceFill([
            'recurrente_metodo_pago_id' => $metodoPagoId,
            'tarjeta_descripcion' => trim(Str::ucfirst($marca).($ultimos !== '' ? ' •••• '.$ultimos : '')),
        ])->save();

        return redirect()->route('cliente.membresia.index')->with('success', 'Tarjeta guardada: la próxima vez pagas con un toque.');
    }

    public function eliminarTarjeta(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['recurrente_metodo_pago_id' => null, 'tarjeta_descripcion' => null])->save();

        return back()->with('success', 'Quitamos tu tarjeta guardada.');
    }

    /* ---------------- Membresia ---------------- */

    public function suscribir(Request $request): RedirectResponse
    {
        abort_unless(RecurrenteClient::habilitado() && MembresiaService::habilitada(), 404);
        $user = $request->user();

        try {
            $checkout = $this->recurrente->crearCheckout(
                items: [[
                    'name' => 'Membresía Atlantia Envío Gratis',
                    'amount_in_cents' => Dinero::from(MembresiaService::precio())->toCents(),
                    'currency' => 'GTQ',
                    'quantity' => 1,
                    'charge_type' => 'recurring',
                    'billing_interval' => 'month',
                    'billing_interval_count' => 1,
                ]],
                successUrl: route('cliente.membresia.retorno'),
                cancelUrl: route('cliente.membresia.index'),
                metadata: ['tipo' => 'membresia', 'user_uuid' => $user->uuid],
                expiresAt: now()->addMinutes(30),
                idempotencyKey: 'membresia-'.$user->uuid.'-'.now()->format('YmdHi')
            );
        } catch (PagoRechazadoException $exception) {
            return back()->with('error', $exception->publicMessage());
        }

        $request->session()->put('recurrente_membresia_checkout', $checkout['id']);

        return redirect()->away((string) $checkout['checkout_url']);
    }

    public function retornoMembresia(Request $request): RedirectResponse
    {
        $checkoutId = (string) $request->session()->pull('recurrente_membresia_checkout', '');
        abort_if($checkoutId === '', 404);

        try {
            $checkout = $this->recurrente->obtenerCheckout($checkoutId);
        } catch (PagoRechazadoException $exception) {
            return redirect()->route('cliente.membresia.index')->with('error', $exception->publicMessage());
        }

        if (($checkout['status'] ?? null) !== 'paid') {
            return redirect()->route('cliente.membresia.index')->with('error', 'El pago de la membresía no se completó.');
        }

        $suscripcionId = data_get($checkout, 'payment.paymentable.type') === 'Subscription'
            ? (string) data_get($checkout, 'payment.paymentable.id')
            : null;
        $this->membresias->extenderUnMes($request->user(), $suscripcionId);

        return redirect()->route('cliente.membresia.index')->with('success', '¡Bienvenido a la membresía! Tus envíos desde Q '.number_format(MembresiaService::minimoEnvioGratis(), 2).' son gratis.');
    }

    public function cancelarMembresia(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (filled($user->membresia_suscripcion_id)) {
            try {
                $this->recurrente->cancelarSuscripcion($user->membresia_suscripcion_id);
            } catch (PagoRechazadoException $exception) {
                return back()->with('error', $exception->publicMessage());
            }
        }

        $user->forceFill(['membresia_suscripcion_id' => null])->save();

        return back()->with('success', 'Cancelamos los próximos cobros. Conservas el envío gratis hasta '.optional($user->membresia_hasta)->format('d/m/Y').'.');
    }

    private function customerId(User $user): string
    {
        if (filled($user->recurrente_customer_id)) {
            return $user->recurrente_customer_id;
        }

        $cliente = $this->recurrente->crearCliente($user->email, $user->name);
        $id = (string) ($cliente['id'] ?? '');
        if ($id === '') {
            throw new PagoRechazadoException('No se pudo registrar tu cuenta en la pasarela de pago.');
        }

        $user->forceFill(['recurrente_customer_id' => $id])->save();

        return $id;
    }
}
