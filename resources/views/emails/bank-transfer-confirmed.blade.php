<x-mail::message>
# ¡Pago confirmado!

Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Confirmamos tu transferencia bancaria y **tu cuenta en {{ config('app.name') }} ya está activa**. Puedes empezar a monitorear licitaciones del DGCP de inmediato.

<x-mail::panel>
Pago recibido: **US${{ number_format($amount, 2) }}**
@if($periodEnd)
<br>Tu suscripción está vigente hasta el **{{ $periodEnd->isoFormat('D [de] MMMM, YYYY') }}**.
@endif
</x-mail::panel>

<x-mail::button :url="$url" color="primary">
Ir a mi panel
</x-mail::button>

Gracias por confiar en nosotros. Si tienes cualquier duda, responde a este correo.

Saludos,<br>
Equipo {{ config('app.name') }}
</x-mail::message>
