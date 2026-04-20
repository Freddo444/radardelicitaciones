@extends('marketing.layout')

@section('title', 'Políticas de pago y seguridad — Radar de Licitaciones')
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-zinc-900')
@section('navLink', 'text-zinc-600 hover:text-zinc-900')

@section('content')
@php
    $supportEmail = config('services.support.email', 'info@radardelicitaciones.com');
    $supportPhone = config('services.support.phone', '');
    $addressLine = config('services.support.address_line', '');
    $city = config('services.support.city', 'Santo Domingo');
    $country = config('services.support.country', 'República Dominicana');
@endphp

<section class="pt-28 pb-20 sm:pt-40 sm:pb-32">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="font-display text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">Políticas de pago y seguridad</h1>
        <p class="mt-2 text-sm text-zinc-500">Última actualización: 20 de abril de 2026</p>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <img src="{{ asset('images/payments/visa.png') }}" alt="Visa" class="h-8 w-auto rounded border border-zinc-200 bg-white p-1">
            <img src="{{ asset('images/payments/mastercard.png') }}" alt="Mastercard" class="h-9 w-auto rounded border border-zinc-200 bg-white p-1">
            <img src="{{ asset('images/payments/verified-by-visa.png') }}" alt="Verified by Visa" class="h-9 w-auto rounded border border-zinc-200 bg-white p-1">
            <img src="{{ asset('images/payments/mastercard-id-check.png') }}" alt="Mastercard ID Check" class="h-9 w-auto rounded border border-zinc-200 bg-white p-1">
        </div>

        <div class="prose prose-zinc mt-8 max-w-none text-sm leading-6 prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline sm:leading-7">
            <h2 class="font-display text-lg font-semibold text-zinc-900">1. Moneda de compra</h2>
            <p>Todas las compras y cobros en esta plataforma se procesan en <strong>DOP (RD$)</strong> o su equivalente mostrado por el proveedor de pago al momento de autorizar la transacción.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">2. Políticas de devoluciones y reembolsos</h2>
            <p>Radar de Licitaciones es un servicio digital por suscripción. Los pagos realizados no son reembolsables por períodos parciales ya iniciados. Si cancela su suscripción, mantiene acceso hasta finalizar el período ya pagado y no se realizarán cobros automáticos posteriores.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">3. Política de cancelación</h2>
            <p>La suscripción puede cancelarse en cualquier momento desde el panel de facturación. La cancelación no aplica penalidad y surte efecto al final del ciclo vigente.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">4. Política de entrega del servicio</h2>
            <p>Al completarse el pago, la activación o renovación de la suscripción es digital y se refleja en su cuenta. En la mayoría de casos ocurre en minutos; en validaciones manuales puede tomar hasta 1 día laborable. No hay entrega física de productos.</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">5. Servicio al cliente</h2>
            <p>Puede contactarnos por:
                <br>Correo: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
                <br>Teléfono: <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">{{ $supportPhone }}</a>
            </p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">6. Dirección del comercio</h2>
            <p>{{ trim(($addressLine ? $addressLine.', ' : '').$city.', '.$country, ', ') }}</p>

            <h2 class="font-display text-lg font-semibold text-zinc-900">7. Seguridad para transmisión de datos de tarjetas</h2>
            <p><strong>WEBSITE:</strong> Tomamos medidas y precauciones razonables para proteger su información personal y seguimos buenas prácticas de la industria para evitar uso inapropiado, alteración o destrucción de datos. La transmisión de datos sensibles durante el pago se realiza por conexión segura (SSL/TLS). Además, aplicamos controles de seguridad alineados con los estándares PCI-DSS aplicables al modelo de servicio.</p>
            <p><strong>PAGOS:</strong> Los métodos de pago utilizados por Radar de Licitaciones son provistos por terceros especializados (incluyendo AZUL y/o otros adquirentes habilitados). Estos proveedores cumplen estándares de seguridad y cifrado para proteger la información de la transacción y solo utilizan los datos necesarios para completar el proceso de pago. Recomendamos consultar también las políticas de privacidad y seguridad de dichos proveedores.</p>
            <ul>
                <li>No almacenamos números completos de tarjeta ni códigos de seguridad (CVV) en nuestros servidores.</li>
                <li>Aplicamos autenticación reforzada cuando el emisor/proveedor la requiere (por ejemplo, 3D Secure).</li>
                <li>Mantenemos monitoreo de seguridad y controles de acceso administrativo para reducir el riesgo de fraude.</li>
            </ul>

            <h2 class="font-display text-lg font-semibold text-zinc-900">8. Modelo de comprobante de pago</h2>
            <p>Luego del pago, el cliente recibe un comprobante/confirmación de transacción. Modelo referencial:</p>
            <div class="not-prose overflow-hidden rounded-xl border border-zinc-200">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-zinc-200">
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Comercio</td><td class="px-4 py-2 text-zinc-900">Radar de Licitaciones</td></tr>
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Referencia</td><td class="px-4 py-2 text-zinc-900">PAY-{{ now()->format('Ymd') }}-000001</td></tr>
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Fecha</td><td class="px-4 py-2 text-zinc-900">{{ now()->format('d/m/Y H:i') }}</td></tr>
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Monto</td><td class="px-4 py-2 text-zinc-900">RD$ 2,655.00</td></tr>
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Método</td><td class="px-4 py-2 text-zinc-900">Tarjeta crédito/débito</td></tr>
                        <tr><td class="bg-zinc-50 px-4 py-2 font-medium text-zinc-700">Estado</td><td class="px-4 py-2 text-zinc-900">Aprobado</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
