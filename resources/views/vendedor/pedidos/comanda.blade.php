<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comanda {{ $pedido->numero_pedido }}</title>
    <style @nonce>
        /* Ticket de 80 mm para impresoras termicas; tambien imprime bien en carta. */
        body { margin: 0; font-family: ui-monospace, Menlo, Consolas, monospace; color: #000; background: #fff; }
        .ticket { width: 72mm; margin: 0 auto; padding: 4mm 0; font-size: 12px; }
        h1 { margin: 0; font-size: 18px; text-align: center; }
        .centro { text-align: center; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .cant { width: 12mm; font-weight: 700; font-size: 14px; }
        .notas { white-space: pre-line; }
        .acciones { text-align: center; margin: 12px 0; }
        @media print { .acciones { display: none; } @page { margin: 0; } }
    </style>
</head>
<body>
    <div class="ticket">
        <h1>COMANDA</h1>
        <p class="centro"><strong>{{ $pedido->numero_pedido }}</strong><br>{{ ($pedido->created_at)->format('d/m/Y h:i a') }}</p>
        <p class="centro">{{ strtoupper(str_replace('_', ' ', $pedido->tipo_entrega ?? 'domicilio')) }}</p>
        <hr>
        <table>
            @foreach ($pedido->items as $item)
                <tr>
                    <td class="cant">{{ $item->cantidad }}x</td>
                    <td>{{ $item->producto_nombre_snapshot }}@if ($item->producto_sku_snapshot)<br><small>SKU {{ $item->producto_sku_snapshot }}</small>@endif</td>
                </tr>
            @endforeach
        </table>
        <hr>
        <p><strong>Cliente:</strong> {{ $pedido->direccion?->nombre_contacto }}<br>
            <strong>Tel:</strong> {{ $pedido->direccion?->telefono_contacto }}</p>
        @if ($pedido->notas)
            <hr>
            <p class="notas"><strong>Notas:</strong> {{ $pedido->notas }}</p>
        @endif
        <hr>
        <p class="centro">Total Q {{ number_format((float) $pedido->total, 2) }}</p>
        <div class="acciones"><button type="button" id="imprimir">Imprimir</button></div>
    </div>
    <script @nonce>
        document.getElementById('imprimir').addEventListener('click', () => window.print());
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
