<x-mail::message>
## Tu pago fue confirmado

Recibimos el pago de tu suscripción a **Radar de Licitaciones**. Solo falta crear tu cuenta para activarla.

@php
    $plan = $pending->plan ?? [];
    $amount = !empty($plan['charged_usd']) ? 'US$'.number_format((float)$plan['charged_usd'], 2) : null;
    $companies = $plan['max_companies'] ?? null;
    $users = $plan['max_users'] ?? null;
    $cycle = ($plan['billing_cycle'] ?? 'monthly') === 'annual' ? 'anual' : 'mensual';
@endphp

@if($amount)
**Monto cobrado:** {{ $amount }} ({{ $cycle }})
@endif
@if($companies && $users)
**Plan:** {{ $companies }} empresa(s), {{ $users }} usuarios
@endif

**Referencia de orden:** `{{ $pending->order_number }}`

Haz clic en el botón para continuar con el registro. El enlace es válido por 48 horas.

<x-mail::button :url="$recoveryUrl">
Completar mi registro
</x-mail::button>

Si no solicitaste esta suscripción, ignora este correo y contacta soporte.

---

<small>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>{{ $recoveryUrl }}</small>
</x-mail::message>
