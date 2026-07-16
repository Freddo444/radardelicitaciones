<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: {{ $t['font'] }}, sans-serif; color: {{ $t['ink'] }}; background: {{ $t['page_bg'] }}; }
    .page { position: relative; width: 210mm; height: 297mm; overflow: hidden; }
    .abs { position: absolute; }
    .up { text-transform: uppercase; letter-spacing: 2px; }
</style>
</head>
<body>
<div class="page">

@if($t['key'] === 'construccion')
    {{-- ── CONSTRUCCIÓN: bold left accent sidebar, industrial uppercase ── --}}
    <div class="abs" style="left:0; top:0; width:26mm; height:297mm; background:{{ $t['accent'] }};"></div>
    <div class="abs" style="left:26mm; top:0; width:4mm; height:297mm; background:{{ $t['accent_dark'] }};"></div>

    @if($logo)
    <div class="abs" style="left:44mm; top:26mm; width:120mm;">
        <img src="{{ $logo }}" style="max-height:26mm; max-width:120mm;">
    </div>
    @endif

    <div class="abs up" style="left:44mm; top:96mm; font-size:13px; color:{{ $t['accent'] }}; font-weight:bold;">Propuesta · {{ $sobre }}</div>
    <div class="abs" style="left:44mm; top:104mm; width:150mm; font-size:34px; font-weight:bold; line-height:1.1; color:{{ $t['ink'] }};">{{ $company->razon_social }}</div>
    @if($company->rnc)
    <div class="abs" style="left:44mm; top:130mm; font-size:12px; color:{{ $t['muted'] }};">RNC {{ $company->rnc }}@if($company->rpe_numero) · RPE {{ $company->rpe_numero }}@endif</div>
    @endif

    <div class="abs" style="left:44mm; top:158mm; width:150mm; border-top:3px solid {{ $t['accent'] }}; padding-top:10mm;">
        <div class="up" style="font-size:10px; color:{{ $t['muted'] }}; margin-bottom:4mm;">Proceso de contratación</div>
        <div style="font-size:15px; font-weight:bold; line-height:1.35; margin-bottom:5mm;">{{ $proceso['nombre'] }}</div>
        <table style="font-size:12px; color:{{ $t['ink'] }};">
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Código</td><td style="font-weight:bold;">{{ $proceso['codigo'] }}</td></tr>
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Entidad</td><td>{{ $proceso['entidad'] }}</td></tr>
        </table>
    </div>

    <div class="abs" style="left:44mm; top:272mm; width:150mm; font-size:9px; color:{{ $t['muted'] }};">
        {{ $footer }}
    </div>

@elseif($t['key'] === 'minimalista')
    {{-- ── MINIMALISTA: whitespace, hairline accent, elegant ── --}}
    <div class="abs" style="left:24mm; top:24mm; width:6mm; height:6mm; background:{{ $t['accent'] }};"></div>

    @if($logo)
    <div class="abs" style="right:24mm; top:24mm; width:60mm; text-align:right;">
        <img src="{{ $logo }}" style="max-height:18mm; max-width:60mm;">
    </div>
    @endif

    <div class="abs" style="left:24mm; top:120mm; font-size:11px; letter-spacing:3px; color:{{ $t['accent'] }};">PROPUESTA — {{ mb_strtoupper($sobre) }}</div>
    <div class="abs" style="left:24mm; top:130mm; width:162mm; font-size:30px; font-weight:normal; line-height:1.15; color:{{ $t['ink'] }};">{{ $company->razon_social }}</div>
    @if($company->rnc)
    <div class="abs" style="left:24mm; top:{{ 130 + 18 }}mm; font-size:11px; color:{{ $t['muted'] }};">RNC {{ $company->rnc }}@if($company->rpe_numero) &nbsp;·&nbsp; RPE {{ $company->rpe_numero }}@endif</div>
    @endif

    <div class="abs" style="left:24mm; top:190mm; width:162mm; border-top:1px solid #e5e7eb; padding-top:8mm;">
        <div style="font-size:15px; color:{{ $t['ink'] }}; line-height:1.4; margin-bottom:6mm;">{{ $proceso['nombre'] }}</div>
        <table style="font-size:11px;">
            <tr>
                <td style="padding-right:16mm;"><span style="color:{{ $t['muted'] }};">Código</span><br><span style="color:{{ $t['ink'] }};">{{ $proceso['codigo'] }}</span></td>
                <td><span style="color:{{ $t['muted'] }};">Entidad</span><br><span style="color:{{ $t['ink'] }};">{{ $proceso['entidad'] }}</span></td>
            </tr>
        </table>
    </div>

    <div class="abs" style="left:24mm; bottom:20mm; width:162mm; font-size:9px; color:{{ $t['muted'] }};">{{ $footer }}</div>

@elseif($t['key'] === 'oscuro')
    {{-- ── ELEGANTE OSCURO: dark premium ── --}}
    <div class="abs" style="left:0; top:0; width:210mm; height:6mm; background:{{ $t['accent'] }};"></div>

    @if($logo)
    <div class="abs" style="left:26mm; top:30mm; width:120mm;">
        <img src="{{ $logo }}" style="max-height:24mm; max-width:120mm;">
    </div>
    @endif

    <div class="abs up" style="left:26mm; top:100mm; font-size:12px; letter-spacing:3px; color:{{ $t['accent'] }};">Propuesta · {{ $sobre }}</div>
    <div class="abs" style="left:26mm; top:110mm; width:158mm; font-size:32px; font-weight:bold; line-height:1.15; color:{{ $t['ink'] }};">{{ $company->razon_social }}</div>
    @if($company->rnc)
    <div class="abs" style="left:26mm; top:136mm; font-size:12px; color:{{ $t['muted'] }};">RNC {{ $company->rnc }}@if($company->rpe_numero) · RPE {{ $company->rpe_numero }}@endif</div>
    @endif

    <div class="abs" style="left:26mm; top:164mm; width:158mm; border-top:1px solid #334155; padding-top:9mm;">
        <div class="up" style="font-size:9px; color:{{ $t['accent'] }}; margin-bottom:4mm; letter-spacing:2px;">Proceso de contratación</div>
        <div style="font-size:15px; font-weight:bold; color:{{ $t['ink'] }}; line-height:1.35; margin-bottom:5mm;">{{ $proceso['nombre'] }}</div>
        <table style="font-size:12px; color:{{ $t['ink'] }};">
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Código</td><td style="font-weight:bold;">{{ $proceso['codigo'] }}</td></tr>
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Entidad</td><td>{{ $proceso['entidad'] }}</td></tr>
        </table>
    </div>

    <div class="abs" style="left:26mm; bottom:20mm; width:158mm; font-size:9px; color:{{ $t['muted'] }};">{{ $footer }}</div>

@else
    {{-- ── CORPORATIVO: accent header band, clean ── --}}
    <div class="abs" style="left:0; top:0; width:210mm; height:42mm; background:{{ $t['accent'] }};"></div>
    <div class="abs" style="left:0; top:42mm; width:210mm; height:2mm; background:{{ $t['accent_dark'] }};"></div>

    @if($logo)
    <div class="abs" style="left:24mm; top:58mm; width:120mm;">
        <img src="{{ $logo }}" style="max-height:28mm; max-width:120mm;">
    </div>
    @endif

    <div class="abs" style="left:24mm; top:100mm; font-size:12px; color:{{ $t['accent'] }}; font-weight:bold; letter-spacing:1px;">PROPUESTA — {{ mb_strtoupper($sobre) }}</div>
    <div class="abs" style="left:24mm; top:108mm; width:162mm; font-size:32px; font-weight:bold; line-height:1.15; color:{{ $t['ink'] }};">{{ $company->razon_social }}</div>
    @if($company->nombre_comercial && $company->nombre_comercial !== $company->razon_social)
    <div class="abs" style="left:24mm; top:{{ 108 + 20 }}mm; font-size:13px; color:{{ $t['muted'] }};">{{ $company->nombre_comercial }}</div>
    @endif
    @if($company->rnc)
    <div class="abs" style="left:24mm; top:{{ 108 + 27 }}mm; font-size:12px; color:{{ $t['muted'] }};">RNC {{ $company->rnc }}@if($company->rpe_numero) · RPE {{ $company->rpe_numero }}@endif</div>
    @endif

    <div class="abs" style="left:24mm; top:170mm; width:162mm; background:#f8fafc; border:1px solid #e5e7eb; border-left:4px solid {{ $t['accent'] }}; padding:8mm;">
        <div style="font-size:10px; color:{{ $t['muted'] }}; text-transform:uppercase; letter-spacing:1px; margin-bottom:4mm;">Proceso de contratación</div>
        <div style="font-size:15px; font-weight:bold; line-height:1.35; margin-bottom:5mm; color:{{ $t['ink'] }};">{{ $proceso['nombre'] }}</div>
        <table style="font-size:12px; color:{{ $t['ink'] }};">
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }}; width:24mm;">Código</td><td style="font-weight:bold;">{{ $proceso['codigo'] }}</td></tr>
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Entidad</td><td>{{ $proceso['entidad'] }}</td></tr>
            <tr><td style="padding:1.5mm 8mm 1.5mm 0; color:{{ $t['muted'] }};">Fecha</td><td>{{ $fecha }}</td></tr>
        </table>
    </div>

    <div class="abs" style="left:24mm; bottom:18mm; width:162mm; border-top:1px solid #e5e7eb; padding-top:5mm; font-size:9px; color:{{ $t['muted'] }};">{{ $footer }}</div>
@endif

</div>
</body>
</html>
