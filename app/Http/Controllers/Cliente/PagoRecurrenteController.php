<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\EstadoPago;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Pagos\Recurrente\RecurrentePagoService;
use App\Services\Pedidos\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Regreso del cliente desde el checkout alojado de Recurrente.
 *
 * El navegador nunca acredita el pago: al volver se consulta el checkout en la
 * API de Recurrente desde el servidor. El webhook es la fuente principal.
 */
class PagoRecurrenteController extends Controller
{
    public function __construct(private readonly RecurrentePagoService $recurrentePagoService) {}

    /**
     * success_url del checkout.
     */
    public function retorno(Request $request, Payment $payment): RedirectResponse
    {
        $payment = $this->sincronizar($payment);

        return match ($payment->estado) {
            EstadoPago::Aprobado, EstadoPago::Pagado => $this->redirigirAlPedido($request, $payment)
                ->with('success', 'Pago con tarjeta recibido. Tu pedido quedo confirmado.'),
            EstadoPago::Rechazado, EstadoPago::Anulado => $this->redirigirAlPedido($request, $payment)
                ->with('error', 'El pago no se completo y el pedido fue cancelado.'),
            /* La vista del pedido muestra el aviso de pago en confirmacion. */
            default => $this->redirigirAlPedido($request, $payment),
        };
    }

    /**
     * cancel_url del checkout: el cliente salio sin pagar.
     */
    public function cancelado(Request $request, Payment $payment): RedirectResponse
    {
        $payment = $this->sincronizar($payment);

        if (! CheckoutService::esperaPagoEnLinea($payment)) {
            return $this->retorno($request, $payment);
        }

        return $this->redirigirAlPedido($request, $payment)
            ->with('error', 'No se completo el pago con tarjeta. Puedes intentarlo de nuevo antes de que venza el enlace.');
    }

    /**
     * Muestra el pago con tarjeta dentro de Atlantia (checkout embebido de Recurrente).
     * Si el sitio no corre en https ni en localhost, el formulario de tarjeta del procesador
     * no funciona dentro del iframe; en ese caso se usa la pagina alojada de Recurrente.
     */
    public function pagar(Request $request, Payment $payment): RedirectResponse|View
    {
        $pedido = $payment->pedido;
        $esDueno = $pedido !== null && (
            ($request->user() !== null && (int) $pedido->cliente_id === (int) $request->user()->id)
            || in_array($pedido->uuid, $request->session()->get('guest_order_uuids', []), true)
        );

        abort_unless($esDueno, 403);

        $payload = $payment->pasarela_payload ?? [];
        $venceEn = isset($payload['checkout_expires_at']) ? Carbon::parse($payload['checkout_expires_at']) : null;

        if (! CheckoutService::esperaPagoEnLinea($payment) || empty($payload['checkout_url']) || ($venceEn !== null && $venceEn->isPast())) {
            return $this->redirigirAlPedido($request, $payment)
                ->with('error', 'Este enlace de pago ya no esta disponible.');
        }

        $url = (string) $payload['checkout_url'];

        if (! $this->puedeEmbeberse($request)) {
            return redirect()->away($url);
        }

        $pedido->loadMissing(['items', 'pedidosHijos.items', 'pedidosHijos.vendor']);
        $items = $pedido->items->isNotEmpty()
            ? $pedido->items
            : $pedido->pedidosHijos->flatMap(fn ($hijo) => $hijo->items);

        return view('cliente.pagos.recurrente', [
            'payment' => $payment,
            'pedido' => $pedido,
            'items' => $items,
            'total' => isset($payload['amount_in_cents']) ? ((int) $payload['amount_in_cents']) / 100 : $pedido->montoACobrar(),
            'checkoutUrl' => $url,
            'venceEn' => $venceEn,
            'retornoUrl' => route('cliente.pagos.recurrente.retorno', $payment),
            'canceladoUrl' => route('cliente.pagos.recurrente.cancelado', $payment),
            'modoDesarrollo' => app()->environment('local'),
        ]);
    }

    private function puedeEmbeberse(Request $request): bool
    {
        if (! (bool) config('services.recurrente.embebido', true)) {
            return false;
        }

        return $request->secure() || $request->getHost() === 'localhost';
    }

    private function sincronizar(Payment $payment): Payment
    {
        try {
            return $this->recurrentePagoService->sincronizar($payment);
        } catch (Throwable $exception) {
            Log::warning('No fue posible verificar el checkout de Recurrente al regresar.', [
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            return $payment->refresh();
        }
    }

    private function redirigirAlPedido(Request $request, Payment $payment): RedirectResponse
    {
        $pedido = $payment->pedido;

        if ($pedido === null) {
            return redirect()->route('home');
        }

        if ($request->user() !== null && (int) $pedido->cliente_id === (int) $request->user()->id) {
            return redirect()->route('cliente.pedidos.show', $pedido);
        }

        if (in_array($pedido->uuid, $request->session()->get('guest_order_uuids', []), true)) {
            return redirect()->route('cliente.pedidos.guest-show', $pedido);
        }

        return redirect()->route('home');
    }
}
