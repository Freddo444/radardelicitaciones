<x-mail::message>
Hola {{ $greeting }},

Soy **Frederick López**. Quizás coincidimos por correo desde **Constructora AG**.

Después de años en el sector viendo de primera mano cómo se nos escapaban procesos del DGCP que claramente nos hubieran encajado — simplemente por no enterarse a tiempo — construí {{ config('app.name') }} para resolver ese problema de forma sistemática.

**Lo que hace, en una línea:** monitorea las compras públicas del DGCP en tiempo real y le avisa por correo o Telegram cuando aparece algo que encaja con {{ $companyName }}.

Más allá del aviso, cubre todo el flujo: análisis de pliegos con IA, pre-llenado de formularios oficiales y gestor documental — todo el proceso de licitación en una sola plataforma.

Si ya compiten en licitaciones (o están dando los primeros pasos), pueden probarlo **14 días gratis**. Toma 2 minutos abrir cuenta.

<x-mail::button :url="$trackingUrl" color="primary">
Probar 14 días gratis
</x-mail::button>

Si prefiere, **responda directo a este correo** — yo soy el que lee.

<p style="margin: 24px 0 0; font-size: 15px; line-height: 1.55; color: #18181b;">
Saludos,<br>
<strong>Frederick López</strong><br>
<span style="color: #71717a;"><a href="https://radardelicitaciones.com" style="color: #2563eb; text-decoration: none;">radardelicitaciones.com</a></span>
</p>

<hr style="border: none; border-top: 1px solid #e4e4e7; margin: 28px 0 18px;">

<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #3f3f46;">
<strong>P.D.</strong> Si responde con el rubro o actividad principal de {{ $companyName }}, le envío gratis un reporte de las licitaciones más relevantes en su sector que cerraron este mes — sin que tenga que abrir cuenta.
</p>

<p class="email-muted" style="margin-top: 24px; font-size: 12px; line-height: 1.5;">
Recibió este mensaje porque su empresa figura en datos públicos de compras del DGCP. Si prefiere no recibir más correos como este, responda «baja» y lo retiramos.
</p>
</x-mail::message>
