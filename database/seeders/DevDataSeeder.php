<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrito;
use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cliente\ClienteDetalle;
use App\Models\Cliente\Direccion;
use App\Models\ContactMessage;
use App\Models\DeliveryRoute;
use App\Models\DeliveryZone;
use App\Models\Dte\DteFactura;
use App\Models\Dte\DteItem;
use App\Models\Inventario;
use App\Models\MarketCourierStatus;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Resena;
use App\Models\ResenaImagen;
use App\Models\SentEmail;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDeliveryZone;
use App\Models\VendorFiscalProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder de datos funcionales de desarrollo.
 */
class DevDataSeeder extends Seeder
{
    /**
     * Ejecuta el seeder de datos navegables.
     */
    public function run(): void
    {
        $admin = $this->user('admin@atlantia.test');
        $cliente = $this->user('cliente@atlantia.test');
        $repartidor = $this->user('repartidor@atlantia.test');
        $empleado = $this->user('empleado@atlantia.test');

        $vendors = $this->createVendors($admin);
        $this->createClienteData($cliente);
        $productos = $this->createProductos($vendors);
        $pedido = $this->createCommerceFlow($cliente, $repartidor, $vendors[0], $productos);
        $this->createSocialAndSupport($cliente, $empleado, $pedido, $productos[0]);
        $this->createAuditSamples($admin, $pedido);
    }

    /**
     * Obtiene un usuario por correo.
     *
     * @param string $email
     * @return User
     */
    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    /**
     * Crea vendedores aprobados y sus perfiles fiscales.
     *
     * @param User $admin
     * @return array<int, Vendor>
     */
    private function createVendors(User $admin): array
    {
        $vendorData = [
            [
                'user' => ['name' => 'Carlos Mendez', 'email' => 'vendedor@atlantia.test', 'phone' => '+502 7948-1006'],
                'business_name' => 'Despensa Santo Tomas',
                'slug' => 'despensa-santo-tomas',
                'municipio' => 'Santo Tomás',
                'direccion' => 'Avenida principal de Santo Tomas de Castilla, local 12',
                'lat' => 15.69690000,
                'lng' => -88.62060000,
                'nit' => '8456123-4',
                'razon' => 'Despensa Santo Tomas, Sociedad Anonima',
            ],
            [
                'user' => ['name' => 'Andrea Morales', 'email' => 'mariscos@atlantia.test', 'phone' => '+502 7948-2101'],
                'business_name' => 'Mariscos Bahia Amatique',
                'slug' => 'mariscos-bahia-amatique',
                'municipio' => 'Puerto Barrios',
                'direccion' => 'Barrio El Rastro, Puerto Barrios, cerca del mercado municipal',
                'lat' => 15.73090000,
                'lng' => -88.59440000,
                'nit' => '7392041-8',
                'razon' => 'Comercial Bahia Amatique, Sociedad Anonima',
            ],
            [
                'user' => ['name' => 'Luis Barrios', 'email' => 'frutas@atlantia.test', 'phone' => '+502 7948-2102'],
                'business_name' => 'Frutas del Caribe',
                'slug' => 'frutas-del-caribe',
                'municipio' => 'Morales',
                'direccion' => 'Entrada a Morales, carretera al Atlantico, bodega 3',
                'lat' => 15.47250000,
                'lng' => -88.84090000,
                'nit' => '6891205-1',
                'razon' => 'Frutas del Caribe de Izabal',
            ],
        ];

        $vendors = [];

        foreach ($vendorData as $index => $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['user']['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('Atlantia2026!'),
                    'phone' => $data['user']['phone'],
                    'status' => 'active',
                    'is_system_user' => false,
                    'two_factor_enabled' => false,
                ]
            );
            $user->assignRole('vendedor');

            $vendor = Vendor::query()->firstOrNew(['slug' => $data['slug']]);
            $vendor->fill([
                'uuid' => $vendor->uuid ?? (string) Str::uuid(),
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'descripcion' => 'Vendedor local aprobado con productos de alta rotacion para familias de Izabal.',
                'logo_path' => 'vendors/logos/' . $data['slug'] . '.webp',
                'cover_path' => 'vendors/covers/' . $data['slug'] . '.webp',
                'telefono_publico' => $data['user']['phone'],
                'email_publico' => $data['user']['email'],
                'municipio' => $data['municipio'],
                'direccion_comercial' => $data['direccion'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'is_approved' => true,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(20 + $index),
                'suspendido_at' => null,
                'suspendido_por' => null,
                'motivo_suspension' => null,
                'status' => 'approved',
                'commission_percentage' => [7.50, 8.00, 6.50][$index],
                'monthly_rent' => [250.00, 300.00, 225.00][$index],
                'accepts_cash' => true,
                'accepts_transfer' => true,
                'accepts_card' => true,
            ]);
            $vendor->save();

            VendorFiscalProfile::query()->updateOrCreate(
                ['vendor_id' => $vendor->id],
                [
                    'nit' => $data['nit'],
                    'razon_social' => $data['razon'],
                    'nombre_comercial_sat' => $data['business_name'],
                    'direccion_fiscal' => $data['direccion'],
                    'regimen_sat' => $index === 2 ? 'pequeno_contribuyente' : 'general',
                    'codigo_establecimiento' => 'EST-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'afiliacion_iva' => 'GEN',
                    'certificador_fel' => 'infile',
                    'fel_usuario' => 'sandbox-' . $data['slug'],
                    'fel_llave_firma' => 'sandbox-firma-' . $data['slug'],
                    'fel_llave_certificador' => 'sandbox-certificador-' . $data['slug'],
                    'banco_nombre' => 'Banco Industrial',
                    'cuenta_bancaria' => 'GTQ-0001000200' . ($index + 1),
                    'cuenta_bancaria_tipo' => 'monetaria',
                    'cuenta_bancaria_titular' => $data['razon'],
                    'fel_activo' => true,
                    'fel_validado_at' => now()->subDays(15),
                    'fel_validado_por' => $admin->id,
                ]
            );

            foreach (DeliveryZone::query()->where('activa', true)->limit(3)->get() as $zone) {
                VendorDeliveryZone::query()->updateOrCreate(
                    ['vendor_id' => $vendor->id, 'delivery_zone_id' => $zone->id],
                    [
                        'costo_override' => $zone->costo_base,
                        'tiempo_estimado_min' => match ($zone->municipio) {
                            'Puerto Barrios', 'Santo Tomás' => 35,
                            'Morales' => 75,
                            default => 90,
                        },
                        'activa' => true,
                    ]
                );
            }

            $vendors[] = $vendor;
        }

        return $vendors;
    }

    /**
     * Crea perfil y direccion principal del cliente demo.
     *
     * @param User $cliente
     */
    private function createClienteData(User $cliente): void
    {
        ClienteDetalle::query()->updateOrCreate(
            ['user_id' => $cliente->id],
            [
                'dpi' => '1801 12345 1801',
                'telefono' => '+502 7948-1005',
                'fecha_nacimiento' => '1998-08-14',
                'genero' => 'femenino',
                'preferencias' => ['mariscos', 'frutas frescas', 'entrega tarde'],
                'acepta_marketing' => true,
                'terminos_aceptados_at' => now()->subDays(30),
                'privacidad_aceptada_at' => now()->subDays(30),
            ]
        );

        $direccion = Direccion::query()->firstOrNew(['user_id' => $cliente->id, 'alias' => 'Casa']);
        $direccion->fill([
            'uuid' => $direccion->uuid ?? (string) Str::uuid(),
            'nombre_contacto' => 'Mariela Castillo',
            'telefono_contacto' => '+502 7948-1005',
            'municipio' => 'Puerto Barrios',
            'zona_o_barrio' => 'Barrio El Centro',
            'direccion_linea_1' => '6a avenida entre 7a y 8a calle, Puerto Barrios',
            'direccion_linea_2' => 'Casa color celeste con porton negro',
            'referencia' => 'A tres cuadras del parque central.',
            'latitude' => 15.73090000,
            'longitude' => -88.59440000,
            'mapbox_place_id' => 'mapbox.pb.centro.demo',
            'es_principal' => true,
            'activa' => true,
        ]);
        $direccion->save();
    }

    /**
     * Crea productos, imagenes e inventario.
     *
     * @param array<int, Vendor> $vendors
     * @return array<int, Producto>
     */
    private function createProductos(array $vendors): array
    {
        $productosData = [
            ['sku' => 'ATL-ABA-001', 'vendor' => 0, 'cat' => 'granos-basicos', 'nombre' => 'Frijol negro de Oriente 2 lb', 'precio' => 18.50, 'stock' => 90],
            ['sku' => 'ATL-ABA-002', 'vendor' => 0, 'cat' => 'granos-basicos', 'nombre' => 'Arroz blanco nacional 5 lb', 'precio' => 32.00, 'stock' => 75],
            ['sku' => 'ATL-MAR-001', 'vendor' => 1, 'cat' => 'mariscos-atlantico', 'nombre' => 'Camaron fresco del Atlantico 1 lb', 'precio' => 58.00, 'stock' => 24],
            ['sku' => 'ATL-MAR-002', 'vendor' => 1, 'cat' => 'mariscos-atlantico', 'nombre' => 'Filete de robalo fresco 1 lb', 'precio' => 64.00, 'stock' => 18],
            ['sku' => 'ATL-FRU-001', 'vendor' => 2, 'cat' => 'frutas-caribe', 'nombre' => 'Banano criollo por docena', 'precio' => 12.00, 'stock' => 120],
            ['sku' => 'ATL-FRU-002', 'vendor' => 2, 'cat' => 'frutas-caribe', 'nombre' => 'Pina dulce de Morales unidad', 'precio' => 16.00, 'stock' => 45],
        ];

        $productos = [];

        foreach ($productosData as $index => $data) {
            $categoria = Categoria::query()->where('slug', $data['cat'])->firstOrFail();
            $vendor = $vendors[$data['vendor']];
            $slug = Str::slug($data['nombre']);

            $producto = Producto::query()->firstOrNew(['vendor_id' => $vendor->id, 'sku' => $data['sku']]);
            $producto->fill([
                'uuid' => $producto->uuid ?? (string) Str::uuid(),
                'categoria_id' => $categoria->id,
                'nombre' => $data['nombre'],
                'slug' => $slug,
                'descripcion' => 'Producto local seleccionado para compras familiares en Atlantia Supermarket.',
                'precio_base' => $data['precio'],
                'precio_oferta' => $index % 3 === 0 ? $data['precio'] - 2 : null,
                'peso_gramos' => str_contains($data['nombre'], 'lb') ? 454 : null,
                'unidad_medida' => str_contains($data['nombre'], 'docena') ? 'docena' : 'unidad',
                'requiere_refrigeracion' => str_contains($data['nombre'], 'fresco'),
                'is_active' => true,
                'visible_catalogo' => true,
                'publicado_at' => now()->subDays(10),
            ]);
            $producto->save();

            ProductoImagen::query()->updateOrCreate(
                ['producto_id' => $producto->id, 'orden' => 1],
                [
                    'path' => 'productos/' . $slug . '.webp',
                    'alt_text' => $data['nombre'],
                    'es_principal' => true,
                ]
            );

            Inventario::query()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => $data['stock'],
                    'stock_reservado' => 2,
                    'stock_minimo' => 10,
                    'stock_maximo' => 180,
                    'ultima_actualizacion' => now(),
                ]
            );

            $productos[] = $producto;
        }

        return $productos;
    }

    /**
     * Crea un flujo de comercio completo y navegable.
     *
     * @param User $cliente
     * @param User $repartidor
     * @param Vendor $vendor
     * @param array<int, Producto> $productos
     * @return Pedido
     */
    private function createCommerceFlow(User $cliente, User $repartidor, Vendor $vendor, array $productos): Pedido
    {
        $direccion = Direccion::query()->where('user_id', $cliente->id)->firstOrFail();
        $producto = $productos[0];
        $subtotal = 2 * (float) $producto->precio_base;
        $envio = 18.00;
        $impuestos = round($subtotal * 0.12, 2);
        $total = $subtotal + $envio + $impuestos;

        $carrito = Carrito::query()->firstOrNew(['user_id' => $cliente->id, 'estado' => 'activo']);
        $carrito->fill([
            'uuid' => $carrito->uuid ?? (string) Str::uuid(),
            'session_id' => null,
            'expira_at' => now()->addDays(7),
        ]);
        $carrito->save();

        CarritoItem::query()->updateOrCreate(
            ['carrito_id' => $carrito->id, 'producto_id' => $producto->id],
            ['cantidad' => 2, 'precio_unitario_snapshot' => $producto->precio_base]
        );

        $pedido = Pedido::query()->firstOrNew(['numero_pedido' => 'ATL-20260418-0001']);
        $pedido->fill([
            'uuid' => $pedido->uuid ?? (string) Str::uuid(),
            'pedido_padre_id' => null,
            'cliente_id' => $cliente->id,
            'vendor_id' => $vendor->id,
            'direccion_id' => $direccion->id,
            'subtotal' => $subtotal,
            'envio' => $envio,
            'impuestos' => $impuestos,
            'descuento' => 0.00,
            'total' => $total,
            'estado' => 'entregado',
            'metodo_pago' => 'tarjeta',
            'estado_pago' => 'pagado',
            'notas' => 'Pedido demo entregado en Puerto Barrios centro.',
            'confirmado_at' => now()->subDays(2),
            'cancelado_at' => null,
        ]);
        $pedido->save();

        PedidoEstado::query()->updateOrCreate(
            ['pedido_id' => $pedido->id, 'estado' => 'entregado'],
            ['notas' => 'Entrega completada con evidencia digital.', 'usuario_id' => $repartidor->id]
        );

        $item = \App\Models\PedidoItem::query()->updateOrCreate(
            ['pedido_id' => $pedido->id, 'producto_id' => $producto->id],
            [
                'producto_nombre_snapshot' => $producto->nombre,
                'producto_sku_snapshot' => $producto->sku,
                'cantidad' => 2,
                'precio_unitario_snapshot' => $producto->precio_base,
                'subtotal' => $subtotal,
                'descuento' => 0.00,
                'impuestos' => $impuestos,
            ]
        );

        $payment = Payment::query()->firstOrNew(['pedido_id' => $pedido->id, 'transaccion_id_pasarela' => 'ATL-PAY-0001']);
        $payment->fill([
            'uuid' => $payment->uuid ?? (string) Str::uuid(),
            'metodo' => 'tarjeta',
            'monto' => $total,
            'estado' => 'aprobado',
            'hmac_validado' => true,
            'referencia_bancaria' => null,
            'validado_por' => null,
            'validado_at' => now()->subDays(2),
            'pasarela_payload' => ['gateway' => 'mock-visanet', 'authorization' => 'APROBADA'],
        ]);
        $payment->save();

        PaymentSplit::query()->updateOrCreate(
            ['payment_id' => $payment->id, 'vendor_id' => $vendor->id],
            [
                'monto_bruto' => $total,
                'comision_atlantia' => round($total * 0.075, 2),
                'monto_neto_vendedor' => round($total * 0.925, 2),
                'estado' => 'liquidado',
                'liquidado_at' => now()->subDay(),
            ]
        );

        $dte = DteFactura::query()->firstOrNew(['numero_dte' => 'DTE-2026-000001']);
        $dte->fill([
            'uuid' => $dte->uuid ?? (string) Str::uuid(),
            'pedido_id' => $pedido->id,
            'vendor_id' => $vendor->id,
            'uuid_sat' => $dte->uuid_sat ?? (string) Str::uuid(),
            'serie' => 'ATL-A',
            'numero' => 1,
            'tipo_dte' => 'FACT',
            'monto_neto' => $subtotal,
            'monto_iva' => $impuestos,
            'monto_total' => $total,
            'moneda' => 'GTQ',
            'xml_dte' => '<dte><serie>ATL-A</serie><numero>1</numero><moneda>GTQ</moneda></dte>',
            'pdf_path' => 'dte/facturas/2026/04/dte-2026-000001.pdf',
            'estado' => 'certificado',
            'fecha_certificacion' => now()->subDays(2),
            'certificador_respuesta' => ['certificador' => 'INFILE', 'ambiente' => 'sandbox', 'resultado' => 'certificado'],
        ]);
        $dte->save();

        DteItem::query()->updateOrCreate(
            ['dte_id' => $dte->id, 'producto_id' => $producto->id],
            [
                'descripcion' => $item->producto_nombre_snapshot,
                'cantidad' => 2,
                'precio_unitario' => $producto->precio_base,
                'descuento' => 0.00,
                'monto_iva' => $impuestos,
                'monto_total' => $subtotal + $impuestos,
            ]
        );

        $pedido->update(['dte_id' => $dte->id]);

        $deliveryRoute = DeliveryRoute::query()->firstOrNew(['pedido_id' => $pedido->id]);
        $deliveryRoute->fill([
            'uuid' => $deliveryRoute->uuid ?? (string) Str::uuid(),
            'repartidor_id' => $repartidor->id,
            'ruta_planificada' => ['origen' => 'Despensa Santo Tomas', 'destino' => 'Puerto Barrios Centro'],
            'ruta_real' => ['puntos' => [[15.69690000, -88.62060000], [15.73090000, -88.59440000]]],
            'distancia_km' => 6.40,
            'tiempo_estimado_min' => 32,
            'tiempo_real_min' => 35,
            'estado' => 'completada',
            'asignada_at' => now()->subDays(2)->subHours(2),
            'iniciada_at' => now()->subDays(2)->subHour(),
            'completada_at' => now()->subDays(2),
            'firma_path' => 'entregas/firmas/atl-20260418-0001.png',
            'foto_entrega_path' => 'entregas/fotos/atl-20260418-0001.webp',
        ]);
        $deliveryRoute->save();

        MarketCourierStatus::query()->updateOrCreate(
            [
                'repartidor_id' => $repartidor->id,
                'pedido_id' => $pedido->id,
                'timestamp_gps' => now()->subDays(2)->setTime(14, 30),
            ],
            [
                'latitude' => 15.73090000,
                'longitude' => -88.59440000,
                'estado' => 'entregando',
                'battery_level' => 76,
                'accuracy_meters' => 8.50,
                'notas' => 'Ubicacion final capturada al entregar.',
            ]
        );

        return $pedido;
    }

    /**
     * Crea resenas y mensajes de soporte.
     *
     * @param User $cliente
     * @param User $empleado
     * @param Pedido $pedido
     * @param Producto $producto
     */
    private function createSocialAndSupport(User $cliente, User $empleado, Pedido $pedido, Producto $producto): void
    {
        $resena = Resena::query()->firstOrNew([
            'producto_id' => $producto->id,
            'cliente_id' => $cliente->id,
            'pedido_id' => $pedido->id,
        ]);
        $resena->fill([
            'uuid' => $resena->uuid ?? (string) Str::uuid(),
            'calificacion' => 5,
            'titulo' => 'Buen producto y entrega puntual',
            'contenido' => 'El producto llego bien empacado y fresco. La entrega fue puntual en Puerto Barrios.',
            'imagenes_count' => 1,
            'aprobada' => true,
            'flagged_ml' => false,
            'moderada_por' => $empleado->id,
            'moderada_at' => now()->subDay(),
        ]);
        $resena->save();

        ResenaImagen::query()->updateOrCreate(
            ['resena_id' => $resena->id, 'orden' => 1],
            ['path' => 'resenas/frijol-negro-entrega.webp']
        );

        $contactMessage = ContactMessage::query()->firstOrNew([
            'email' => 'cliente@atlantia.test',
            'asunto' => 'Consulta sobre horario de entrega',
        ]);
        $contactMessage->fill([
            'uuid' => $contactMessage->uuid ?? (string) Str::uuid(),
            'user_id' => $cliente->id,
            'nombre' => 'Mariela Castillo',
            'telefono' => '+502 7948-1005',
            'mensaje' => 'Quisiera confirmar si entregan despues de las 5 de la tarde en Puerto Barrios centro.',
            'atendido' => true,
            'atendido_por' => $empleado->id,
            'atendido_at' => now()->subHours(6),
            'prioridad' => 'normal',
        ]);
        $contactMessage->save();

        SentEmail::query()->updateOrCreate(
            ['uuid' => '00000000-0000-4000-8000-000000000101'],
            [
                'user_id' => $cliente->id,
                'to' => 'cliente@atlantia.test',
                'subject' => 'Tu pedido ATL-20260418-0001 fue entregado',
                'template' => 'pedido-entregado',
                'status' => 'sent',
                'error' => null,
                'metadata' => ['pedido' => 'ATL-20260418-0001'],
                'sent_at' => now()->subDays(2),
            ]
        );
    }

    /**
     * Crea eventos minimos de auditoria.
     *
     * @param User $admin
     * @param Pedido $pedido
     */
    private function createAuditSamples(User $admin, Pedido $pedido): void
    {
        AuditLog::query()->updateOrCreate(
            ['uuid' => '00000000-0000-4000-8000-000000000201'],
            [
                'user_id' => $admin->id,
                'event' => 'pedido.demo_creado',
                'auditable_type' => Pedido::class,
                'auditable_id' => $pedido->id,
                'old_values' => null,
                'new_values' => ['estado' => $pedido->estado, 'total' => $pedido->total],
                'metadata' => ['origen' => 'DevDataSeeder'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder Atlantia',
                'request_id' => 'seed-dev-data',
                'url' => '/seeders/dev-data',
                'method' => 'SEED',
            ]
        );
    }
}
