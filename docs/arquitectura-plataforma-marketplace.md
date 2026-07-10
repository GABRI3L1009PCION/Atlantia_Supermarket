# Arquitectura Atlantia Marketplace

Atlantia debe operar como una sola plataforma modular, no como dos aplicaciones separadas.

## Decision

Mantener una sola aplicacion Laravel con varias experiencias:

- Cliente: marketplace, comercios, productos, carrito, checkout, seguimiento y perfil.
- Vendedor/comercio: gestion de productos, inventario, pedidos, zonas, facturacion y reportes.
- Repartidor: disponibilidad, ofertas, recogida, entrega, billetera, historial y soporte.
- Admin Atlantia: control de usuarios, comercios, repartidores, pedidos, pagos, DTE, soporte y auditoria.
- Supermercado Atlantia: comercio oficial dentro del marketplace.

## Por que una sola plataforma

- Evita duplicar login, pedidos, pagos, cupones, facturacion, soporte y notificaciones.
- Permite que cualquier comercio use la misma red de delivery.
- Mantiene un solo carrito y checkout para supermercado, tiendas y emprendedores.
- Facilita reportes y control operativo desde un panel central.

## Separacion interna

La separacion se hace por modulos, rutas y roles:

- `/` y `/catalogo`: experiencia publica de compra.
- `/comercios`: directorio y storefronts de negocios.
- `/cliente`: pedidos, direcciones, seguimiento, perfil y favoritos.
- `/vendedor`: panel del comercio.
- `/repartidor`: app web del repartidor.
- `/admin`: administracion de plataforma.

## Regla de producto

Ninguna pantalla nueva debe asumir que Atlantia Supermarket es el unico negocio. Atlantia Supermarket es un `Vendor` oficial, igual que cualquier otro comercio aprobado.
