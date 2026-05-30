<?php

namespace App\Http\Controllers\Cliente;

use App\DTOs\PedidoDTO;
use App\Exceptions\DireccionFueraDeZonaException;
use App\Exceptions\PagoRechazadoException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\TransaccionFallidaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\CheckoutRequest;
use App\Models\Cliente\Direccion;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Carrito\CarritoService;
use App\Services\Geolocalizacion\DeliveryCoverageService;
use App\Services\Pedidos\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Controlador de finalizacion de compra.
 */
class CheckoutController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CarritoService $carritoService
    ) {
    }

    /**
     * Muestra la pantalla de checkout.
     */
    public function create(Request $request): View
    {
        if ($request->user() !== null) {
            $this->authorize('checkout', Pedido::class);
        }

        return view('cliente.checkout.create', ['checkout' => $this->checkoutService->summary($request)]);
    }

    /**
     * Procesa la compra.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cliente = $request->user();
        $startedAsGuest = $cliente === null;

        try {
            $data = $request->validated();
            $loginAfterCheckout = false;

            if ($startedAsGuest) {
                [$cliente, $direccion] = $this->prepareGuestCustomer($request, $data);
                $data['direccion_id'] = $direccion->id;
                $data['envio'] = app(DeliveryCoverageService::class)->deliveryCostFor($direccion) ?? ($data['envio'] ?? 0);
                $data['notas'] = $this->appendGuestContactToNotes($data);
                $loginAfterCheckout = $request->boolean('crear_cuenta');
            }

            $data['notas'] = $this->appendDeliveryScheduleToNotes($data);

            $pedido = $this->checkoutService->checkout(
                $cliente,
                PedidoDTO::fromCheckoutArray($data)
            );

            if ($loginAfterCheckout) {
                Auth::login($cliente);

                return redirect()->route('cliente.pedidos.show', $pedido)->with('success', 'Pedido creado correctamente. Tu cuenta quedo lista para futuras compras.');
            }

            if ($startedAsGuest) {
                $request->session()->push('guest_order_uuids', $pedido->uuid);
                $request->session()->put("guest_order_email.{$pedido->uuid}", $data['guest_email'] ?? null);

                return redirect()->route('cliente.pedidos.guest-show', $pedido)->with('success', 'Pedido creado correctamente.');
            }

            return redirect()->route('cliente.pedidos.show', $pedido)->with('success', 'Pedido creado correctamente.');
        } catch (StockInsuficienteException|PagoRechazadoException|DireccionFueraDeZonaException|TransaccionFallidaException $exception) {
            if ($startedAsGuest && $cliente instanceof User) {
                $this->carritoService->restoreUserCartToGuestSession($cliente, $request->session()->getId());
            }

            return back()
                ->withInput()
                ->with('error', $exception->publicMessage())
                ->with('error_type', class_basename($exception));
        }
    }

    /**
     * Crea el perfil minimo necesario para registrar el pedido de un visitante.
     *
     * @param array<string, mixed> $data
     * @return array{0: User, 1: Direccion}
     */
    private function prepareGuestCustomer(CheckoutRequest $request, array $data): array
    {
        $crearCuenta = $request->boolean('crear_cuenta');
        $email = $crearCuenta
            ? (string) $data['guest_email']
            : 'guest-' . Str::uuid() . '@invitados.atlantia.local';

        $cliente = User::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => (string) $data['guest_nombre'],
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $crearCuenta ? (string) $data['password'] : Str::password(48),
            'phone' => (string) $data['guest_telefono'],
            'status' => 'active',
            'is_system_user' => ! $crearCuenta,
        ]);

        if (Role::query()->where('name', 'cliente')->exists()) {
            $cliente->assignRole('cliente');
        }

        $direccion = Direccion::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $cliente->id,
            'alias' => (string) ($data['guest_alias'] ?? 'Casa'),
            'nombre_contacto' => (string) $data['guest_nombre'],
            'telefono_contacto' => (string) $data['guest_telefono'],
            'municipio' => (string) $data['guest_municipio'],
            'zona_o_barrio' => $data['guest_zona_barrio'] ?? null,
            'direccion_linea_1' => (string) $data['guest_direccion'],
            'direccion_linea_2' => null,
            'referencia' => $data['guest_referencia'] ?? null,
            'latitude' => $data['guest_latitude'],
            'longitude' => $data['guest_longitude'],
            'mapbox_place_id' => null,
            'es_principal' => true,
            'activa' => true,
        ]);

        $this->carritoService->mergeGuestCartIntoUser($request->session()->getId(), $cliente);

        return [$cliente, $direccion];
    }

    /**
     * Mantiene visible el correo real del invitado para operaciones internas.
     *
     * @param array<string, mixed> $data
     */
    private function appendGuestContactToNotes(array $data): string
    {
        $notas = trim((string) ($data['notas'] ?? ''));
        $contacto = sprintf(
            'Contacto invitado: %s, %s, %s.',
            $data['guest_nombre'] ?? 'sin nombre',
            $data['guest_email'] ?? 'sin correo',
            $data['guest_telefono'] ?? 'sin telefono'
        );

        return $notas === '' ? $contacto : $notas . "\n\n" . $contacto;
    }

    /**
     * Agrega el tipo y horario elegido a las notas visibles del pedido.
     *
     * @param array<string, mixed> $data
     */
    private function appendDeliveryScheduleToNotes(array $data): string
    {
        $notas = trim((string) ($data['notas'] ?? ''));
        $tipoEntrega = (string) ($data['tipo_entrega'] ?? 'domicilio');
        $entrega = match ($tipoEntrega) {
            'programado' => sprintf(
                'Entrega programada: %s a las %s.',
                $data['programado_fecha'] ?? 'sin fecha',
                $data['programado_hora'] ?? 'sin hora'
            ),
            'recoger' => sprintf('Tipo de entrega: recoger en tienda. Ventana: %s.', $data['ventana_entrega'] ?? 'sin ventana'),
            default => sprintf('Tipo de entrega: domicilio. Ventana: %s.', $data['ventana_entrega'] ?? 'sin ventana'),
        };

        return $notas === '' ? $entrega : $notas . "\n\n" . $entrega;
    }
}
