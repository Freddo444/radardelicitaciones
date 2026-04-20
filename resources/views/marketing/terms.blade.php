@extends('marketing.layout')

@section('title', 'Términos de servicio — Radar de Licitaciones')
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-zinc-900')
@section('navLink', 'text-zinc-600 hover:text-zinc-900')

@section('content')

<section class="pt-28 pb-20 sm:pt-40 sm:pb-32">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Términos de servicio</h1>
        <p class="mt-2 text-sm text-zinc-500">Última actualización: 31 de marzo de 2026</p>

        <div class="prose prose-zinc mt-10 max-w-none text-sm leading-6 prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline sm:leading-7">

            <h2 class="font-display text-lg font-semibold text-zinc-900">1. Aceptación de los términos</h2>
            <p>Al acceder o utilizar Radar de Licitaciones ("el Servicio"), operado por Radar de Licitaciones SRL ("nosotros", "nuestro"), usted acepta estar sujeto a estos Términos de Servicio. Si no está de acuerdo con alguno de estos términos, no utilice el Servicio.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">2. Descripción del servicio</h2>
            <p>Radar de Licitaciones es una plataforma SaaS de monitoreo de licitaciones públicas publicadas por la Dirección General de Contrataciones Públicas (DGCP) de la República Dominicana. El Servicio incluye:</p>
            <ul>
                <li>Monitoreo automatizado del portal de la DGCP</li>
                <li>Análisis de documentos de pliegos mediante inteligencia artificial</li>
                <li>Herramientas para la preparación de ofertas</li>
                <li>Notificaciones por correo electrónico y Telegram</li>
                <li>Almacenamiento de documentos empresariales</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-zinc-900">3. Registro y cuentas</h2>
            <p>Para utilizar el Servicio, debe crear una cuenta proporcionando información veraz y completa. Usted es responsable de mantener la confidencialidad de su contraseña y de todas las actividades que ocurran bajo su cuenta. Debe notificarnos inmediatamente de cualquier uso no autorizado.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">4. Planes y precios</h2>
            <p>El Servicio se ofrece bajo un modelo de suscripción mensual. Los precios se publican en nuestra página de precios y pueden actualizarse con aviso previo de 30 días. El plan base incluye 1 empresa y 2 usuarios; empresas y usuarios adicionales tienen costo adicional.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">5. Pagos y facturación</h2>
            <p>Los pagos se procesan a través de PayPal o transferencia bancaria. Las suscripciones se renuevan automáticamente cada mes. Usted autoriza el cobro recurrente al suscribirse. No se emiten reembolsos por períodos parciales. Para conocer detalles de moneda, seguridad de tarjetas, cancelación y entrega del servicio, consulte nuestras <a href="{{ route('payment-policies') }}">Políticas de pago y seguridad</a>.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">6. Cancelación</h2>
            <p>Puede cancelar su suscripción en cualquier momento desde su panel de facturación. Al cancelar, su acceso continuará hasta el final del período de facturación actual. No hay penalidades por cancelación.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">7. Uso aceptable</h2>
            <p>Usted se compromete a:</p>
            <ul>
                <li>No utilizar el Servicio para fines ilegales</li>
                <li>No intentar acceder a cuentas o datos de otros usuarios</li>
                <li>No realizar ingeniería inversa del Servicio</li>
                <li>No sobrecargar intencionalmente los sistemas</li>
                <li>No compartir sus credenciales de acceso con terceros no autorizados</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-zinc-900">8. Propiedad intelectual</h2>
            <p>El Servicio, incluyendo su diseño, código, marcas y contenido original, es propiedad de Radar de Licitaciones SRL. Los datos de licitaciones provienen de fuentes públicas de la DGCP. Los documentos que usted sube al Servicio permanecen de su propiedad.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">9. Limitación de responsabilidad</h2>
            <p>El Servicio se proporciona "tal cual". No garantizamos la disponibilidad ininterrumpida ni la exactitud absoluta de los datos obtenidos de la DGCP. No somos responsables por decisiones comerciales tomadas en base a la información proporcionada por el Servicio. Nuestra responsabilidad total está limitada al monto pagado por usted en los últimos 3 meses.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">10. Disponibilidad del servicio</h2>
            <p>Nos esforzamos por mantener el Servicio disponible 24/7, pero no garantizamos disponibilidad ininterrumpida. Podemos realizar mantenimiento programado con aviso previo. No somos responsables por interrupciones de terceros (DGCP, proveedores de pago, etc.).</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">11. Modificaciones</h2>
            <p>Nos reservamos el derecho de modificar estos términos. Los cambios significativos se comunicarán por correo electrónico con al menos 15 días de anticipación. El uso continuado del Servicio después de los cambios constituye su aceptación.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">12. Ley aplicable</h2>
            <p>Estos términos se rigen por las leyes de la República Dominicana. Cualquier disputa se resolverá en los tribunales competentes del Distrito Nacional, Santo Domingo.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">13. Contacto</h2>
            <p>Para consultas sobre estos términos, contáctenos en <a href="mailto:info@radardelicitaciones.com" class="text-emerald-600 hover:text-emerald-500">info@radardelicitaciones.com</a>.</p>
        </div>
    </div>
</section>

@endsection
