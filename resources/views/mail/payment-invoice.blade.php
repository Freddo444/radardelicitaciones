<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de pago</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:ui-sans-serif,system-ui,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f5;padding:24px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <tr>
                    <td style="padding:28px 28px 8px 28px;">
                        @php
                            $logoPath = public_path('images/badgeonly.png');
                        @endphp
                        @if(file_exists($logoPath))
                            <img src="{{ $message->embed($logoPath) }}" alt="{{ config('app.name') }}" style="height:44px;width:auto;display:block;">
                        @endif
                        <h1 style="margin:16px 0 8px 0;font-size:20px;font-weight:700;color:#18181b;">Gracias por su pago</h1>
                        <p style="margin:0;font-size:15px;line-height:1.55;color:#3f3f46;">
                            Adjuntamos su comprobante en PDF (factura No. {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}).
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 28px 28px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e4e4e7;border-radius:8px;">
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#52525c;border-bottom:1px solid #e4e4e7;">
                                    <strong style="color:#18181b;">Monto</strong><br>
                                    {{ $payment->amountFormatted() }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#52525c;border-bottom:1px solid #e4e4e7;">
                                    <strong style="color:#18181b;">Método</strong><br>
                                    {{ $payment->gatewayLabel() }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#52525c;">
                                    <strong style="color:#18181b;">Fecha</strong><br>
                                    {{ $payment->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                                </td>
                            </tr>
                        </table>
                        <p style="margin:20px 0 0 0;font-size:12px;line-height:1.5;color:#71717a;">
                            Si tiene preguntas, responda a este correo o escriba a
                            <a href="mailto:{{ config('services.support.email') }}" style="color:#4f46e5;">{{ config('services.support.email') }}</a>.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
