<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifica tu correo electrónico de DocTotal</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f7fb;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 16px 40px rgba(15,23,42,.10);">
                <tr>
                    <td style="padding:30px 34px;background:linear-gradient(135deg,#0756e8 0%,#2563eb 45%,#6d28d9 100%);">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td width="72" valign="middle" style="padding-right:14px;">
                                    <img src="{{ $message->embed(public_path('images/branding/doctotal-icon.png')) }}" alt="DocTotal" width="60" height="60" style="display:block;width:60px;height:60px;border:0;outline:none;text-decoration:none;">
                                </td>
                                <td valign="middle">
                                    <div style="font-size:25px;line-height:30px;font-weight:800;color:#ffffff;letter-spacing:-.5px;">DocTotal</div>
                                    <div style="margin-top:3px;font-size:11px;line-height:16px;color:#dbeafe;letter-spacing:.7px;text-transform:uppercase;">Gestión médica inteligente</div>
                                </td>
                            </tr>
                        </table>
                        <div style="margin-top:26px;font-size:13px;line-height:20px;color:#dbeafe;">Seguridad de tu cuenta</div>
                        <div style="margin-top:4px;font-size:26px;line-height:34px;font-weight:800;color:#ffffff;">Confirma tu correo electrónico</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:36px 34px 12px;">
                        <div style="font-size:24px;line-height:32px;font-weight:800;color:#0f172a;">¡Hola{{ filled($user->name) ? ', ' . $user->name : '' }}!</div>
                        <p style="margin:16px 0 0;font-size:15px;line-height:25px;color:#475569;">
                            Para proteger tu cuenta y confirmar que este correo te pertenece, verifica tu dirección de acceso a <strong style="color:#2563eb;">DocTotal</strong>.
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:28px auto;">
                            <tr>
                                <td align="center" style="border-radius:12px;background:#2563eb;">
                                    <a href="{{ $url }}" style="display:inline-block;padding:15px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:12px;background:#2563eb;">Verificar mi correo</a>
                                </td>
                            </tr>
                        </table>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:8px;">
                            <tr>
                                <td style="padding:14px 16px;border:1px solid #bfdbfe;border-radius:14px;background:#eff6ff;font-size:13px;line-height:20px;color:#1e40af;">
                                    Por seguridad, el enlace está firmado y solo puede utilizarse para verificar esta cuenta.
                                </td>
                            </tr>
                        </table>
                        <p style="margin:22px 0 0;font-size:13px;line-height:21px;color:#64748b;">Si no creaste una cuenta en DocTotal, puedes ignorar este mensaje.</p>
                        <p style="margin:28px 0 0;font-size:14px;line-height:22px;color:#475569;">Saludos,<br><strong style="color:#0f172a;">Equipo DocTotal</strong></p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 34px 30px;">
                        <div style="border-top:1px solid #e2e8f0;padding-top:20px;font-size:11px;line-height:18px;color:#94a3b8;">
                            Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                            <a href="{{ $url }}" style="color:#2563eb;word-break:break-all;text-decoration:none;">{{ $url }}</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:22px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <img src="{{ $message->embed(public_path('images/branding/doctotal-icon.png')) }}" alt="DocTotal" width="28" height="28" style="display:block;width:28px;height:28px;margin:0 auto 7px;border:0;outline:none;text-decoration:none;">
                        <div style="font-size:12px;line-height:18px;font-weight:700;color:#334155;">DocTotal</div>
                        <div style="margin-top:3px;font-size:11px;line-height:18px;color:#94a3b8;">Tu consultorio, todo en un solo lugar.</div>
                        <div style="margin-top:8px;font-size:10px;line-height:16px;color:#cbd5e1;">© {{ date('Y') }} DocTotal. Todos los derechos reservados.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
