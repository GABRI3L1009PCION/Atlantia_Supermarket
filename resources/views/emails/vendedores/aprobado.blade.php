@extends('emails.layouts.atlantia')

@section('titulo', 'Tu tienda fue aprobada')

@section('contenido')
    <tr>
        <td style="padding:30px 34px 0;">
            <p style="margin:0 0 8px;color:#047857;font-size:13px;font-weight:700;text-transform:uppercase;">Solicitud aprobada</p>
            <h1 style="margin:0;color:#2a0f12;font-size:26px;line-height:1.25;">¡Bienvenido a Atlantia, {{ $vendor->business_name }}!</h1>
            <p style="margin:14px 0 0;color:#5c4650;font-size:15px;line-height:1.6;">
                Hola {{ $user->name }}, revisamos tus documentos y tu tienda ya puede vender en Atlantia Store.
                Solo falta crear tu contraseña para entrar a tu panel de vendedor.
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 34px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f7;border:1px solid #e6c7d2;border-radius:10px;">
                <tr>
                    <td style="padding:16px 18px;font-size:14px;color:#2a0f12;line-height:1.8;">
                        <strong>Tu usuario:</strong> {{ $user->email }}<br>
                        <strong>Código de tu tienda:</strong> {{ $vendor->application_code }}<br>
                        @php
                            $planCorreo = \App\Services\Monetizacion\PlanesVendedorService::datos((string) ($vendor->plan ?: \App\Services\Monetizacion\PlanesVendedorService::base()));
                            $planConMensualidad = (float) ($planCorreo['precio'] ?? 0) > 0;
                        @endphp
                        <strong>Tu plan:</strong> {{ $planCorreo['nombre'] }}{{ $planConMensualidad ? ' · Q '.number_format((float) $planCorreo['precio'], 2).' al mes' : ' · sin mensualidad' }}<br>
                        <strong>Comisión por venta:</strong> {{ rtrim(rtrim(number_format((float) $vendor->commission_percentage, 2), '0'), '.') }} %
                        @if ($planConMensualidad && ! $vendor->enPrueba())
                            <br><span style="color:#5e0008;"><strong>Para activar tu plan {{ $planCorreo['nombre'] }}:</strong> entra a tu panel → Mi plan → Pagar mensualidad. Mientras tanto ya puedes vender con el plan Inicial.</span>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:26px 34px 8px;">
            <a href="{{ $enlace }}" style="display:inline-block;background:#5e0008;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:14px 30px;border-radius:10px;">Crear mi contraseña</a>
            <p style="margin:12px 0 0;color:#6d5962;font-size:12px;">El enlace sirve una sola vez y vence en {{ $horas }} horas.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:18px 34px 28px;">
            <p style="margin:0 0 8px;color:#2a0f12;font-size:14px;font-weight:700;">Qué sigue:</p>
            <ol style="margin:0;padding-left:20px;color:#5c4650;font-size:14px;line-height:1.7;">
                <li>Crea una contraseña segura.</li>
                <li>Escanea un código QR con Google Authenticator: protege tu cuenta y tus pagos.</li>
                <li>Guarda tus códigos de respaldo y entra a tu panel para subir tus productos.</li>
            </ol>
            <p style="margin:16px 0 0;color:#6d5962;font-size:12px;line-height:1.6;">
                Atlantia nunca te pedirá tu contraseña por correo ni por teléfono. Si el enlace venció, pídelo de nuevo en la página de
                seguimiento de tu solicitud.
            </p>
        </td>
    </tr>
@endsection
