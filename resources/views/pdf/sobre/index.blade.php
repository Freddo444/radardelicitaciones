<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 22mm 20mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: {{ $t['font'] }}, sans-serif; color: {{ $t['ink'] }}; }
    .kicker { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: {{ $t['accent'] }}; }
    h1 { font-size: 24px; font-weight: bold; margin: 3mm 0 2mm; color: {{ $t['ink'] }}; }
    .sub { font-size: 11px; color: {{ $t['muted'] }}; margin-bottom: 9mm; }
    .rule { border-bottom: 3px solid {{ $t['accent'] }}; width: 28mm; margin-bottom: 9mm; }
    table.toc { width: 100%; border-collapse: collapse; font-size: 12px; }
    table.toc td { padding: 3.2mm 0; border-bottom: 1px solid #e5e7eb; vertical-align: bottom; }
    .num { width: 10mm; color: {{ $t['accent'] }}; font-weight: bold; }
    .doc { color: {{ $t['ink'] }}; }
    .pg { width: 20mm; text-align: right; color: {{ $t['muted'] }}; font-weight: bold; }
    .dots { color: #d1d5db; }
</style>
</head>
<body>
    <div class="kicker">{{ $sobre }}</div>
    <h1>Índice de documentos</h1>
    <div class="rule"></div>
    <div class="sub">{{ $company->razon_social }} · {{ $proceso['codigo'] }}</div>

    <table class="toc">
        @foreach($entries as $entry)
        <tr>
            <td class="num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
            <td class="doc">{{ $entry['label'] }}</td>
            <td class="pg">@if($entry['page'])pág. {{ $entry['page'] }}@endif</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
