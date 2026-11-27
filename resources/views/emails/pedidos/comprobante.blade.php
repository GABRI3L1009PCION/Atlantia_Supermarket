@extends('emails.layouts.atlantia')

@section('titulo', 'Tu comprobante de compra')

@section('contenido')
    <tr>
        <td style="padding:30px 34px 0;">
            <p style="margin:0 0 8px;color:#5e0008;font-size:13px;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">Comprobante de compra</p>
            <h1 style="margin:0;color:#2a0f12;font-size:24px;line-height:1.3;">¡Gracias por tu compra, {{ $cliente }}!</h1>
            <p style="margin:14px 0 0;color:#5c4650;font-size:15px;line-height:1.6;">
                Te enviamos el comprobante de tu compra en <strong>{{ $tienda }}</strong>. Lo encuentras adjunto en PDF y también puedes descargarlo cuando quieras desde tu pedido.
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 34px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf7f7;border:1px solid #e3d6da;border-left:4px solid #5e0008;border-radius:10px;">
                <tr>
                    <td style="padding:16px 18px;font-size:14px;color:#2a0f12;line-height:1.8;">
                        <strong>Pedido:</strong> #{{ $padre->numero_pedido }}<br>
                        <strong>Tienda:</strong> {{ $tienda }}<br>
                        <strong>Productos:</strong> {{ $parte->items->count() }} {{ $parte->items->count() === 1 ? 'producto' : 'productos' }}<br>
                        <strong>Total:</strong> Q {{ number_format((float) $parte->total, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:24px 34px 6px;">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr><td bgcolor="#5e0008" style="background-color:#5e0008;background-image:linear-gradient(#5e0008,#5e0008);border-radius:10px;"><a href="{{ $urlPedido }}" style="display:inline-block;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 28px;">Ver mi pedido</a></td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:18px 34px 28px;">
            <p style="margin:0;color:#6d5962;font-size:12px;line-height:1.6;">
                Este comprobante no sustituye una factura. Si necesitas factura, pídela a la tienda con tu NIT; cuando la emita, aparecerá en tu pedido.
                @if (config('atlantia.support.email'))
                    ¿Dudas? Escríbenos a <a href="mailto:{{ config('atlantia.support.email') }}" style="color:#5e0008;">{{ config('atlantia.support.email') }}</a>
                    @if (config('atlantia.support.phone'))
                        o llama al {{ config('atlantia.support.phone') }}
                    @endif
                @endif
            </p>
        </td>
    </tr>
@endsection
