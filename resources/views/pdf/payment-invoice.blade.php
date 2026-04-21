<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $payment->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 36px 48px; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; }
        th { background: #f9fafb; font-weight: 600; }
        .totals td { border: none; padding: 4px 0; }
        .totals .label { text-align: right; width: 78%; color: #374151; }
        .totals .amount { text-align: right; font-weight: 700; font-size: 13px; }
        .header { display: table; width: 100%; margin-bottom: 24px; }
        .header-row { display: table-row; }
        .header-cell { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-row">
            <div class="header-cell" style="width: 55%;">
                @if($logoBase64)
                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="" style="height: 48px; width: auto;">
                @endif
                <h1 style="margin-top: 10px;">{{ $appName }}</h1>
                <p class="muted" style="margin: 4px 0 0 0;">
                    {{ $merchant['address_line'] ?? '' }}<br>
                    {{ $merchant['city'] ?? '' }}{{ isset($merchant['country']) ? ', '.$merchant['country'] : '' }}<br>
                    @if(!empty($merchant['email'])){{ $merchant['email'] }}@endif
                    @if(!empty($merchant['phone']))<br>{{ $merchant['phone'] }}@endif
                </p>
            </div>
            <div class="header-cell right">
                <p style="margin: 0; font-size: 12px; font-weight: 700;">Comprobante de pago</p>
                <p class="muted" style="margin: 6px 0 0 0;">No. {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                <p class="muted" style="margin: 4px 0 0 0;">
                    Fecha: {{ $payment->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    <p style="margin: 0 0 4px 0; font-weight: 600;">Cliente</p>
    <p style="margin: 0; color: #374151;">
        {{ $payment->subscription?->owner?->name ?? '—' }}<br>
        {{ $payment->subscription?->owner?->email ?? '' }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="width: 22%;">Método</th>
                <th style="width: 18%; text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Suscripción Radar de Licitaciones
                    @if($payment->notes)
                        <br><span class="muted">{{ $payment->notes }}</span>
                    @endif
                </td>
                <td>{{ $payment->gatewayLabel() }}</td>
                <td style="text-align: right;">{{ $payment->amountFormatted() }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals" style="margin-top: 16px;">
        <tr>
            <td class="label">Total</td>
            <td class="amount" style="width: 22%;">{{ $payment->amountFormatted() }}</td>
        </tr>
    </table>

    @if($payment->gateway_payment_id)
        <p class="muted" style="margin-top: 24px;">Referencia de pago: {{ $payment->gateway_payment_id }}</p>
    @endif

    @if($payment->card_last_four)
        <p class="muted" style="margin-top: 8px;">Tarjeta: ****{{ $payment->card_last_four }}</p>
    @endif

    <p class="muted" style="margin-top: 28px; font-size: 9px;">
        Documento generado electrónicamente. Conserve este comprobante para sus registros.
    </p>
</body>
</html>
