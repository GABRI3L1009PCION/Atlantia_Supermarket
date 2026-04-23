<?php

namespace Database\Seeders;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\Cliente\ClienteDetalle;
use App\Models\Cliente\Direccion;
use App\Models\Inventario;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder de desarrollo con datos operativos para entorno local.
 */
class DevelopmentSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategoriaSeeder::class,
            DeliveryZoneSeeder::class,
        ]);

        $superAdmin = $this->seedSuperAdmin();
        $admins = $this->seedAdmins();
        $vendors = $this->seedVendedores($admins->first());
        $repartidores = $this->seedRepartidores();
        $clientes = $this->seedClientes();

        $this->seedPedidos($clientes->all(), $vendors->all(), $repartidores->all());

        $this->command?->info('DevelopmentSeeder completado: super admin, admins, vendedores, clientes y pedidos base listos.');
    }

    /**
     * Crea super admin local.
     */
    private function seedSuperAdmin(): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'superadmin@atlantia.test'],
            [
                'uuid' => User::query()->where('email', 'superadmin@atlantia.test')->value('uuid') ?? (string) Str::uuid(),
                'name' => 'Gabriel Antonio Picon Escalante',
                'email_verified_at' => now(),
                'password' => bcrypt('Atlantia2026!'),
                'phone' => '+502 5512-3344',
                'status' => 'active',
                'is_system_user' => true,
                'two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);

        return $user;
    }

    /**
     * Crea administradores operativos.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function seedAdmins()
    {
        $rows = [
            ['Cindy Picon', 'cindy@atlantia.test', '+502 4211-1200'],
            ['Daniela Escalante', 'daniela@atlantia.test', '+502 4211-1201'],
            ['Carlos Mendez', 'carlos@atlantia.test', '+502 4211-1202'],
        ];

        return collect($rows)->map(function (array $row): User {
            $user = User::query()->updateOrCreate(
                ['email' => $row[1]],
                [
                    'uuid' => User::query()->where('email', $row[1])->value('uuid') ?? (string) Str::uuid(),
                    'name' => $row[0],
                    'email_verified_at' => now(),
                    'password' => bcrypt('Atlantia2026!'),
                    'phone' => $row[2],
                    'status' => 'active',
                    'two_factor_enabled' => true,
                    'two_factor_confirmed_at' => now(),
                ]
            );

            $user->syncRoles(['admin']);

            return $user;
        });
    }

    /**
     * Crea vendedores con catalogo inicial.
     *
     * @param User $adminAprobador
     * @return \Illuminate\Support\Collection<int, Vendor>
     */
    private function seedVendedores(User $adminAprobador)
    {
        $rows = [
            ['Panaderia La Esquina', 'vendedor1@atlantia.test', 'Puerto Barrios'],
            ['Mariscos Bahia Amatique', 'vendedor2@atlantia.test', 'Santo Tomas'],
            ['Despensa Santo Tomas', 'vendedor3@atlantia.test', 'Santo Tomas'],
            ['Lacteos La Ruidosa', 'vendedor4@atlantia.test', 'Morales'],
            ['Frutas del Caribe', 'vendedor5@atlantia.test', 'Puerto Barrios'],
        ];

        return collect($rows)->map(function (array $row, int $index) use ($adminAprobador): Vendor {
            $user = User::query()->updateOrCreate(
                ['email' => $row[1]],
                [
                    'uuid' => User::query()->where('email', $row[1])->value('uuid') ?? (string) Str::uuid(),
                    'name' => 'Vendedor ' . ($index + 1) . ' Atlantia',
                    'email_verified_at' => now(),
                    'password' => bcrypt('Atlantia2026!'),
                    'phone' => '+502 5600-12' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'status' => 'active',
                ]
            );
            $user->syncRoles(['vendedor']);

            $vendor = Vendor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => Vendor::query()->where('user_id', $user->id)->value('uuid') ?? (string) Str::uuid(),
                    'business_name' => $row[0],
                    'slug' => Str::slug($row[0]),
                    'descripcion' => 'Vendedor local habilitado para operar dentro del marketplace Atlantia.',
                    'telefono_publico' => $user->phone,
                    'email_publico' => $user->email,
                    'municipio' => $row[2],
                    'direccion_comercial' => 'Zona comercial principal de ' . $row[2],
                    'latitude' => $row[2] === 'Morales' ? 15.47250000 : 15.73090000,
                    'longitude' => $row[2] === 'Morales' ? -88.84090000 : -88.59440000,
                    'is_approved' => true,
                    'approved_by' => $adminAprobador->id,
                    'approved_at' => now()->subDays(30),
                    'status' => 'approved',
                    'commission_percentage' => 8.50,
                    'monthly_rent' => 250.00,
                    'accepts_cash' => true,
                    'accepts_transfer' => true,
                    'accepts_card' => true,
                ]
            );

            VendorFiscalProfile::query()->updateOrCreate(
                ['vendor_id' => $vendor->id],
                [
                    'nit' => '54879' . ($index + 1) . '-K',
                    'razon_social' => $row[0] . ', Sociedad Individual',
                    'nombre_comercial_sat' => $row[0],
                    'direccion_fiscal' => 'Izabal, Guatemala',
                    'regimen_sat' => 'general',
                    'codigo_establecimiento' => 'EST-' . ($index + 1),
                    'afiliacion_iva' => 'general',
                    'certificador_fel' => 'infile',
                    'fel_activo' => true,
                    'fel_validado_at' => now()->subDays(20),
                    'fel_validado_por' => $adminAprobador->id,
                ]
            );

            $this->seedProductosParaVendor($vendor, $index + 1);

            return $vendor;
        });
    }

    /**
     * Crea 20 productos con inventario por vendedor.
     */
    private function seedProductosParaVendor(Vendor $vendor, int $vendorNumber): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $producto = Producto::query()->updateOrCreate(
                ['sku' => sprintf('VEN%02d-%03d', $vendorNumber, $i), 'vendor_id' => $vendor->id],
                Producto::factory()->publicado()->make([
                    'vendor_id' => $vendor->id,
                    'sku' => sprintf('VEN%02d-%03d', $vendorNumber, $i),
                    'slug' => sprintf('%s-%03d', Str::slug($vendor->business_name), $i),
                ])->toArray()
            );

            Inventario::query()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual' => 30 + $i,
                    'stock_reservado' => 0,
                    'stock_minimo' => 5,
                    'stock_maximo' => 150,
                    'ultima_actualizacion' => now(),
                ]
            );
        }
    }

    /**
     * Crea clientes y perfiles.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function seedClientes()
    {
        return collect(range(1, 50))->map(function (int $index): User {
            $email = sprintf('cliente%02d@atlantia.test', $index);

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'uuid' => User::query()->where('email', $email)->value('uuid') ?? (string) Str::uuid(),
                    'name' => 'Cliente Atlantia ' . $index,
                    'email_verified_at' => now(),
                    'password' => bcrypt('Atlantia2026!'),
                    'phone' => '+502 5800-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'status' => 'active',
                ]
            );
            $user->syncRoles(['cliente']);

            ClienteDetalle::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'dpi' => str_pad((string) (1000000000000 + $index), 13, '0', STR_PAD_LEFT),
                    'telefono' => $user->phone,
                    'fecha_nacimiento' => now()->subYears(20)->subDays($index),
                    'genero' => 'prefiero_no_decir',
                    'preferencias' => ['categoria_favorita' => 'abarrotes'],
                    'acepta_marketing' => true,
                    'terminos_aceptados_at' => now(),
                    'privacidad_aceptada_at' => now(),
                ]
            );

            Direccion::query()->updateOrCreate(
                ['user_id' => $user->id, 'alias' => 'Casa'],
                [
                    'uuid' => Direccion::query()->where('user_id', $user->id)->where('alias', 'Casa')->value('uuid')
                        ?? (string) Str::uuid(),
                    'nombre_contacto' => $user->name,
                    'telefono_contacto' => $user->phone,
                    'municipio' => $index % 2 === 0 ? 'Puerto Barrios' : 'Santo Tomas',
                    'zona_o_barrio' => $index % 2 === 0 ? 'Centro' : 'San Agustin',
                    'direccion_linea_1' => '5a avenida ' . $index . '-45',
                    'direccion_linea_2' => null,
                    'referencia' => 'Casa color vino junto a la tienda de barrio.',
                    'latitude' => $index % 2 === 0 ? 15.73090000 : 15.69690000,
                    'longitude' => $index % 2 === 0 ? -88.59440000 : -88.62060000,
                    'mapbox_place_id' => null,
                    'es_principal' => true,
                    'activa' => true,
                ]
            );

            return $user;
        });
    }

    /**
     * Crea repartidores base.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function seedRepartidores()
    {
        $rows = [
            ['Tony Picon', 'repartidor1@atlantia.test'],
            ['Luis Ramirez', 'repartidor2@atlantia.test'],
        ];

        return collect($rows)->map(function (array $row, int $index): User {
            $user = User::query()->updateOrCreate(
                ['email' => $row[1]],
                [
                    'uuid' => User::query()->where('email', $row[1])->value('uuid') ?? (string) Str::uuid(),
                    'name' => $row[0],
                    'email_verified_at' => now(),
                    'password' => bcrypt('Atlantia2026!'),
                    'phone' => '+502 5900-22' . $index,
                    'status' => 'active',
                ]
            );
            $user->syncRoles(['repartidor']);

            return $user;
        });
    }

    /**
     * Genera pedidos historicos para clientes.
     *
     * @param array<int, User> $clientes
     * @param array<int, Vendor> $vendors
     * @param array<int, User> $repartidores
     */
    private function seedPedidos(array $clientes, array $vendors, array $repartidores): void
    {
        foreach ($clientes as $clienteIndex => $cliente) {
            $direccion = $cliente->direcciones()->first();

            if ($direccion === null) {
                continue;
            }

            $pedidoCount = 3 + ($clienteIndex % 8);

            for ($i = 1; $i <= $pedidoCount; $i++) {
                $vendor = $vendors[($clienteIndex + $i) % count($vendors)];
                $productos = $vendor->productos()->limit(2)->get();
                $subtotal = $productos->sum('precio_base');
                $envio = 18.00;
                $impuestos = round($subtotal * 0.12, 2);
                $total = round($subtotal + $envio + $impuestos, 2);

                $pedido = Pedido::query()->updateOrCreate(
                    ['numero_pedido' => sprintf('ATL-DEV-%03d-%02d', $clienteIndex + 1, $i)],
                    [
                        'uuid' => Pedido::query()->where('numero_pedido', sprintf('ATL-DEV-%03d-%02d', $clienteIndex + 1, $i))
                            ->value('uuid') ?? (string) Str::uuid(),
                        'pedido_padre_id' => null,
                        'cliente_id' => $cliente->id,
                        'vendor_id' => $vendor->id,
                        'direccion_id' => $direccion->id,
                        'subtotal' => $subtotal,
                        'envio' => $envio,
                        'impuestos' => $impuestos,
                        'descuento' => 0,
                        'total' => $total,
                        'estado' => EstadoPedido::Entregado->value,
                        'metodo_pago' => MetodoPago::Efectivo->value,
                        'estado_pago' => EstadoPago::Pagado->value,
                        'notas' => 'Pedido de desarrollo para navegacion local.',
                        'confirmado_at' => now()->subDays(5),
                    ]
                );

                foreach ($productos as $producto) {
                    PedidoItem::query()->updateOrCreate(
                        ['pedido_id' => $pedido->id, 'producto_id' => $producto->id],
                        [
                            'producto_nombre_snapshot' => $producto->nombre,
                            'producto_sku_snapshot' => $producto->sku,
                            'cantidad' => 1,
                            'precio_unitario_snapshot' => $producto->precio_base,
                            'subtotal' => $producto->precio_base,
                            'descuento' => 0,
                            'impuestos' => round(((float) $producto->precio_base) * 0.12, 2),
                        ]
                    );
                }

                Payment::query()->updateOrCreate(
                    ['pedido_id' => $pedido->id, 'metodo' => MetodoPago::Efectivo->value],
                    [
                        'uuid' => Payment::query()->where('pedido_id', $pedido->id)->value('uuid') ?? (string) Str::uuid(),
                        'monto' => $pedido->total,
                        'estado' => EstadoPago::Aprobado->value,
                        'transaccion_id_pasarela' => 'cash_' . Str::lower(Str::random(10)),
                        'hmac_validado' => true,
                        'validado_at' => now()->subDays(5),
                        'pasarela_payload' => ['gateway' => 'cash'],
                    ]
                );
            }
        }
    }
}
