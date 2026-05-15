<x-mail::message>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 28px 0; border-collapse: collapse;">
<tr>
<td style="background-color: #1e40af; background-image: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #1d4ed8 100%); border-radius: 10px; padding: 22px 24px;">
<p style="margin: 0; font-size: 11px; font-weight: 600; letter-spacing: 0.16em; text-transform: uppercase; color: rgba(255,255,255,0.88);">
{{ config('app.name') }}
</p>
<p style="margin: 10px 0 0; font-size: 19px; font-weight: 700; line-height: 1.25; color: #ffffff; letter-spacing: -0.03em;">
Menos ruido. Más oportunidades que importan.
</p>
<p style="margin: 10px 0 0; font-size: 14px; line-height: 1.5; color: rgba(255,255,255,0.92);">
Monitoreo inteligente de licitaciones públicas para equipos que ya compiten — o están listos para hacerlo.
</p>
</td>
</tr>
</table>

## Hola {{ $companyName }},

En compras públicas, **enterarse tarde** de una convocatoria suele significar **una oportunidad menos** — aunque el equipo haya trabajado bien en otras cosas.

<p style="margin: 0 0 8px; font-size: 15px; font-weight: 600; color: #18181b;">
{{ config('app.name') }} le ayuda a:
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 16px 0 4px; border-collapse: separate; border-spacing: 0 10px;">
<tr>
<td>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e4e4e7; border-radius: 10px; background-color: #fafafa;">
<tr>
<td style="padding: 16px 18px; border-left: 4px solid #2563eb; border-radius: 10px;">
<p style="margin: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #2563eb;">01 — Detectar</p>
<p style="margin: 8px 0 0; font-size: 15px; line-height: 1.5; color: #3f3f46;">Procesos que encajan con lo que su empresa ofrece, sin depender solo de revisar el portal a mano.</p>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e4e4e7; border-radius: 10px; background-color: #fafafa;">
<tr>
<td style="padding: 16px 18px; border-left: 4px solid #3b82f6; border-radius: 10px;">
<p style="margin: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #3b82f6;">02 — Analizar</p>
<p style="margin: 8px 0 0; font-size: 15px; line-height: 1.5; color: #3f3f46;">Pliegos más rápido con IA como apoyo (no sustituto del criterio jurídico y técnico de ustedes).</p>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e4e4e7; border-radius: 10px; background-color: #fafafa;">
<tr>
<td style="padding: 16px 18px; border-left: 4px solid #1d4ed8; border-radius: 10px;">
<p style="margin: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #1d4ed8;">03 — Organizar</p>
<p style="margin: 8px 0 0; font-size: 15px; line-height: 1.5; color: #3f3f46;">Seguimiento y plazos sin perder versiones de documentos.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>

<x-mail::panel>
Puede <strong>probar Radar gratis</strong> hoy y comprobar en casos reales si les ahorra tiempo frente al flujo actual.
</x-mail::panel>

<x-mail::button :url="$trackingUrl">
Probar gratis ahora
</x-mail::button>

<p style="margin: 20px 0 0; font-size: 15px; line-height: 1.55; color: #52525b;">
Si ya compiten en licitaciones — o están dando los primeros pasos — esta herramienta está hecha para <strong>reducir fricción</strong> y <strong>detectar oportunidades antes</strong>.
</p>

<hr style="border: none; border-top: 1px solid #e4e4e7; margin: 28px 0 20px;">

<p style="margin: 0; font-size: 15px; line-height: 1.55; color: #3f3f46;">
Saludos,<br>
<span style="font-weight: 700; color: #18181b;">Equipo {{ config('app.name') }}</span>
</p>

<p class="email-muted" style="margin-top: 24px; font-size: 13px;">
Si el botón no funciona, copie y pegue este enlace en su navegador:<br>
<span class="break-all">{{ $trackingUrl }}</span>
</p>

<p class="email-muted" style="font-size: 12px; line-height: 1.5;">
Recibió este mensaje porque su empresa aparece como proveedor en datos públicos de compras. Si no desea más correos como este, responda con «baja» y lo tendremos en cuenta.
</p>
</x-mail::message>
