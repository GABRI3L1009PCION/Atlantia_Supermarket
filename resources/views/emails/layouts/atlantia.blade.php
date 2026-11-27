<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Pide a los clientes de correo que no inviertan los colores en modo oscuro. --}}
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>@yield('titulo', 'Atlantia Store')</title>
    <style>
        :root { color-scheme: light only; supported-color-schemes: light only; }
    </style>
</head>
<body style="margin:0;background:#f4f0f0;font-family:Arial,Helvetica,sans-serif;color:#2a0f12;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f0f0;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e3d6da;border-radius:12px;overflow:hidden;">
                    <tr>
                        {{-- El degradado (aunque sea de un solo color) evita que Gmail cambie el vino por rosado en modo oscuro. --}}
                        <td align="center" bgcolor="#5e0008" style="background-color:#5e0008;background-image:linear-gradient(135deg,#3a0004 0%,#5e0008 60%,#7a0f2a 100%);padding:24px 28px;">
                            <span style="font-family:Arial,Helvetica,sans-serif;font-size:26px;font-weight:800;letter-spacing:0.5px;color:#ffffff;">Atlantia Store</span>
                        </td>
                    </tr>
                    @yield('contenido')
                    <tr>
                        <td bgcolor="#3a0004" style="background-color:#3a0004;background-image:linear-gradient(#3a0004,#3a0004);padding:16px 34px;text-align:center;">
                            <p style="margin:0;color:#ffffff;font-size:12px;font-weight:700;">Atlantia Store · Puerto Barrios y Santo Tomás de Castilla, Izabal</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
