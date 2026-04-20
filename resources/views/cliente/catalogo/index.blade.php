<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main style="max-width: 1200px; margin: 0 auto; padding: 24px;">
    <h1>Catálogo</h1>

    <form method="GET" style="margin: 16px 0;">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Buscar productos..."
            style="padding: 10px; width: 280px;"
        >
        <button type="submit" style="padding: 10px 16px;">Buscar</button>
    </form>

    @if(isset($catalogo) && $catalogo->count())
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
            @foreach($catalogo as $producto)
                <article style="border: 1px solid #ddd; border-radius: 12px; padding: 16px;">
                    <h2 style="margin-top: 0;">
                        {{ $producto->nombre ?? 'Producto sin nombre' }}
                    </h2>

                    <p><strong>SKU:</strong> {{ $producto->sku ?? 'N/A' }}</p>

                    @if(!is_null($producto->precio ?? null))
                        <p><strong>Precio:</strong> Q {{ number_format((float) $producto->precio, 2) }}</p>
                    @elseif(!is_null($producto->precio_venta ?? null))
                        <p><strong>Precio:</strong> Q {{ number_format((float) $producto->precio_venta, 2) }}</p>
                    @endif

                    @if(!empty($producto->descripcion_corta))
                        <p>{{ $producto->descripcion_corta }}</p>
                    @elseif(!empty($producto->descripcion))
                        <p>{{ \Illuminate\Support\Str::limit($producto->descripcion, 120) }}</p>
                    @endif
                </article>
            @endforeach
        </div>

        <div style="margin-top: 24px;">
            {{ $catalogo->links() }}
        </div>
    @else
        <p>No hay productos disponibles.</p>
    @endif
</main>
</body>
</html><?php
