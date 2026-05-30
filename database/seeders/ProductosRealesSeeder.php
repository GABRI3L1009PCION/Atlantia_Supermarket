<?php

namespace Database\Seeders;

use App\Models\Inventario;
use App\Models\ProductoImagen;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductosRealesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->productos() as $data) {
            $slug = Str::slug($data['nombre']) . '-' . strtolower(substr($data['sku'], -4));

            $producto = Producto::query()->updateOrCreate(
                ['vendor_id' => $data['vendor_id'], 'sku' => $data['sku']],
                [
                    'uuid'                  => Producto::query()->where('sku', $data['sku'])->where('vendor_id', $data['vendor_id'])->value('uuid') ?? (string) Str::uuid(),
                    'categoria_id'          => $data['categoria_id'],
                    'nombre'                => $data['nombre'],
                    'slug'                  => $slug,
                    'descripcion'           => $data['descripcion'],
                    'precio_base'           => $data['precio'],
                    'precio_oferta'         => $data['precio_oferta'] ?? null,
                    'peso_gramos'           => $data['peso_gramos'] ?? null,
                    'unidad_medida'         => $data['unidad'],
                    'requiere_refrigeracion' => $data['refrigeracion'] ?? false,
                    'is_active'             => true,
                    'visible_catalogo'      => true,
                    'publicado_at'          => now()->subDays(rand(1, 45)),
                    'imagen'                => $data['imagen'],
                ]
            );

            ProductoImagen::query()->updateOrCreate(
                ['producto_id' => $producto->id, 'es_principal' => true],
                [
                    'path'       => $data['imagen'],
                    'alt_text'   => $data['nombre'],
                    'orden'      => 0,
                    'es_principal' => true,
                ]
            );

            Inventario::query()->updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'stock_actual'        => $data['stock'] ?? rand(25, 120),
                    'stock_reservado'     => 0,
                    'stock_minimo'        => $data['stock_min'] ?? 5,
                    'stock_maximo'        => $data['stock_max'] ?? 200,
                    'ultima_actualizacion' => now(),
                ]
            );
        }

        $this->command?->info('ProductosRealesSeeder: 100 productos reales creados correctamente.');
    }

    private function img(string $id): string
    {
        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w=600&q=80";
    }

    private function productos(): array
    {
        // Vendor IDs: 4=Panadería La Esquina | 5=Mariscos Bahia Amatique
        //             6=Despensa Santo Tomás  | 7=Lácteos La Ruidosa | 8=Frutas del Caribe
        // Categoría IDs relevantes:
        //  1=Abarrotes básicos | 6=Lácteos y huevos | 7=Carnes, pollo y embutidos
        //  8=Pescados y mariscos | 9=Frutas | 10=Verduras y hortalizas
        // 11=Panadería y tortillería | 12=Snacks y golosinas | 13=Bebidas sin alcohol
        // 16=Limpieza del hogar | 17=Papel, bolsas y desechables

        return [

            // ─────────────────────────────────────────────────────────────
            // PANADERÍA LA ESQUINA  (vendor_id=4)  20 productos
            // ─────────────────────────────────────────────────────────────
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-001',
                'nombre'    => 'Pan Francés Recién Horneado',
                'descripcion' => 'Pan francés crujiente por fuera y suave por dentro, horneado fresco cada mañana. Ideal para el desayuno o para acompañar cualquier comida.',
                'precio' => 4.50, 'unidad' => 'unidad', 'peso_gramos' => 80,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1509440159596-0249088772ff'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-002',
                'nombre'    => 'Pan Dulce Surtido',
                'descripcion' => 'Bolsa surtida con 6 piezas de pan dulce artesanal: conchas, cuernos, orejas y polvorones. Perfectos para la merienda o el café.',
                'precio' => 18.00, 'precio_oferta' => 15.00, 'unidad' => 'bolsa', 'peso_gramos' => 450,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1549931319-a545dcf3bc73'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-003',
                'nombre'    => 'Tortillas de Maíz Artesanales',
                'descripcion' => 'Docena de tortillas de maíz blanco hechas a mano, gruesas y suaves. Elaboradas con masa nixtamalizada tradicional del Caribe guatemalteco.',
                'precio' => 8.00, 'unidad' => 'docena', 'peso_gramos' => 600,
                'stock' => 120, 'stock_min' => 20,
                'imagen' => $this->img('1575535468632-345892291673'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-004',
                'nombre'    => 'Pan Integral de Semillas',
                'descripcion' => 'Pan integral enriquecido con semillas de ajonjolí y linaza. Rico en fibra y nutrientes, ideal para una alimentación balanceada.',
                'precio' => 24.00, 'unidad' => 'unidad', 'peso_gramos' => 500,
                'stock' => 45, 'stock_min' => 5,
                'imagen' => $this->img('1509440159596-0249088772ff'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-005',
                'nombre'    => 'Bollitos de Pan con Mantequilla',
                'descripcion' => 'Paquete de 6 bollitos suaves bañados en mantequilla. Receta clásica de panadería artesanal, perfectos para desayunos en familia.',
                'precio' => 15.00, 'unidad' => 'bolsa', 'peso_gramos' => 360,
                'stock' => 55, 'stock_min' => 6,
                'imagen' => $this->img('1565299507177-b0ac66763828'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-006',
                'nombre'    => 'Rosquillas de Canela y Azúcar',
                'descripcion' => 'Rosquillas esponjosas con cobertura de canela y azúcar. Receta casera de la panadería, horneadas sin aceites artificiales.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 250,
                'stock' => 40, 'stock_min' => 5,
                'imagen' => $this->img('1551024601-bec78aea704b'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-007',
                'nombre'    => 'Pan Tostado Artesanal',
                'descripcion' => 'Pan tostado crujiente elaborado con harina de trigo y levadura natural. Ideal para acompañar con queso, mantequilla o mermelada.',
                'precio' => 22.00, 'unidad' => 'bolsa', 'peso_gramos' => 400,
                'stock' => 35, 'stock_min' => 5,
                'imagen' => $this->img('1590080875515-0ee3ec63b5c5'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-008',
                'nombre'    => 'Donas Glaseadas Surtidas',
                'descripcion' => 'Caja con 6 donas glaseadas con diferentes coberturas: chocolate, fresa, vainilla y azúcar. Elaboradas con ingredientes frescos cada día.',
                'precio' => 28.00, 'unidad' => 'unidad', 'peso_gramos' => 480,
                'stock' => 30, 'stock_min' => 4,
                'imagen' => $this->img('1551024601-bec78aea704b'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-009',
                'nombre'    => 'Quesadillas de Queso',
                'descripcion' => 'Quesadillas dulces rellenas de queso crema y ricotta. Postre tradicional guatemalteco horneado fresco con ingredientes locales.',
                'precio' => 20.00, 'unidad' => 'bolsa', 'peso_gramos' => 300,
                'stock' => 50, 'stock_min' => 5,
                'imagen' => $this->img('1565299624946-b28f40a0ae38'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-010',
                'nombre'    => 'Croissant de Mantequilla',
                'descripcion' => 'Croissants hojaldrados con abundante mantequilla, crujientes por fuera y suaves por dentro. Elaborados con técnica artesanal europea.',
                'precio' => 14.00, 'unidad' => 'unidad', 'peso_gramos' => 180,
                'stock' => 25, 'stock_min' => 4,
                'imagen' => $this->img('1555507036-ab1f4038808a'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-011',
                'nombre'    => 'Semitas de Anís',
                'descripcion' => 'Semitas tradicionales elaboradas con anís y panela. Dulce típico guatemalteco de textura firme, ideal para acompañar bebidas calientes.',
                'precio' => 12.00, 'unidad' => 'bolsa', 'peso_gramos' => 200,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1558961363-fa8fdf82db35'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-012',
                'nombre'    => 'Empanadas de Frijol Colado',
                'descripcion' => 'Empanadas rellenas de frijol negro colado con loroco. Masa de maíz hecha a mano, horneadas sin grasa adicional. Porción de 4 unidades.',
                'precio' => 16.00, 'unidad' => 'bolsa', 'peso_gramos' => 320,
                'stock' => 40, 'stock_min' => 6,
                'imagen' => $this->img('1604908177453-7462950a6a3b'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-013',
                'nombre'    => 'Pan de Coco Caribeño',
                'descripcion' => 'Pan dulce esponjoso elaborado con leche de coco fresco. Especialidad de la Panadería La Esquina, con aroma y sabor auténtico del Caribe.',
                'precio' => 20.00, 'unidad' => 'unidad', 'peso_gramos' => 300,
                'stock' => 35, 'stock_min' => 5,
                'imagen' => $this->img('1533910534207-90f31029a78e'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-014',
                'nombre'    => 'Bizcochitos de Azúcar',
                'descripcion' => 'Bolsa de bizcochitos crujientes cubiertos de azúcar granulada. Receta artesanal sin colorantes artificiales, perfectos para la hora del té.',
                'precio' => 12.00, 'unidad' => 'bolsa', 'peso_gramos' => 200,
                'stock' => 55, 'stock_min' => 6,
                'imagen' => $this->img('1558961363-fa8fdf82db35'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-015',
                'nombre'    => 'Pan Brioche Esponjoso',
                'descripcion' => 'Pan brioche de origen francés, elaborado con huevos frescos y mantequilla de primera calidad. De textura suave y color dorado.',
                'precio' => 30.00, 'unidad' => 'unidad', 'peso_gramos' => 400,
                'stock' => 20, 'stock_min' => 3,
                'imagen' => $this->img('1555507036-ab1f4038808a'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-016',
                'nombre'    => 'Tostadas Simples de Maíz',
                'descripcion' => 'Tostadas crujientes de maíz blanco, ligeramente saladas. Ideales para antojitos, enchiladas o como base para preparaciones rápidas.',
                'precio' => 10.00, 'unidad' => 'bolsa', 'peso_gramos' => 250,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1590080875515-0ee3ec63b5c5'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-017',
                'nombre'    => 'Palitos de Pan con Ajonjolí',
                'descripcion' => 'Palitos de pan crujientes cubiertos de ajonjolí tostado. Perfectos para acompañar sopas, ensaladas o disfrutar como snack saludable.',
                'precio' => 11.00, 'unidad' => 'bolsa', 'peso_gramos' => 150,
                'stock' => 45, 'stock_min' => 5,
                'imagen' => $this->img('1586444248902-2f64eddc13df'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-018',
                'nombre'    => 'Pan Pita de Trigo Integral',
                'descripcion' => 'Pan pita esponjoso elaborado con harina de trigo integral. Versátil y nutritivo, ideal para wraps, dips de hummus o como acompañante.',
                'precio' => 22.00, 'unidad' => 'bolsa', 'peso_gramos' => 360,
                'stock' => 30, 'stock_min' => 4,
                'imagen' => $this->img('1560806887-1e4cd0b6cbd6'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-019',
                'nombre'    => 'Pan de Plátano Maduro',
                'descripcion' => 'Bizcocho húmedo de plátano maduro con nueces y canela. Receta artesanal sin conservadores, elaborada con plátanos locales de Izabal.',
                'precio' => 26.00, 'unidad' => 'unidad', 'peso_gramos' => 350,
                'stock' => 20, 'stock_min' => 3,
                'imagen' => $this->img('1603833665858-e61d17a86224'),
            ],
            [
                'vendor_id' => 4, 'categoria_id' => 11, 'sku' => 'PAN-020',
                'nombre'    => 'Cupcakes de Vainilla con Betún',
                'descripcion' => 'Caja de 4 cupcakes esponjosos de vainilla con betún de mantequilla. Decorados a mano con diferentes colores, perfectos para celebraciones.',
                'precio' => 32.00, 'unidad' => 'unidad', 'peso_gramos' => 280,
                'stock' => 18, 'stock_min' => 3,
                'imagen' => $this->img('1464349095431-e9a21285b5f3'),
            ],

            // ─────────────────────────────────────────────────────────────
            // MARISCOS BAHIA AMATIQUE  (vendor_id=5)  20 productos
            // ─────────────────────────────────────────────────────────────
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-001',
                'nombre'    => 'Camarones Frescos Medianos',
                'descripcion' => 'Camarones frescos del Golfo de Honduras, limpios y desvenados. Capturados el mismo día, ideales para ceviche, camaronada o al ajillo.',
                'precio' => 95.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 30, 'stock_min' => 5,
                'imagen' => $this->img('1565680018434-b513d5e5fd47'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-002',
                'nombre'    => 'Filete de Tilapia Fresco',
                'descripcion' => 'Filetes de tilapia limpia sin espinas, pescada fresca de las aguas del Caribe. Proteína blanca magra, ideal para freír, hornear o a la plancha.',
                'precio' => 42.00, 'precio_oferta' => 36.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 40, 'stock_min' => 5,
                'imagen' => $this->img('1615141982883-c7ad0e69fd62'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-003',
                'nombre'    => 'Corvina Entera Fresca',
                'descripcion' => 'Corvina entera del Atlántico guatemalteco, fresca y limpia. Ideal para preparar en caldo, frita entera o marinada con especias tropicales.',
                'precio' => 55.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 25, 'stock_min' => 4,
                'imagen' => $this->img('1498654200943-1088dd4438ae'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-004',
                'nombre'    => 'Langostinos del Golfo',
                'descripcion' => 'Langostinos grandes con cabeza, capturados en el Golfo de Honduras. Sabor intenso y carne firme, perfectos para parrillada o marisquería.',
                'precio' => 130.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 15, 'stock_min' => 3,
                'imagen' => $this->img('1565680018434-b513d5e5fd47'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-005',
                'nombre'    => 'Mojarra Fresca Entera',
                'descripcion' => 'Mojarra del Caribe, fresca y limpia. Pez de sabor suave muy apreciado en la cocina local. Ideal para freír entero o preparar en escabeche.',
                'precio' => 38.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 35, 'stock_min' => 5,
                'imagen' => $this->img('1615141982883-c7ad0e69fd62'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-006',
                'nombre'    => 'Calamar Fresco Limpio',
                'descripcion' => 'Calamar fresco del Atlántico, ya limpio y cortado en anillos. Carne tierna de sabor suave, perfecto para calamares a la romana o en salsa.',
                'precio' => 65.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 20, 'stock_min' => 3,
                'imagen' => $this->img('1604908176997-125f25cc6f3d'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-007',
                'nombre'    => 'Pulpo del Caribe',
                'descripcion' => 'Pulpo fresco del Mar Caribe, limpio y precocido para facilitar su preparación. Carne firme y sabrosa, ideal para ensaladas, ceviches o a la plancha.',
                'precio' => 75.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 18, 'stock_min' => 3,
                'imagen' => $this->img('1559564522-6d84bbde4662'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-008',
                'nombre'    => 'Mezcla de Mariscos del Atlántico',
                'descripcion' => 'Combinación de camarones, calamar, jaiba y mejillones frescos. Perfecta para sopas de mariscos, pasta o risotto de frutos del mar.',
                'precio' => 85.00, 'precio_oferta' => 75.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 22, 'stock_min' => 4,
                'imagen' => $this->img('1565680018434-b513d5e5fd47'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-009',
                'nombre'    => 'Atún Fresco en Filete',
                'descripcion' => 'Filete de atún aleta amarilla del Atlántico, fresco y sin piel. Ideal para tataki, sushi, sellado a la plancha o en ceviche gourmet.',
                'precio' => 88.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 15, 'stock_min' => 3,
                'imagen' => $this->img('1504674900247-0877df9cc836'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-010',
                'nombre'    => 'Sardinas Frescas del Caribe',
                'descripcion' => 'Sardinas frescas capturadas localmente, limpias y sin cabeza. Ricas en omega-3 y proteínas. Perfectas para freír, ahumar o en escabeche.',
                'precio' => 28.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 45, 'stock_min' => 6,
                'imagen' => $this->img('1615141982883-c7ad0e69fd62'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-011',
                'nombre'    => 'Jaiba del Atlántico Cocida',
                'descripcion' => 'Jaiba del Atlántico guatemalteco, ya cocida y lista para comer. Sabor dulce y carne jugosa, ideal para sopas, ceviche o empanadas de jaiba.',
                'precio' => 60.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 18, 'stock_min' => 3,
                'imagen' => $this->img('1559564522-6d84bbde4662'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-012',
                'nombre'    => 'Robalo en Filete sin Espinas',
                'descripcion' => 'Filete de robalo fresco sin espinas ni piel. Carne blanca y de sabor delicado, considerado uno de los pescados más finos del Caribe.',
                'precio' => 72.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 20, 'stock_min' => 3,
                'imagen' => $this->img('1498654200943-1088dd4438ae'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-013',
                'nombre'    => 'Camarones Tigre Jumbo',
                'descripcion' => 'Camarones tigre de gran tamaño, pelados y listos para cocinar. Carne firme y sabor intenso. Ideales para parrillada, tempura o al coco.',
                'precio' => 145.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 12, 'stock_min' => 2,
                'imagen' => $this->img('1565680018434-b513d5e5fd47'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-014',
                'nombre'    => 'Pargo Rojo Entero',
                'descripcion' => 'Pargo rojo fresco del Caribe, entero y limpio. Pescado de carne firme y sabor excepcional. Ideal para hornear entero con hierbas o freír.',
                'precio' => 58.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 22, 'stock_min' => 4,
                'imagen' => $this->img('1498654200943-1088dd4438ae'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 8, 'sku' => 'MAR-015',
                'nombre'    => 'Filete de Dorado (Mahi-Mahi)',
                'descripcion' => 'Filete de dorado o mahi-mahi del Atlántico, sin piel ni espinas. Carne suave de color rosado, muy apreciada en restaurantes por su sabor.',
                'precio' => 80.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 16, 'stock_min' => 3,
                'imagen' => $this->img('1615141982883-c7ad0e69fd62'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 7, 'sku' => 'MAR-016',
                'nombre'    => 'Pollo Entero Fresco de Granja',
                'descripcion' => 'Pollo entero de granja local, fresco y sin hormonas. Limpio y listo para trozar. Ideal para caldo, pollo asado o preparaciones al horno.',
                'precio' => 68.00, 'unidad' => 'unidad', 'peso_gramos' => 1800, 'refrigeracion' => true,
                'stock' => 25, 'stock_min' => 4,
                'imagen' => $this->img('1604503468506-a8da13d11d36'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 7, 'sku' => 'MAR-017',
                'nombre'    => 'Posta de Res para Bistec',
                'descripcion' => 'Corte de res fresco, ideal para bistec o a la plancha. Carne de calidad local con el balance perfecto entre magro y marmoleado.',
                'precio' => 55.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 20, 'stock_min' => 4,
                'imagen' => $this->img('1529694157872-4bb3ac05ab6f'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 7, 'sku' => 'MAR-018',
                'nombre'    => 'Chuletas de Cerdo Ahumadas',
                'descripcion' => 'Chuletas de cerdo ahumadas con maderas locales, listas para calentar y servir. Sabor intenso y textura jugosa, perfectas para la parrilla.',
                'precio' => 62.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 18, 'stock_min' => 3,
                'imagen' => $this->img('1529694157872-4bb3ac05ab6f'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 7, 'sku' => 'MAR-019',
                'nombre'    => 'Costillas de Cerdo Frescas',
                'descripcion' => 'Costillas de cerdo frescas con hueso, listas para marinar y asar. Corte generoso ideal para BBQ, cocido o en salsa de tomate con especias.',
                'precio' => 45.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 22, 'stock_min' => 4,
                'imagen' => $this->img('1529694157872-4bb3ac05ab6f'),
            ],
            [
                'vendor_id' => 5, 'categoria_id' => 7, 'sku' => 'MAR-020',
                'nombre'    => 'Salchicha Ahumada Artesanal',
                'descripcion' => 'Salchicha artesanal ahumada elaborada con carne de cerdo y especias naturales. Sin conservadores artificiales. Lista para asar, freír o en guisos.',
                'precio' => 48.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 30, 'stock_min' => 5,
                'imagen' => $this->img('1529694157872-4bb3ac05ab6f'),
            ],

            // ─────────────────────────────────────────────────────────────
            // DESPENSA SANTO TOMÁS  (vendor_id=6)  30 productos
            // ─────────────────────────────────────────────────────────────

            // Abarrotes
            [
                'vendor_id' => 6, 'categoria_id' => 1, 'sku' => 'DSP-001',
                'nombre'    => 'Arroz Blanco Extra Larga',
                'descripcion' => 'Arroz blanco de grano largo extra, limpio y seleccionado. Cocción perfecta en 20 minutos, ideal para acompañar cualquier platillo de la cocina guatemalteca.',
                'precio' => 9.50, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 200, 'stock_min' => 30, 'stock_max' => 500,
                'imagen' => $this->img('1536304929831-ee1ca9d44906'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 1, 'sku' => 'DSP-002',
                'nombre'    => 'Frijoles Negros de Izabal',
                'descripcion' => 'Frijoles negros locales cosechados en la región de Izabal. Grano grande y brillante que se cuece rápido. Ricos en proteína vegetal y fibra.',
                'precio' => 8.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 180, 'stock_min' => 25, 'stock_max' => 400,
                'imagen' => $this->img('1536304929831-ee1ca9d44906'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 1, 'sku' => 'DSP-003',
                'nombre'    => 'Azúcar Blanca Granulada',
                'descripcion' => 'Azúcar blanca de caña guatemalteca, finamente granulada. Ideal para endulzar bebidas, preparar postres y para uso general en la cocina.',
                'precio' => 6.50, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 220, 'stock_min' => 30, 'stock_max' => 500,
                'imagen' => $this->img('1589419946083-e9aab3bc9d81'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 3, 'sku' => 'DSP-004',
                'nombre'    => 'Aceite Vegetal de Palma',
                'descripcion' => 'Aceite vegetal refinado de palma guatemalteca, sin colesterol. Botella de 750ml con tapa de seguridad. Ideal para freír, saltear y aderezar.',
                'precio' => 28.00, 'unidad' => 'unidad', 'peso_gramos' => 750,
                'stock' => 100, 'stock_min' => 15,
                'imagen' => $this->img('1474979266404-7eaacbcd87c5'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 1, 'sku' => 'DSP-005',
                'nombre'    => 'Sal de Mesa Yodada',
                'descripcion' => 'Sal de mesa yodada fina, empacada en bolsa resistente de 500g. Sal de primera calidad para condimentar y conservar alimentos.',
                'precio' => 4.50, 'unidad' => 'bolsa', 'peso_gramos' => 500,
                'stock' => 250, 'stock_min' => 30, 'stock_max' => 600,
                'imagen' => $this->img('1528750717929-32abb73d3bd9'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 2, 'sku' => 'DSP-006',
                'nombre'    => 'Pasta Espagueti 400g',
                'descripcion' => 'Espagueti de sémola de trigo duro, cocción al dente en 8 minutos. Versátil para bolognesa, carbonara, alfredo o cualquier salsa de tu preferencia.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 400,
                'stock' => 120, 'stock_min' => 15,
                'imagen' => $this->img('1563379926898-05f4575a45d8'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 2, 'sku' => 'DSP-007',
                'nombre'    => 'Salsa de Tomate con Albahaca',
                'descripcion' => 'Salsa de tomate concentrada con albahaca y orégano. Lista para usar en pastas, pizzas o guisos. Enlatada sin conservadores artificiales.',
                'precio' => 12.00, 'unidad' => 'unidad', 'peso_gramos' => 400,
                'stock' => 90, 'stock_min' => 12,
                'imagen' => $this->img('1546548970-71785318a17b'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 3, 'sku' => 'DSP-008',
                'nombre'    => 'Mayonesa Clásica Cremosa',
                'descripcion' => 'Mayonesa cremosa elaborada con aceite de soya y huevo. Sabor suave y textura untuosa. Ideal para sándwiches, ensaladas y aderezos.',
                'precio' => 22.00, 'precio_oferta' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 450,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1580584126903-c17d41830450'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 3, 'sku' => 'DSP-009',
                'nombre'    => 'Consomé de Pollo en Polvo',
                'descripcion' => 'Consomé de pollo en polvo con hierbas aromáticas. Lata de 200g para sazonar sopas, arroces, frijoles y todo tipo de guisos con sabor intenso.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 200,
                'stock' => 100, 'stock_min' => 15,
                'imagen' => $this->img('1474979266404-7eaacbcd87c5'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 3, 'sku' => 'DSP-010',
                'nombre'    => 'Salsa Inglesa Worcestershire',
                'descripcion' => 'Salsa inglesa tipo Worcestershire de 150ml. Imprescindible para marinar carnes, preparar cócteles y dar profundidad de sabor a guisos y salsas.',
                'precio' => 16.00, 'unidad' => 'unidad', 'peso_gramos' => 150,
                'stock' => 70, 'stock_min' => 10,
                'imagen' => $this->img('1474979266404-7eaacbcd87c5'),
            ],

            // Bebidas
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-011',
                'nombre'    => 'Agua Purificada 1.5 Litros',
                'descripcion' => 'Agua purificada por ósmosis inversa, libre de cloro y contaminantes. Botella ergonómica de 1.5L con tapón de seguridad. Refrescante y pura.',
                'precio' => 7.00, 'unidad' => 'unidad', 'peso_gramos' => 1500,
                'stock' => 300, 'stock_min' => 40, 'stock_max' => 600,
                'imagen' => $this->img('1548839140-29a749e1cf4d'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-012',
                'nombre'    => 'Jugo de Naranja Natural 1L',
                'descripcion' => 'Jugo de naranja 100% natural sin azúcar añadida, prensado en frío. Rico en vitamina C y antioxidantes. Refrigerar después de abrir.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 1000, 'refrigeracion' => true,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1613478223719-2ab802602423'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-013',
                'nombre'    => 'Gaseosa de Cola 2 Litros',
                'descripcion' => 'Refresco carbonatado sabor cola en presentación familiar de 2 litros. Perfecto para acompañar comidas en reuniones y celebraciones.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 2000,
                'stock' => 120, 'stock_min' => 15,
                'imagen' => $this->img('1554866585-cd94860890b7'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-014',
                'nombre'    => 'Té Frío de Durazno 500ml',
                'descripcion' => 'Bebida de té negro con sabor a durazno natural, baja en calorías. Botella de 500ml lista para consumir, refrescante y sin conservadores artificiales.',
                'precio' => 10.00, 'unidad' => 'unidad', 'peso_gramos' => 500,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1597481499666-5d2c2c7e3b82'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-015',
                'nombre'    => 'Bebida Energizante 500ml',
                'descripcion' => 'Bebida energizante con cafeína, taurina y vitaminas del complejo B. Lata de 500ml para cuando necesitas un impulso extra durante el día.',
                'precio' => 20.00, 'unidad' => 'unidad', 'peso_gramos' => 500,
                'stock' => 70, 'stock_min' => 10,
                'imagen' => $this->img('1571019613454-1cb2f99b2d8b'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 13, 'sku' => 'DSP-016',
                'nombre'    => 'Agua con Gas Sabor Limón 600ml',
                'descripcion' => 'Agua mineral carbonatada con toque natural de limón. Sin azúcar, sin calorías. Refrescante alternativa saludable a los refrescos tradicionales.',
                'precio' => 12.00, 'unidad' => 'unidad', 'peso_gramos' => 600,
                'stock' => 90, 'stock_min' => 12,
                'imagen' => $this->img('1548839140-29a749e1cf4d'),
            ],

            // Snacks
            [
                'vendor_id' => 6, 'categoria_id' => 12, 'sku' => 'DSP-017',
                'nombre'    => 'Papas Fritas con Sal de Mar',
                'descripcion' => 'Papas fritas en hojuelas crujientes, sazonadas con sal de mar gruesa. Sin saborizantes artificiales. Bolsa de 150g con cierre zip para conservar la frescura.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 150,
                'stock' => 100, 'stock_min' => 12,
                'imagen' => $this->img('1621606119405-4a47c1d04e25'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 12, 'sku' => 'DSP-018',
                'nombre'    => 'Galletas de Vainilla con Chispas',
                'descripcion' => 'Galletas crujientes de vainilla con chispas de chocolate. Bolsa de 200g ideal para meriendas, postres o para acompañar leche y café.',
                'precio' => 14.00, 'precio_oferta' => 12.00, 'unidad' => 'bolsa', 'peso_gramos' => 200,
                'stock' => 90, 'stock_min' => 10,
                'imagen' => $this->img('1558961363-fa8fdf82db35'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 12, 'sku' => 'DSP-019',
                'nombre'    => 'Palomitas con Mantequilla',
                'descripcion' => 'Palomitas de maíz para microondas con mantequilla real. Bolsa de 85g lista en 3 minutos. El snack perfecto para películas y reuniones.',
                'precio' => 10.00, 'unidad' => 'unidad', 'peso_gramos' => 85,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1585647347484-8f5a6b6c4c3a'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 12, 'sku' => 'DSP-020',
                'nombre'    => 'Nachos con Chile y Limón',
                'descripcion' => 'Nachos triangulares de maíz con sazón de chile y limón. Perfectos para dips de guacamole, salsa o queso fundido. Bolsa de 150g.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 150,
                'stock' => 85, 'stock_min' => 10,
                'imagen' => $this->img('1558008258-3256797b43f3'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 12, 'sku' => 'DSP-021',
                'nombre'    => 'Chicharrones de Maíz Horneados',
                'descripcion' => 'Chicharrones de maíz expandido horneados, sin grasa añadida. Sabor achiote y limón. Snack ligero y crujiente, alto en fibra. Bolsa de 80g.',
                'precio' => 8.00, 'unidad' => 'bolsa', 'peso_gramos' => 80,
                'stock' => 100, 'stock_min' => 12,
                'imagen' => $this->img('1566478989037-eec170784d0b'),
            ],

            // Limpieza y desechables
            [
                'vendor_id' => 6, 'categoria_id' => 16, 'sku' => 'DSP-022',
                'nombre'    => 'Detergente en Polvo Lavanda 500g',
                'descripcion' => 'Detergente en polvo con fragancia de lavanda para ropa blanca y de color. Fórmula concentrada que rinde hasta 20 lavadas. Bolsa con cierre hermético.',
                'precio' => 24.00, 'unidad' => 'bolsa', 'peso_gramos' => 500,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1585771724684-38269d6639fd'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 16, 'sku' => 'DSP-023',
                'nombre'    => 'Cloro Líquido Desinfectante 1L',
                'descripcion' => 'Cloro líquido al 5.25% de hipoclorito de sodio para desinfección del hogar. Elimina bacterias y virus. Botella de 1 litro con tapón de seguridad.',
                'precio' => 12.00, 'unidad' => 'unidad', 'peso_gramos' => 1000,
                'stock' => 90, 'stock_min' => 12,
                'imagen' => $this->img('1563453392212-326f5e854473'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 16, 'sku' => 'DSP-024',
                'nombre'    => 'Suavizante de Telas Primavera 800ml',
                'descripcion' => 'Suavizante concentrado con fragancia floral de primavera. Rinde hasta 25 lavadas. Deja la ropa suave, con aroma duradero y reduce la electricidad estática.',
                'precio' => 28.00, 'unidad' => 'unidad', 'peso_gramos' => 800,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1584305574647-0cc949a2bb9f'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 16, 'sku' => 'DSP-025',
                'nombre'    => 'Limpiapisos Pino 500ml',
                'descripcion' => 'Limpiador multiusos con aroma a pino para pisos de cerámica, madera y concreto. Desinfecta y deja superficies brillantes. Fórmula antibacterial.',
                'precio' => 14.00, 'unidad' => 'unidad', 'peso_gramos' => 500,
                'stock' => 70, 'stock_min' => 10,
                'imagen' => $this->img('1563453392212-326f5e854473'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 17, 'sku' => 'DSP-026',
                'nombre'    => 'Vasos Desechables 8oz x50',
                'descripcion' => 'Vasos desechables de plástico transparente de 8 onzas. Paquete de 50 unidades ideales para agua, jugos, refrescos y bebidas calientes. No tóxicos.',
                'precio' => 18.00, 'unidad' => 'bolsa', 'peso_gramos' => 180,
                'stock' => 70, 'stock_min' => 8,
                'imagen' => $this->img('1577565177369-4fe26e7fc7d4'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 17, 'sku' => 'DSP-027',
                'nombre'    => 'Platos Desechables x20 Unidades',
                'descripcion' => 'Platos desechables de cartón prensado de 9 pulgadas. Paquete de 20 unidades. Resistentes al agua y a alimentos calientes. Biodegradables.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 200,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1565193566173-7a0ee3dbe261'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 17, 'sku' => 'DSP-028',
                'nombre'    => 'Bolsas de Basura Negras x25',
                'descripcion' => 'Bolsas de basura negras de alta resistencia calibre 200. Paquete de 25 unidades tamaño 26x34 pulgadas. Con cierre tipo lazo para mayor comodidad.',
                'precio' => 14.00, 'unidad' => 'bolsa', 'peso_gramos' => 300,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1584308666744-24d5c474f2ae'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 17, 'sku' => 'DSP-029',
                'nombre'    => 'Papel Higiénico Doble Hoja x4',
                'descripcion' => 'Papel higiénico doble hoja de alta suavidad, 200 hojas por rollo. Paquete de 4 rollos. Sin blanqueadores agresivos, suave con la piel sensible.',
                'precio' => 28.00, 'precio_oferta' => 24.00, 'unidad' => 'bolsa', 'peso_gramos' => 400,
                'stock' => 90, 'stock_min' => 12,
                'imagen' => $this->img('1584308666744-24d5c474f2ae'),
            ],
            [
                'vendor_id' => 6, 'categoria_id' => 17, 'sku' => 'DSP-030',
                'nombre'    => 'Cubiertos Desechables x12 Juegos',
                'descripcion' => 'Set de cubiertos desechables: tenedor, cuchillo y cuchara por juego. 12 juegos por paquete. Resistentes y sin BPA, ideales para eventos y reuniones.',
                'precio' => 8.00, 'unidad' => 'bolsa', 'peso_gramos' => 150,
                'stock' => 70, 'stock_min' => 8,
                'imagen' => $this->img('1565193566173-7a0ee3dbe261'),
            ],

            // ─────────────────────────────────────────────────────────────
            // LÁCTEOS LA RUIDOSA  (vendor_id=7)  15 productos
            // ─────────────────────────────────────────────────────────────
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-001',
                'nombre'    => 'Leche Entera Fresca 1 Litro',
                'descripcion' => 'Leche entera fresca pasteurizada de ganado local de Izabal. Sin hormonas ni antibióticos. Rica en calcio y vitaminas A y D. Refrigerar siempre.',
                'precio' => 14.00, 'unidad' => 'unidad', 'peso_gramos' => 1000, 'refrigeracion' => true,
                'stock' => 80, 'stock_min' => 12,
                'imagen' => $this->img('1550583724-b2692b85b150'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-002',
                'nombre'    => 'Leche Descremada 1 Litro',
                'descripcion' => 'Leche descremada pasteurizada con 0% de grasa. Conserva todas las proteínas y el calcio de la leche entera. Ideal para dietas bajas en grasa.',
                'precio' => 14.00, 'unidad' => 'unidad', 'peso_gramos' => 1000, 'refrigeracion' => true,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1550583724-b2692b85b150'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-003',
                'nombre'    => 'Queso Fresco de Izabal',
                'descripcion' => 'Queso fresco elaborado artesanalmente con leche de vaca local. Textura firme y sabor suave. Ideal para desayunos, pupusas, tamales y platillos típicos.',
                'precio' => 32.00, 'unidad' => 'libra', 'refrigeracion' => true,
                'stock' => 40, 'stock_min' => 6,
                'imagen' => $this->img('1486297678162-eb2a19b0a32d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-004',
                'nombre'    => 'Queso Crema Untable 250g',
                'descripcion' => 'Queso crema suave y untable elaborado con leche entera. Perfecto para untar en pan, preparar pastel de queso, dips y salsas cremosas.',
                'precio' => 22.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 50, 'stock_min' => 8,
                'imagen' => $this->img('1486297678162-eb2a19b0a32d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-005',
                'nombre'    => 'Mantequilla Sin Sal 250g',
                'descripcion' => 'Mantequilla de leche de vaca sin sal añadida. Elaborada con crema fresca pasteurizada. Ideal para hornear, cocinar y untar con el mejor sabor lácteo.',
                'precio' => 30.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 35, 'stock_min' => 5,
                'imagen' => $this->img('1589985270826-4b7bb135bc9d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-006',
                'nombre'    => 'Crema de Leche para Cocinar 250ml',
                'descripcion' => 'Crema de leche fresca con 35% de materia grasa. Perfecta para salsas, sopas, postres y café. Textura rica y sabor lácteo auténtico de Izabal.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 50, 'stock_min' => 6,
                'imagen' => $this->img('1563636619-e9143da7973b'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-007',
                'nombre'    => 'Yogurt Natural Sin Azúcar 200g',
                'descripcion' => 'Yogurt natural sin azúcar añadida, elaborado con cultivos lácticos vivos. Alto en proteína y probióticos para la salud digestiva. Envase de 200g.',
                'precio' => 14.00, 'precio_oferta' => 12.00, 'unidad' => 'unidad', 'peso_gramos' => 200, 'refrigeracion' => true,
                'stock' => 55, 'stock_min' => 8,
                'imagen' => $this->img('1488477181946-6428a0291777'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-008',
                'nombre'    => 'Queso Duro Añejo Rallado 250g',
                'descripcion' => 'Queso duro añejo madurado y rallado fino. Sabor intenso y concentrado, ideal para pastas, sopas, tostadas y gratinar platillos al horno.',
                'precio' => 38.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 30, 'stock_min' => 5,
                'imagen' => $this->img('1486297678162-eb2a19b0a32d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-009',
                'nombre'    => 'Queso Mozzarella en Bola 250g',
                'descripcion' => 'Queso mozzarella fresco en bola, elaborado con leche de vaca pasteurizada. Suave, elástico y con sabor suave. Perfecto para pizza, caprese y pasta.',
                'precio' => 36.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 30, 'stock_min' => 5,
                'imagen' => $this->img('1486297678162-eb2a19b0a32d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-010',
                'nombre'    => 'Leche Condensada Azucarada 397g',
                'descripcion' => 'Leche condensada entera azucarada en lata de 397g. Esencial para postres, dulces, flan, tres leches, cajetas y bebidas dulces.',
                'precio' => 24.00, 'unidad' => 'unidad', 'peso_gramos' => 397,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1550583724-b2692b85b150'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-011',
                'nombre'    => 'Huevos Blancos Grandes Docena',
                'descripcion' => 'Docena de huevos blancos de gallina campestre, tamaño grande. Ricos en proteína de alta calidad. Frescos y seleccionados de granjas locales de Izabal.',
                'precio' => 28.00, 'unidad' => 'docena', 'refrigeracion' => true,
                'stock' => 100, 'stock_min' => 15,
                'imagen' => $this->img('1598965675045-45c5e72c7d05'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-012',
                'nombre'    => 'Huevos Rojos Orgánicos Docena',
                'descripcion' => 'Docena de huevos rojos de gallinas criadas en campo abierto sin jaulas. Yema más naranja y sabor más intenso. Certificado sin antibióticos.',
                'precio' => 32.00, 'unidad' => 'docena', 'refrigeracion' => true,
                'stock' => 70, 'stock_min' => 10,
                'imagen' => $this->img('1598965675045-45c5e72c7d05'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-013',
                'nombre'    => 'Crema Ácida Fresca 250g',
                'descripcion' => 'Crema ácida fresca de 250g elaborada con crema de leche local fermentada. Sabor suave y textura espesa. Indispensable en la cocina guatemalteca.',
                'precio' => 16.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1563636619-e9143da7973b'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-014',
                'nombre'    => 'Requesón Fresco 250g',
                'descripcion' => 'Requesón fresco de textura granulada y sabor suave. Ideal para rellenar pupusas, enchiladas, tacos o usar en repostería y salsas cremosas.',
                'precio' => 20.00, 'unidad' => 'unidad', 'peso_gramos' => 250, 'refrigeracion' => true,
                'stock' => 35, 'stock_min' => 5,
                'imagen' => $this->img('1486297678162-eb2a19b0a32d'),
            ],
            [
                'vendor_id' => 7, 'categoria_id' => 6, 'sku' => 'LAC-015',
                'nombre'    => 'Leche de Soya Sin Azúcar 1L',
                'descripcion' => 'Bebida de soya natural sin azúcar añadida, enriquecida con calcio y vitamina D. Alternativa vegetal a la leche de vaca, apta para intolerantes a la lactosa.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 1000,
                'stock' => 40, 'stock_min' => 5,
                'imagen' => $this->img('1550583724-b2692b85b150'),
            ],

            // ─────────────────────────────────────────────────────────────
            // FRUTAS DEL CARIBE  (vendor_id=8)  15 productos
            // ─────────────────────────────────────────────────────────────
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-001',
                'nombre'    => 'Bananos Maduros de Izabal',
                'descripcion' => 'Bananos maduros cultivados en las fértiles tierras de Izabal. Dulces, aromáticos y llenos de potasio. Perfectos para consumir solos o en batidos.',
                'precio' => 6.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 150, 'stock_min' => 20, 'stock_max' => 400,
                'imagen' => $this->img('1603833665858-e61d17a86224'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-002',
                'nombre'    => 'Mangos Tommy Atkins Maduros',
                'descripcion' => 'Mangos Tommy Atkins del Caribe guatemalteco, seleccionados en su punto óptimo de madurez. Pulpa firme, jugosa y de color naranja intenso. Sin fibra.',
                'precio' => 8.00, 'precio_oferta' => 6.50, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 100, 'stock_min' => 15,
                'imagen' => $this->img('1553279768-865429fa0078'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-003',
                'nombre'    => 'Piña Dulce de Honduras',
                'descripcion' => 'Piña tropical dulce y jugosa, cortada y lista para consumir o en entera. Rica en vitamina C, bromelaína y fibra. Sabor caribeño auténtico.',
                'precio' => 18.00, 'unidad' => 'unidad', 'peso_gramos' => 1200,
                'stock' => 50, 'stock_min' => 8,
                'imagen' => $this->img('1490885578174-acda8905c2c6'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-004',
                'nombre'    => 'Naranjas de Jugo Valencia',
                'descripcion' => 'Naranjas Valencia jugosas y dulces, ideales para exprimir o consumir en gajos. Altas en vitamina C y antioxidantes. Cosechadas en la región de Izabal.',
                'precio' => 5.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 150, 'stock_min' => 20,
                'imagen' => $this->img('1547514701-42782101795e'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-005',
                'nombre'    => 'Papaya Maradol Grande',
                'descripcion' => 'Papaya Maradol de tamaño grande, pulpa roja y dulce. Rica en papaína, vitamina C y betacaroteno. Ideal para ensaladas de frutas, jugos y postres.',
                'precio' => 6.50, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 60, 'stock_min' => 8,
                'imagen' => $this->img('1526318630405-e0f615ab0955'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-006',
                'nombre'    => 'Aguacate Hass Cremoso',
                'descripcion' => 'Aguacate Hass de piel rugosa y pulpa cremosa sin hebras. Rico en grasas saludables y potasio. Perfecto para guacamole, tostadas y ensaladas.',
                'precio' => 12.00, 'unidad' => 'unidad', 'peso_gramos' => 200,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1523049673857-eb18f1d7b578'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-007',
                'nombre'    => 'Limones Persas sin Semilla',
                'descripcion' => 'Limones persas grandes y jugosos, sin semillas. Ideales para aderezar comidas, preparar limonadas, ceviches y postres. Muy aromáticos.',
                'precio' => 6.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 120, 'stock_min' => 15,
                'imagen' => $this->img('1582735689369-4fe905f5c5a5'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 9, 'sku' => 'FRU-008',
                'nombre'    => 'Sandía Roja sin Semilla',
                'descripcion' => 'Sandía roja dulce sin semilla, cultivada en clima cálido caribeño. Alta en agua y vitamina A. Fresca y refrescante, ideal para el calor tropical.',
                'precio' => 5.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1568909503-3bb41c9a7f71'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-009',
                'nombre'    => 'Tomates Maduros de Temporada',
                'descripcion' => 'Tomates rojos maduros de cultivo local, firmes y jugosos. Ricos en licopeno y vitamina C. Perfectos para ensaladas, salsas y guisos frescos.',
                'precio' => 8.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 130, 'stock_min' => 15,
                'imagen' => $this->img('1561136594-7f68ecba218b'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-010',
                'nombre'    => 'Cebolla Blanca Grande',
                'descripcion' => 'Cebolla blanca grande de cultivo local, sabor intenso y aroma fuerte. Esencial en la cocina guatemalteca para bases de sopas, guisos y marinadas.',
                'precio' => 7.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 180, 'stock_min' => 20,
                'imagen' => $this->img('1518977956812-cd3dbadaaf31'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-011',
                'nombre'    => 'Ajo Fresco de Cabeza',
                'descripcion' => 'Cabeza de ajo fresco de cultivo nacional, aromatico y con sabor concentrado. Imprescindible para sofritos, marinadas y condimentar toda clase de platillos.',
                'precio' => 5.00, 'unidad' => 'unidad', 'peso_gramos' => 60,
                'stock' => 200, 'stock_min' => 25,
                'imagen' => $this->img('1587049352846-4a222e784d38'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-012',
                'nombre'    => 'Chile Pimiento Rojo',
                'descripcion' => 'Chile pimiento rojo dulce, carnoso y brillante. Rico en vitamina C y antioxidantes. Ideal para ensaladas, stir-fry, relleno o en salsas asadas.',
                'precio' => 12.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 80, 'stock_min' => 10,
                'imagen' => $this->img('1588482854279-80de9c41e0c2'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-013',
                'nombre'    => 'Zanahoria Fresca con Hoja',
                'descripcion' => 'Zanahorias frescas con hoja, cultivadas sin pesticidas. Color naranja intenso, crujientes y dulces. Ricas en betacaroteno y fibra dietética.',
                'precio' => 6.00, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 130, 'stock_min' => 15,
                'imagen' => $this->img('1598170845058-32b9d6a5da37'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-014',
                'nombre'    => 'Papa Blanca de Calidad Premium',
                'descripcion' => 'Papas blancas seleccionadas de tamaño mediano y uniforme. Textura suave al cocerse. Versátiles para freír, hervir, hornear o en sopas.',
                'precio' => 5.50, 'unidad' => 'libra', 'peso_gramos' => 454,
                'stock' => 200, 'stock_min' => 25, 'stock_max' => 500,
                'imagen' => $this->img('1518977676601-b53f82aba655'),
            ],
            [
                'vendor_id' => 8, 'categoria_id' => 10, 'sku' => 'FRU-015',
                'nombre'    => 'Güisquil Verde Tierno',
                'descripcion' => 'Güisquil verde tierno de cultivo local, piel lisa y sin espinas. Verdura muy consumida en Guatemala, ideal en sopas, guisos, ensaladas y relleno.',
                'precio' => 4.00, 'unidad' => 'unidad', 'peso_gramos' => 350,
                'stock' => 100, 'stock_min' => 12,
                'imagen' => $this->img('1540420773420-3366772f4999'),
            ],
        ];
    }
}
