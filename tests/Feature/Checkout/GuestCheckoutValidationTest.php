<?php

namespace Tests\Feature\Checkout;

use App\Http\Requests\Cliente\CheckoutRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GuestCheckoutValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function guestPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_nombre' => 'Cliente Invitado',
            'guest_telefono' => '55551234',
            'guest_municipio' => 'Puerto Barrios',
            'guest_direccion' => 'Calle principal casa 12',
            'guest_latitude' => 15.72,
            'guest_longitude' => -88.59,
            'tipo_entrega' => 'domicilio',
            'ventana_entrega' => '4-6 pm',
            'metodo_pago' => 'efectivo',
            'facturacion_tipo' => 'cf',
            'acepta_terminos_checkout' => '1',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function errorsFor(array $payload): array
    {
        $request = CheckoutRequest::create('/cliente/checkout', 'POST', $payload);
        $request->setContainer($this->app);
        $request->setUserResolver(fn () => null);
        (fn () => $this->prepareForValidation())->call($request);

        return Validator::make($request->all(), $request->rules())->errors()->toArray();
    }

    public function test_guest_can_checkout_without_email_or_address_type(): void
    {
        $this->assertSame([], $this->errorsFor($this->guestPayload()));
    }

    public function test_home_delivery_needs_no_time_window_but_scheduled_does(): void
    {
        $this->assertSame([], $this->errorsFor($this->guestPayload(['ventana_entrega' => null])));

        $errors = $this->errorsFor($this->guestPayload(['tipo_entrega' => 'programado', 'ventana_entrega' => null]));
        $this->assertArrayHasKey('programado_fecha', $errors);
        $this->assertArrayHasKey('ventana_entrega', $errors);

        $this->assertSame([], $this->errorsFor($this->guestPayload([
            'tipo_entrega' => 'programado',
            'programado_fecha' => now()->addDay()->toDateString(),
            'ventana_entrega' => '8-10 am',
        ])));
    }

    public function test_scheduled_delivery_rejects_past_window_for_today(): void
    {
        $this->travelTo(now()->setTime(15, 30));

        $errors = $this->errorsFor($this->guestPayload([
            'tipo_entrega' => 'programado',
            'programado_fecha' => now()->toDateString(),
            'ventana_entrega' => '2-4 pm',
        ]));

        $this->assertArrayHasKey('ventana_entrega', $errors);
    }

    public function test_gps_location_is_optional_for_guests(): void
    {
        $this->assertSame([], $this->errorsFor($this->guestPayload(['guest_latitude' => null, 'guest_longitude' => null])));
    }

    public function test_email_is_required_when_guest_creates_account(): void
    {
        $errors = $this->errorsFor($this->guestPayload([
            'crear_cuenta' => '1',
            'password' => 'Clave-Segura-2026!',
            'password_confirmation' => 'Clave-Segura-2026!',
        ]));

        $this->assertArrayHasKey('guest_email', $errors);
    }

    public function test_delivery_zone_is_limited_to_operating_towns(): void
    {
        $this->assertArrayHasKey('guest_municipio', $this->errorsFor($this->guestPayload(['guest_municipio' => 'Morales'])));
        $this->assertSame([], $this->errorsFor($this->guestPayload(['guest_municipio' => 'Santo Tomas'])));
    }

    public function test_billing_email_is_required_only_for_invoice_with_customer_data(): void
    {
        $errors = $this->errorsFor($this->guestPayload(['facturacion_tipo' => 'datos', 'nit_facturacion' => '1234567']));

        $this->assertArrayHasKey('correo_facturacion', $errors);
    }
}
