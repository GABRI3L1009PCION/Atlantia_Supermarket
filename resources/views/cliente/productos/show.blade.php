<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $producto->nombre ?? 'Producto' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main style="max-width: 900px; margin: 0 auto; padding: 24px;">
    <p><a href="{{ url('/') }}">← Volver</a></p>

    <h1>{{ $producto->nombre ?? 'Producto sin nombre' }}</h1>

    <p><strong>SKU:</strong> {{ $producto->sku ?? 'N/A' }}</p>

    @if(!is_null($producto->precio ?? null))
        <p><strong>Precio:</strong> Q {{ number_format((float) $producto->precio, 2) }}</p>
    @elseif(!is_null($producto->precio_venta ?? null))
        <p><strong>Precio:</strong> Q {{ number_format((float) $producto->precio_venta, 2) }}</p>
    @endif

    @if(!empty($producto->descripcion))
        <p>{{ $producto->descripcion }}</p>
    @elseif(!empty($producto->descripcion_corta))
        <p>{{ $producto->descripcion_corta }}</p>
    @endif
</main>
</body>
</html><?php
