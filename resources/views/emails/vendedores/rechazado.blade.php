@extends('emails.layouts.atlantia')

@section('titulo', 'Sobre tu solicitud')

@section('contenido')
    <tr>
        <td style="padding:30px 34px 0;">
            <p style="margin:0 0 8px;color:#6e000a;font-size:13px;font-weight:700;text-transform:uppercase;">Solicitud {{ $vendor->application_code }}</p>
            <h1 style="margin:0;color:#2a0f12;font-size:24px;line-height:1.25;">Por ahora no pudimos aprobar tu solicitud</h1>
            <p style="margin:14px 0 0;color:#5c4650;font-size:15px;line-height:1.6;">
                Hola {{ $user->name }}, gracias por querer vender {{ $vendor->business_name }} en Atlantia Store.
                Revisamos tu información y esta vez no fue posible aprobarla.
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 34px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f7;border:1px solid #e6c7d2;border-radius:10px;">
                <tr>
                    <td style="padding:16px 18px;font-size:14px;color:#2a0f12;line-height:1.6;">
                        <strong>Motivo:</strong><br>{{ $vendor->rejection_reason ?: 'No se especificó un motivo.' }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 34px 28px;">
            <p style="margin:0;color:#5c4650;font-size:14px;line-height:1.6;">
                Puedes corregir lo indicado y enviar una nueva solicitud cuando quieras. Si tienes dudas, responde a este correo y con gusto te ayudamos.
            </p>
            <p style="margin:18px 0 0;" align="center">
                <a href="{{ route('vendedor.solicitar.create') }}" style="display:inline-block;background:#5e0008;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:12px 26px;border-radius:10px;">Enviar una nueva solicitud</a>
            </p>
        </td>
    </tr>
@endsection
