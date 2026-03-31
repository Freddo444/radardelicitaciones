@extends('marketing.layout')

@section('title', 'Política de privacidad — Radar de Licitaciones')
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-gray-900')
@section('navLink', 'text-gray-600 hover:text-gray-900')

@section('content')

<section class="pt-32 pb-24 sm:pt-40 sm:pb-32">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Política de privacidad</h1>
        <p class="mt-2 text-sm text-gray-500">Última actualización: 31 de marzo de 2026</p>

        <div class="prose prose-gray mt-10 max-w-none text-sm leading-7">

            <h2 class="font-display text-lg font-semibold text-gray-900">1. Información que recopilamos</h2>
            <p>Recopilamos la siguiente información cuando usted utiliza Radar de Licitaciones:</p>
            <ul>
                <li><strong>Datos de cuenta:</strong> nombre, correo electrónico y contraseña (almacenada de forma encriptada)</li>
                <li><strong>Datos empresariales:</strong> razón social, RNC, RPE, dirección, teléfono y demás información que ingrese sobre su empresa</li>
                <li><strong>Documentos:</strong> archivos que usted sube al sistema (estados financieros, certificaciones, contratos, etc.)</li>
                <li><strong>Datos de uso:</strong> páginas visitadas, funciones utilizadas, fechas y horas de acceso</li>
                <li><strong>Datos de pago:</strong> procesados directamente por PayPal; no almacenamos datos de tarjeta de crédito</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-gray-900">2. Cómo usamos su información</h2>
            <p>Utilizamos su información para:</p>
            <ul>
                <li>Proporcionar y mantener el Servicio</li>
                <li>Enviar notificaciones de licitaciones relevantes a sus rubros</li>
                <li>Procesar pagos y administrar su suscripción</li>
                <li>Generar documentos pre-llenados con los datos de su empresa</li>
                <li>Mejorar el Servicio y la experiencia de usuario</li>
                <li>Comunicarnos con usted sobre su cuenta o el Servicio</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-gray-900">3. Almacenamiento y seguridad</h2>
            <p>Sus datos se almacenan en servidores seguros. Implementamos medidas de seguridad estándar de la industria, incluyendo:</p>
            <ul>
                <li>Encriptación de contraseñas con bcrypt</li>
                <li>Conexiones HTTPS/TLS para toda comunicación</li>
                <li>Acceso restringido a bases de datos</li>
                <li>Respaldos periódicos</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-gray-900">4. Compartición de datos</h2>
            <p><strong>No vendemos ni compartimos sus datos con terceros</strong>, excepto:</p>
            <ul>
                <li><strong>Proveedores de pago:</strong> PayPal procesa sus transacciones bajo sus propias políticas de privacidad</li>
                <li><strong>Servicios de IA:</strong> Los documentos de pliegos (documentos públicos de la DGCP) pueden ser procesados por servicios de inteligencia artificial para generar análisis. No se envían sus datos empresariales privados a estos servicios</li>
                <li><strong>Obligación legal:</strong> si la ley o una orden judicial lo requiere</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-gray-900">5. Aislamiento entre empresas</h2>
            <p>Cada empresa registrada en el sistema opera en un espacio aislado. Los usuarios de una empresa no pueden ver los datos, documentos u ofertas de otra empresa, aún si pertenecen a la misma suscripción.</p>

            <h2 class="font-display text-lg font-semibold text-gray-900">6. Retención de datos</h2>
            <p>Conservamos sus datos mientras su cuenta esté activa. Al cancelar su suscripción, sus datos permanecen accesibles hasta el fin del período pagado. Después de 90 días de inactividad post-cancelación, nos reservamos el derecho de eliminar sus datos. Puede solicitar la eliminación inmediata de sus datos contactándonos.</p>

            <h2 class="font-display text-lg font-semibold text-gray-900">7. Sus derechos</h2>
            <p>Usted tiene derecho a:</p>
            <ul>
                <li>Acceder a los datos personales que tenemos sobre usted</li>
                <li>Solicitar la corrección de datos inexactos</li>
                <li>Solicitar la eliminación de sus datos</li>
                <li>Exportar sus datos en un formato estándar</li>
                <li>Retirar su consentimiento para comunicaciones opcionales</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-gray-900">8. Cookies</h2>
            <p>Utilizamos cookies esenciales para el funcionamiento del Servicio (autenticación, sesión, preferencias). No utilizamos cookies de seguimiento ni de publicidad.</p>

            <h2 class="font-display text-lg font-semibold text-gray-900">9. Cambios a esta política</h2>
            <p>Podemos actualizar esta política periódicamente. Notificaremos cambios significativos por correo electrónico. La fecha de última actualización se muestra al inicio de este documento.</p>

            <h2 class="font-display text-lg font-semibold text-gray-900">10. Contacto</h2>
            <p>Para consultas sobre privacidad o ejercer sus derechos, contáctenos en <a href="mailto:info@radardelicitaciones.com" class="text-emerald-600 hover:text-emerald-500">info@radardelicitaciones.com</a>.</p>
        </div>
    </div>
</section>

@endsection
