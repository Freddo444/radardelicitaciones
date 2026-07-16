<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: {{ $t['font'] }}, sans-serif; background: {{ $t['page_bg'] }}; }
    .page { position: relative; width: 210mm; height: 297mm; }
    .abs { position: absolute; }
</style>
</head>
<body>
<div class="page">
    {{-- Accent bar down the left --}}
    <div class="abs" style="left:0; top:0; width:14mm; height:297mm; background:{{ $t['accent'] }};"></div>

    {{-- Big document number --}}
    <div class="abs" style="left:34mm; top:118mm; font-size:64px; font-weight:bold; color:{{ $t['accent'] }};">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</div>

    {{-- Kicker + title --}}
    <div class="abs" style="left:34mm; top:112mm; font-size:11px; letter-spacing:2px; text-transform:uppercase; color:{{ $t['muted'] }};">Documento</div>
    <div class="abs" style="left:34mm; top:150mm; width:150mm; font-size:24px; font-weight:bold; line-height:1.25; color:{{ $t['ink'] }};">{{ $label }}</div>

    {{-- Process footer --}}
    <div class="abs" style="left:34mm; bottom:22mm; width:150mm; border-top:1px solid {{ $t['key'] === 'oscuro' ? '#334155' : '#e5e7eb' }}; padding-top:5mm; font-size:9px; color:{{ $t['muted'] }};">
        {{ $company->razon_social }} · {{ $sobre }} · {{ $proceso['codigo'] }}
    </div>
</div>
</body>
</html>
