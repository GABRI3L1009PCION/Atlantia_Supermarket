<?php

/*
|--------------------------------------------------------------------------
| Monetizacion de Atlantia
|--------------------------------------------------------------------------
|
| Precios en quetzales, IVA incluido, cobrados mes a mes. Se pueden ajustar sin tocar codigo.
|
| Cada plan es un local digital dentro de Atlantia. El plan Inicial no paga
| mensualidad y deja una comision por venta; los demas pagan solo su mensualidad.
| El plan Personalizado se cotiza y lo configura administracion para cada tienda.
| Ningun plan tiene productos ilimitados.
|
*/

return [

    'planes' => [
        'inicial' => [
            'nombre' => 'Inicial',
            'tipo' => '1 local digital',
            'precio' => 0,
            'comision' => 7,
            'max_productos' => 15,
            'max_promociones' => 1,
            'carga_masiva' => false,
            'destacados_incluidos' => 0,
            'banners_incluidos' => 0,
            'prioridad_listado' => false,
            'liquidacion' => 'Liquidación mensual',
            'soporte' => 'Soporte por correo',
            'resumen' => 'Empieza sin mensualidad: solo pagas el 7 % cuando vendes.',
            'beneficios' => [
                '1 local digital',
                'Hasta 15 productos',
                'Q 0 al mes + 7 % por venta',
                '1 promoción activa',
                'Repartidores de Atlantia y pagos con tarjeta',
            ],
        ],
        'esencial' => [
            'nombre' => 'Esencial',
            'tipo' => '1 local digital',
            'precio' => 199,
            'comision' => 0,
            'max_productos' => 75,
            'max_promociones' => 3,
            'carga_masiva' => false,
            'destacados_incluidos' => 0,
            'banners_incluidos' => 0,
            'prioridad_listado' => false,
            'liquidacion' => 'Liquidación quincenal',
            'soporte' => 'Soporte por chat',
            'resumen' => 'Para emprendimientos que ya venden seguido.',
            'beneficios' => [
                '1 local digital',
                'Hasta 75 productos',
                'Sin comisión por venta',
                '3 promociones activas',
                'Tu tienda con logo y portada',
            ],
        ],
        'profesional' => [
            'nombre' => 'Profesional',
            'tipo' => '1 local digital',
            'precio' => 349,
            'comision' => 0,
            'max_productos' => 200,
            'max_promociones' => null,
            'carga_masiva' => true,
            'destacados_incluidos' => 0,
            'banners_incluidos' => 0,
            'prioridad_listado' => false,
            'recomendado' => true,
            'liquidacion' => 'Liquidación semanal',
            'soporte' => 'Soporte por chat',
            'resumen' => 'Para negocios establecidos con catálogo mediano.',
            'beneficios' => [
                '1 local digital',
                'Hasta 200 productos',
                'Sin comisión por venta',
                'Promociones ilimitadas y ofertas relámpago',
                'Carga masiva de productos con Excel',
                'Reportes de ventas',
            ],
        ],
        'avanzado' => [
            'nombre' => 'Avanzado',
            'tipo' => '1 local digital',
            'precio' => 499,
            'comision' => 0,
            'max_productos' => 600,
            'max_promociones' => null,
            'carga_masiva' => true,
            'destacados_incluidos' => 1,
            'banners_incluidos' => 0,
            'prioridad_listado' => false,
            'liquidacion' => 'Liquidación semanal',
            'soporte' => 'Soporte prioritario',
            'resumen' => 'Para tiendas con catálogo grande que quieren más visibilidad.',
            'beneficios' => [
                '1 local digital',
                'Hasta 600 productos',
                'Todo lo del plan Profesional',
                '1 semana al mes de oferta destacada incluida',
            ],
        ],
        'corporativo' => [
            'nombre' => 'Corporativo',
            'tipo' => '1 local digital',
            'precio' => 699,
            'comision' => 0,
            'max_productos' => 1500,
            'max_promociones' => null,
            'carga_masiva' => true,
            'destacados_incluidos' => 2,
            'banners_incluidos' => 1,
            'prioridad_listado' => true,
            'liquidacion' => 'Liquidación semanal',
            'soporte' => 'Asesor asignado',
            'resumen' => 'Para supermercados, farmacias y ferreterías.',
            'beneficios' => [
                '1 local digital',
                'Hasta 1,500 productos',
                'Todo lo del plan Avanzado',
                '2 semanas al mes de oferta destacada incluidas',
                '1 semana al mes de banner en la portada incluida',
                'Primero en el listado de tiendas, con insignia',
            ],
        ],
        'personalizado' => [
            'nombre' => 'Personalizado',
            'tipo' => '1 local digital',
            'precio' => null,
            'comision' => 0,
            'max_productos' => null,
            'max_promociones' => null,
            'carga_masiva' => true,
            'destacados_incluidos' => 2,
            'banners_incluidos' => 1,
            'prioridad_listado' => true,
            'cotizacion' => true,
            'liquidacion' => 'Liquidación acordada',
            'soporte' => 'Asesor asignado',
            'resumen' => 'Cadenas y negocios grandes: precio y cantidad de productos a tu medida.',
            'beneficios' => [
                '1 local digital',
                'Cantidad de productos acordada',
                'Precio bajo cotización',
                'Todo lo del plan Corporativo',
            ],
        ],
    ],

    /*
    | Promocion de apertura: cada tienda usa cualquier plan gratis estos dias, una sola vez.
    | En el plan Inicial significa no pagar comision esos dias. Se activa y ajusta desde
    | el panel del administrador (Precios de planes).
    */
    'apertura' => [
        'activa' => false,
        'dias' => 30,
        'hasta' => null,
    ],

    /* Plan para tiendas nuevas y para las que dejan de pagar su mensualidad. */
    'plan_base' => 'inicial',

    /*
    | Tarifa de la pasarela (Recurrente) que se descuenta al vendedor en ventas con tarjeta.
    | Se cobra POR TRANSACCION: el porcentaje sobre la venta de su tienda y el fijo por pago;
    | si en un mismo pago hubo varias tiendas, el fijo se reparte entre ellas.
    | Fuente: recurrente.com/precios (cuenta comercial, 4.5 % + Q2, IVA incluido).
    */
    'pasarela' => [
        'porcentaje' => (float) env('ATLANTIA_PASARELA_PORCENTAJE', 4.5),
        'fijo' => (float) env('ATLANTIA_PASARELA_FIJO', 2.0),
        'metodos' => ['tarjeta'],
    ],

    /*
    | Margen sugerido para los productos de Atlantia Store (sobre el precio de venta).
    | Con un pedido minimo de Q50 la tarjeta cuesta 4.5 % + Q2 = 8.5 % del pedido;
    | 15 % deja unos 6.5 puntos para servidor, operacion y ganancia.
    */
    'margen_sugerido' => (float) env('ATLANTIA_MARGEN_SUGERIDO', 15),

    /* Monto minimo para que el vendedor pueda solicitar su pago. */
    'pago_minimo_vendedor' => (float) env('ATLANTIA_PAGO_MINIMO_VENDEDOR', 0),

    /* Publicidad. Precio por posicion del carrusel (1 = la primera que se ve). */
    'banners' => [
        'posiciones' => [
            1 => ['semana' => 250, 'mes' => 800],
            2 => ['semana' => 175, 'mes' => 550],
            3 => ['semana' => 125, 'mes' => 400],
        ],
    ],

    /* Oferta destacada en el aviso semanal de novedades y ofertas. */
    'destacado' => ['semana' => 50, 'maximo_por_semana' => 3],

    /* Producto patrocinado arriba de los resultados de busqueda y categoria. */
    'patrocinado' => ['semana' => 35, 'maximo_en_resultados' => 2],

    /* Atlantia Envios (mensajeria para particulares). El repartidor gana el 70 %. */
    'envios' => [
        'mismo_municipio' => ['Santo Tomas' => 25, 'Puerto Barrios' => 30, 'default' => 30],
        'entre_municipios' => 35,
        'tamanos' => [
            'sobre' => ['nombre' => 'Sobre o documentos', 'recargo' => 0],
            'pequeno' => ['nombre' => 'Paquete pequeño (cabe en una mochila)', 'recargo' => 0],
            'mediano' => ['nombre' => 'Paquete mediano (hasta 10 lb)', 'recargo' => 10],
        ],
    ],

    /* Atlantia Eventos: se muestran los productos (combos) de esta categoria. */
    'eventos' => ['categoria_slug' => env('ATLANTIA_EVENTOS_CATEGORIA', 'eventos-y-fiestas')],

    /* Aviso de novedades y ofertas a clientes que lo aceptaron (dias: 0 = domingo). */
    'novedades' => [
        'dias' => [2, 5],
        'hora' => '10:00',
    ],
];
