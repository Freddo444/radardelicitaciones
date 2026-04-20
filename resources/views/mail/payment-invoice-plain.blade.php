Gracias por su pago — {{ config('app.name') }}

Adjuntamos su comprobante en PDF (factura No. {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}).

Monto: {{ $payment->amountFormatted() }}
Método: {{ $payment->gatewayLabel() }}
Fecha: {{ $payment->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}

Soporte: {{ config('services.support.email') }}
