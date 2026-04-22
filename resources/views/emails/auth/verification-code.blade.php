<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Codigo de verificacion</title>
</head>
<body style="margin:0;background:#f8e7ee;font-family:Arial,Helvetica,sans-serif;color:#2a1018;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8e7ee;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #ead0da;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:30px 28px 16px;">
                            <img
                                src="{{ asset(file_exists(public_path('images/logo.png')) ? 'images/logo.png' : 'images/atlantia-logo.svg') }}"
                                alt="Atlantia Supermarket"
                                style="max-width:260px;height:auto;"
                            >
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 34px 0;">
                            <p style="margin:0 0 8px;color:#8b1d4d;font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                                Atlantia Supermarket
                            </p>
                            <h1 style="margin:0;color:#2a1018;font-size:28px;line-height:1.2;">
                                Verifica tu correo
                            </h1>
                            <p style="margin:14px 0 0;color:#5c4650;font-size:15px;line-height:1.6;">
                                Hola {{ $user->name }}, usa este codigo para confirmar tu cuenta y continuar comprando
                                en Atlantia Supermarket.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 34px;">
                            <div style="background:#fff7f9;border:1px solid #e6c7d2;border-radius:10px;padding:22px;text-align:center;">
                                <p style="margin:0 0 10px;color:#7a1f3d;font-size:13px;font-weight:700;text-transform:uppercase;">
                                    Codigo de verificacion
                                </p>
                                <p style="margin:0;color:#2a1018;font-size:38px;font-weight:800;letter-spacing:10px;">
                                    {{ $code }}
                                </p>
                                <p style="margin:14px 0 0;color:#6d5962;font-size:13px;">
                                    Este codigo vence en {{ $expiresInMinutes }} minutos.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 34px 30px;">
                            <p style="margin:0;color:#5c4650;font-size:14px;line-height:1.6;">
                                Si no solicitaste esta cuenta, puedes ignorar este correo. Nunca compartas este codigo
                                con otra persona.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#7a1f3d;padding:18px 34px;text-align:center;">
                            <p style="margin:0;color:#ffffff;font-size:13px;font-weight:700;">
                                Santo Tomas de Castilla y Puerto Barrios, Izabal
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
