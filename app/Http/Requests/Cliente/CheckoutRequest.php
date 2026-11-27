<?php

namespace App\Http\Requests\Cliente;

use App\Rules\ContrasenaSegura;
use App\Support\CoberturaColonias;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validacion para finalizar compra.
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Determina si el cliente puede ejecutar checkout.
     */
    public function authorize(): bool
    {
        return $this->user() === null
            || $this->user()->hasRole('cliente')
            || $this->user()->can('checkout');
    }

    /**
     * Reglas de validacion del checkout.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guest = $this->user() === null;
        $crearCuenta = $this->boolean('crear_cuenta');

        return [
            'direccion_id' => [
                Rule::requiredIf(! $guest),
                'nullable',
                Rule::exists('direcciones', 'id')->where('user_id', $this->user()?->id)->where('activa', true),
            ],
            'guest_nombre' => [Rule::requiredIf($guest), 'nullable', 'string', 'min:3', 'max:160'],
            /* El correo solo es obligatorio si el visitante quiere crear su cuenta. */
            'guest_email' => [
                Rule::requiredIf($guest && $crearCuenta),
                'nullable',
                'email',
                'max:190',
                ...($crearCuenta ? [Rule::unique('users', 'email')] : []),
            ],
            'guest_telefono' => [Rule::requiredIf($guest), 'nullable', 'regex:/^(\+502)?[2-7][0-9]{7}$/'],
            'guest_alias' => ['nullable', 'string', 'max:80'],
            'guest_municipio' => [Rule::requiredIf($guest), 'nullable', Rule::in(self::zonasEntrega())],
            'guest_zona_barrio' => ['nullable', 'string', 'max:160'],
            'guest_direccion' => [Rule::requiredIf($guest), 'nullable', 'string', 'min:8', 'max:500'],
            'guest_referencia' => ['nullable', 'string', 'max:600'],
            /* La ubicacion GPS es opcional: sin ella se usa la zona del municipio elegido. */
            'guest_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:guest_longitude'],
            'guest_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:guest_latitude'],
            'crear_cuenta' => ['nullable', 'boolean'],
            'password' => [Rule::requiredIf($guest && $crearCuenta), 'nullable', 'confirmed', new ContrasenaSegura],
            'tipo_entrega' => ['required', Rule::in(['domicilio', 'recoger', 'programado'])],
            /* Domicilio y recoger salen lo antes posible; solo "programado" elige dia y horario. */
            'ventana_entrega' => [
                'required_if:tipo_entrega,programado',
                'nullable',
                Rule::in(array_keys(self::ventanasProgramadas())),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('tipo_entrega') !== 'programado' || $value === null) {
                        return;
                    }
                    $inicio = self::ventanasProgramadas()[$value] ?? null;
                    if ($inicio === null || $this->input('programado_fecha') !== now()->toDateString()) {
                        return;
                    }
                    /* Para hoy, el horario debe empezar al menos 1 hora despues de ahora. */
                    $minimo = now()->addHour();
                    $horaMinima = $minimo->isSameDay(now())
                        ? $minimo->hour + ($minimo->minute > 0 || $minimo->second > 0 ? 1 : 0)
                        : 24;
                    if ($inicio < $horaMinima) {
                        $fail('Ese horario ya no esta disponible para hoy. Elige otro horario o dia.');
                    }
                },
            ],
            'programado_fecha' => ['required_if:tipo_entrega,programado', 'nullable', 'date', 'after_or_equal:today', 'before_or_equal:'.now()->addDays(14)->toDateString()],
            'programado_hora' => ['nullable', 'date_format:H:i'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'transferencia', 'tarjeta'])],
            'envio' => ['nullable', 'numeric', 'min:0', 'max:9999.99', 'decimal:0,2'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'propina' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'usar_saldo' => ['nullable', 'boolean'],
            'usar_tarjeta_guardada' => ['nullable', 'boolean'],
            'referencia_bancaria' => ['nullable', 'string', 'max:120'],
            'solicita_cambio' => ['nullable', 'boolean'],
            'cambio_para' => ['required_if:solicita_cambio,1', 'nullable', 'numeric', 'min:1', 'max:9999.99', 'decimal:0,2'],
            'comprobante_path' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'facturacion_tipo' => ['required', Rule::in(['datos', 'cf'])],
            'nit_facturacion' => ['nullable', 'string', 'max:40'],
            'razon_social' => ['nullable', 'string', 'max:180'],
            'correo_facturacion' => ['required_if:facturacion_tipo,datos', 'nullable', 'email', 'max:190'],
            'acepta_terminos_checkout' => ['accepted'],
        ];
    }

    /**
     * Mensajes personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direccion_id.required' => 'Selecciona una direccion de entrega.',
            'direccion_id.exists' => 'La direccion seleccionada no existe o no pertenece a tu cuenta.',
            'guest_nombre.required' => 'Ingresa tu nombre para coordinar la entrega.',
            'guest_email.required' => 'Ingresa tu correo para crear tu cuenta.',
            'guest_email.email' => 'Ingresa un correo valido.',
            'guest_email.unique' => 'Este correo ya tiene cuenta. Inicia sesion o usa otro correo para crear la cuenta.',
            'guest_telefono.required' => 'Ingresa un telefono de contacto.',
            'guest_telefono.regex' => 'Ingresa un telefono de Guatemala valido (ej. 55551234).',
            'guest_municipio.required' => 'Elige si la entrega es en Puerto Barrios o Santo Tomas.',
            'guest_municipio.in' => 'Por ahora solo entregamos en Puerto Barrios y Santo Tomas.',
            'guest_direccion.required' => 'Escribe la direccion completa de entrega.',
            'guest_latitude.required' => 'Captura tu ubicacion exacta para validar cobertura.',
            'password.required' => 'Ingresa una contrasena para crear tu cuenta.',
            'password.confirmed' => 'La confirmacion de contrasena no coincide.',
            'tipo_entrega.required' => 'Selecciona el tipo de entrega.',
            'tipo_entrega.in' => 'El tipo de entrega seleccionado no esta disponible.',
            'ventana_entrega.required_if' => 'Elige el horario de tu entrega programada.',
            'ventana_entrega.in' => 'Elige uno de los horarios disponibles.',
            'programado_fecha.required_if' => 'Elige el dia de tu entrega programada.',
            'programado_fecha.after_or_equal' => 'La fecha programada no puede ser anterior a hoy.',
            'programado_fecha.before_or_equal' => 'Solo puedes programar pedidos hasta 14 dias adelante.',
            'programado_hora.required_if' => 'Selecciona la hora del pedido programado.',
            'programado_hora.date_format' => 'Selecciona una hora valida.',
            'metodo_pago.required' => 'Selecciona un metodo de pago.',
            'metodo_pago.in' => 'El metodo de pago seleccionado no esta disponible.',
            'envio.numeric' => 'El costo de envio debe ser numerico.',
            'envio.max' => 'El costo de envio no puede superar Q:max.',
            'notas.max' => 'Las notas no deben superar :max caracteres.',
            'cambio_para.required_if' => 'Indica para cuanto efectivo necesitas cambio.',
            'cambio_para.numeric' => 'La denominacion para cambio debe ser numerica.',
            'coupon_code.max' => 'El codigo del cupon no debe superar :max caracteres.',
            'correo_facturacion.required_if' => 'Ingresa el correo donde enviaremos la factura o comprobante.',
            'correo_facturacion.email' => 'Ingresa un correo de facturacion valido.',
            'acepta_terminos_checkout.accepted' => 'Debes aceptar las condiciones de compra.',
        ];
    }

    /**
     * Atributos legibles.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'direccion_id' => 'direccion de entrega',
            'guest_nombre' => 'nombre',
            'guest_email' => 'correo electronico',
            'guest_telefono' => 'telefono',
            'guest_municipio' => 'municipio',
            'guest_direccion' => 'direccion de entrega',
            'guest_latitude' => 'ubicacion exacta',
            'guest_longitude' => 'ubicacion exacta',
            'password' => 'contrasena',
            'tipo_entrega' => 'tipo de entrega',
            'ventana_entrega' => 'ventana de entrega',
            'programado_fecha' => 'fecha programada',
            'programado_hora' => 'hora programada',
            'metodo_pago' => 'metodo de pago',
            'envio' => 'costo de envio',
            'notas' => 'notas del pedido',
            'referencia_bancaria' => 'referencia bancaria',
            'solicita_cambio' => 'solicitud de cambio',
            'cambio_para' => 'cambio para billete',
            'comprobante_path' => 'comprobante de transferencia',
            'coupon_code' => 'codigo de cupon',
            'facturacion_tipo' => 'tipo de facturacion',
            'nit_facturacion' => 'NIT',
            'razon_social' => 'nombre o razon social',
            'correo_facturacion' => 'correo de facturacion',
            'acepta_terminos_checkout' => 'aceptacion de condiciones de compra',
        ];
    }

    /**
     * Normaliza campos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $facturacionTipo = $this->input('facturacion_tipo') === 'datos' ? 'datos' : 'cf';

        /* Colonias que pertenecen a otro municipio (ej. Quebrada Seca y San Agustin son de Santo Tomas). */
        $municipioColonia = CoberturaColonias::municipioDe($this->input('guest_zona_barrio'));
        if ($municipioColonia !== null && in_array($municipioColonia, self::zonasEntrega(), true)) {
            $this->merge(['guest_municipio' => $municipioColonia]);
        }

        $this->merge([
            'metodo_pago' => trim((string) $this->input('metodo_pago')),
            'tipo_entrega' => trim((string) $this->input('tipo_entrega', 'domicilio')),
            'ventana_entrega' => $this->blankToNull($this->input('ventana_entrega')),
            'programado_fecha' => $this->blankToNull($this->input('programado_fecha')),
            'programado_hora' => $this->blankToNull($this->input('programado_hora')),
            'envio' => $this->input('envio') === null ? 0 : str_replace(',', '.', (string) $this->input('envio')),
            'notas' => $this->blankToNull($this->input('notas')),
            'solicita_cambio' => $this->boolean('solicita_cambio'),
            'cambio_para' => $this->input('cambio_para') === null ? null : str_replace(',', '.', (string) $this->input('cambio_para')),
            'guest_nombre' => $this->blankToNull($this->input('guest_nombre')),
            'guest_email' => $this->blankToNull($this->input('guest_email')),
            'guest_telefono' => $this->blankToNull(preg_replace('/[\s\-]/', '', (string) $this->input('guest_telefono'))),
            'guest_alias' => $this->blankToNull($this->input('guest_alias')) ?? 'Casa',
            'guest_zona_barrio' => $this->blankToNull($this->input('guest_zona_barrio')),
            'guest_direccion' => $this->blankToNull($this->input('guest_direccion')),
            'guest_referencia' => $this->blankToNull($this->input('guest_referencia')),
            'referencia_bancaria' => $this->blankToNull($this->input('referencia_bancaria')),
            'coupon_code' => $this->blankToNull($this->input('coupon_code')),
            'facturacion_tipo' => $facturacionTipo,
            'nit_facturacion' => $this->blankToNull($facturacionTipo === 'cf' ? 'CF' : $this->input('nit_facturacion')),
            'razon_social' => $this->billingName($facturacionTipo),
            'correo_facturacion' => $this->billingEmail(),
        ]);
    }

    /**
     * Horarios para entregas programadas: etiqueta => hora de inicio (24 h).
     *
     * @return array<string, int>
     */
    public static function ventanasProgramadas(): array
    {
        return [
            '8-10 am' => 8,
            '10 am-12 pm' => 10,
            '12-2 pm' => 12,
            '2-4 pm' => 14,
            '4-6 pm' => 16,
            '6-8 pm' => 18,
        ];
    }

    /**
     * Zonas donde opera la tienda (Puerto Barrios y Santo Tomas por defecto).
     *
     * @return array<int, string>
     */
    public static function zonasEntrega(): array
    {
        $zonas = (array) config('atlantia.marketplace.municipios', []);

        return $zonas === [] ? ['Puerto Barrios', 'Santo Tomas'] : array_values($zonas);
    }

    /**
     * Convierte cadenas vacias a null.
     */
    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Resuelve el correo real para enviar el PDF fiscal.
     */
    private function billingEmail(): ?string
    {
        return $this->blankToNull($this->input('correo_facturacion'))
            ?? $this->blankToNull($this->input('guest_email'))
            ?? $this->user()?->email;
    }

    /**
     * Resuelve el nombre fiscal visible en el PDF.
     */
    private function billingName(string $facturacionTipo): ?string
    {
        if ($facturacionTipo === 'cf') {
            return 'Consumidor final';
        }

        return $this->blankToNull($this->input('razon_social'))
            ?? $this->blankToNull($this->input('guest_nombre'))
            ?? $this->user()?->name;
    }
}
