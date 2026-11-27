<?php

namespace App\Services\Monetizacion;

use App\Exceptions\PagoRechazadoException;
use App\Models\HeroBanner;
use App\Models\ServicioContratado;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Pagos\Recurrente\RecurrenteClient;
use App\ValueObjects\Dinero;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Venta de servicios de Atlantia a comercios y anunciantes, cobrados con Recurrente
 * (o registrados a mano cuando pagan en efectivo o transferencia).
 */
class ServiciosPagadosService
{
    public function __construct(
        private readonly RecurrenteClient $recurrente,
        private readonly PlanesVendedorService $planes,
    ) {}

    /**
     * @param  array<string, mixed>  $detalle
     */
    public function crear(
        ?Vendor $vendor,
        ?User $comprador,
        string $tipo,
        ?Model $referencia,
        float $monto,
        ?Carbon $inicia,
        ?Carbon $termina,
        array $detalle = [],
        ?string $anunciante = null,
    ): ServicioContratado {
        return ServicioContratado::query()->create([
            'uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor?->id,
            'user_id' => $comprador?->id,
            'tipo' => $tipo,
            'referencia_type' => $referencia?->getMorphClass(),
            'referencia_id' => $referencia?->getKey(),
            'anunciante' => $anunciante ?? $vendor?->business_name,
            'detalle' => $detalle,
            'monto' => round($monto, 2),
            'inicia_at' => $inicia,
            'termina_at' => $termina,
            'estado' => 'pendiente_pago',
        ]);
    }

    /**
     * Crea el checkout de Recurrente y devuelve el enlace de pago.
     * Los planes se cobran como suscripcion mensual; lo demas es un pago unico.
     *
     * @throws PagoRechazadoException
     */
    public function enlaceDePago(ServicioContratado $servicio, string $urlRetorno, string $urlCancelar): string
    {
        $item = [
            'name' => 'Atlantia · '.$servicio->etiquetaTipo(),
            'amount_in_cents' => Dinero::from((float) $servicio->monto)->toCents(),
            'currency' => 'GTQ',
            'quantity' => 1,
        ];

        if ($servicio->tipo === 'plan') {
            $item += ['charge_type' => 'recurring', 'billing_interval' => 'month', 'billing_interval_count' => 1];
        }

        $checkout = $this->recurrente->crearCheckout(
            items: [$item],
            successUrl: $urlRetorno,
            cancelUrl: $urlCancelar,
            metadata: ['tipo' => 'servicio', 'servicio_uuid' => $servicio->uuid],
            expiresAt: now()->addHours(2),
            idempotencyKey: 'servicio-'.$servicio->uuid
        );

        $servicio->update(['recurrente_checkout_id' => $checkout['id']]);

        return (string) $checkout['checkout_url'];
    }

    /**
     * Verifica en Recurrente si el checkout se pago y activa el servicio.
     *
     * @throws PagoRechazadoException
     */
    public function verificarPago(ServicioContratado $servicio): ServicioContratado
    {
        if ($servicio->estado !== 'pendiente_pago' || blank($servicio->recurrente_checkout_id)) {
            return $servicio;
        }

        $checkout = $this->recurrente->obtenerCheckout($servicio->recurrente_checkout_id);
        if (($checkout['status'] ?? null) !== 'paid') {
            return $servicio;
        }

        $suscripcion = data_get($checkout, 'payment.paymentable.type') === 'Subscription'
            ? (string) data_get($checkout, 'payment.paymentable.id')
            : null;

        return $this->marcarPagado($servicio, 'recurrente', $suscripcion);
    }

    /**
     * Activa un servicio pagado. Los banners quedan en revision hasta que administracion aprueba el arte.
     */
    public function marcarPagado(ServicioContratado $servicio, string $metodo = 'manual', ?string $suscripcionId = null): ServicioContratado
    {
        $servicio->update([
            'estado' => $servicio->tipo === 'banner' ? 'en_revision' : 'activo',
            'metodo_pago' => $metodo,
            'pagado_at' => now(),
        ]);

        if ($servicio->tipo === 'plan' && $servicio->vendor !== null) {
            $this->planes->aplicar($servicio->vendor, (string) data_get($servicio->detalle, 'plan'), $suscripcionId);
        }

        return $servicio->refresh();
    }

    /**
     * Administracion aprueba el arte de un banner pagado: se publica en sus fechas.
     */
    public function aprobarBanner(ServicioContratado $servicio): void
    {
        $banner = $servicio->referencia;
        if ($banner instanceof HeroBanner) {
            $banner->update(['is_active' => true, 'inicia_en' => $servicio->inicia_at, 'termina_en' => $servicio->termina_at]);
        }

        $servicio->update(['estado' => 'activo']);
    }

    public function rechazar(ServicioContratado $servicio): void
    {
        $servicio->update(['estado' => 'rechazado']);
        if ($servicio->referencia instanceof HeroBanner) {
            $servicio->referencia->update(['is_active' => false]);
        }
    }

    /**
     * Marca como terminados los servicios cuya fecha ya paso.
     */
    public function vencerTerminados(): int
    {
        return ServicioContratado::query()
            ->whereIn('estado', ['activo', 'en_revision'])
            ->whereNotNull('termina_at')
            ->where('termina_at', '<', now())
            ->update(['estado' => 'vencido']);
    }
}
