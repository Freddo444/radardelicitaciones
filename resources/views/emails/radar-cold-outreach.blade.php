<x-mail::message>
Hola {{ $greeting }},

Soy **Frederick López**, desarrollador dominicano. Construí {{ config('app.name') }} porque vi a constructoras y proveedores en el país perder procesos del DGCP por enterarse tarde — o no enterarse del todo.

**Lo que hace, en una línea:** monitorea las compras públicas del DGCP en tiempo real, le avisa por correo o Telegram cuando aparece algo que encaja con {{ $companyName }}, y le ayuda a analizar pliegos con IA.

Si ya compiten en licitaciones (o están dando los primeros pasos), pueden probarlo **14 días gratis**. Toma 2 minutos abrir cuenta.

<x-mail::button :url="$trackingUrl" color="primary">
Probar 14 días gratis
</x-mail::button>

Si prefiere, **responda directo a este correo** — yo soy el que lee.

<p style="margin: 24px 0 0; font-size: 15px; line-height: 1.55; color: #18181b;">
Saludos,<br>
<strong>Frederick López</strong><br>
<span style="color: #71717a;">Fundador · <a href="https://radardelicitaciones.com" style="color: #2563eb; text-decoration: none;">radardelicitaciones.com</a></span>
</p>

<hr style="border: none; border-top: 1px solid #e4e4e7; margin: 28px 0 18px;">

<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #3f3f46;">
<strong>P.D.</strong> Si responde con el rubro o actividad principal de {{ $companyName }}, le envío gratis un reporte de las licitaciones más relevantes en su sector que cerraron este mes — sin que tenga que abrir cuenta.
</p>

<p class="email-muted" style="margin-top: 24px; font-size: 12px; line-height: 1.5;">
Recibió este mensaje porque su empresa figura en datos públicos de compras del DGCP. Si prefiere no recibir más correos como este, responda «baja» y lo retiramos.
</p>
</x-mail::message>
