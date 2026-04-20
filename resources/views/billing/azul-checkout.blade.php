<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirigiendo a Azul…</title>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
<p class="mx-auto max-w-md px-4 py-10 text-center text-sm text-gray-600">
    Redirigiendo a la pasarela de pago Azul… Si no ocurre nada, <button type="submit" form="azul-pay" class="font-medium text-blue-600 underline">haz clic aquí</button>.
</p>
<form id="azul-pay" method="post" action="{{ $action }}" class="hidden">
    @foreach($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <noscript><button type="submit">Continuar a Azul</button></noscript>
</form>
<script>
    document.getElementById('azul-pay').submit();
</script>
</body>
</html>
