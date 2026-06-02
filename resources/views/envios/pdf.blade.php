@php
/**
* Abrevia todas las palabras de un nombre excepto la última.
* Ejemplo: "DANIEL ALCIDES CARRION" → "D.A.CARRION"
*/
function abreviarNombre(?string $nombre): string {
if (empty($nombre)) return '';
$palabras = explode(' ', strtoupper(trim($nombre)));
if (count($palabras) === 1) return $palabras[0];
$ultima = array_pop($palabras);
$iniciales = implode('.', array_map(fn($p) => mb_substr($p, 0, 1), $palabras));
return $iniciales . '.' . $ultima;
}
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Rótulo de Envío</title>
    <style>
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #ccc;
        }

        /* ── Barra de Controles ── */
        .controls-bar {
            background-color: #343a40;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            max-width: 20cm;
            margin: 0 auto 25px auto;
        }

        .controls-left h4 {
            margin: 0 0 4px 0;
            color: white;
        }

        .controls-left span {
            font-size: 13px;
            color: #ccc;
        }

        .select-remitente {
            background-color: #495057;
            color: white;
            border: 1px solid #6c757d;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            min-width: 220px;
        }

        .select-remitente:focus {
            outline: none;
            border-color: #198754;
        }

        .btn-layout {
            padding: 8px 16px;
            background-color: #495057;
            color: white;
            border: 1px solid #6c757d;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 8px;
            transition: all 0.2s ease;
        }

        .btn-layout.active {
            background-color: #198754;
            border-color: #198754;
            box-shadow: 0 0 10px rgba(25, 135, 84, 0.5);
        }

        .btn-layout:hover {
            background-color: #5a6268;
        }

        .btn-print {
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.3);
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            background-color: #0b5ed7;
        }

        /* ── Contenedor General ── */
        #rotulos-container {
            width: 100%;
            margin: 0 auto;
            background: transparent;
        }

        .rotulo-wrapper {
            width: 100%;
        }

        /* ── Páginas de Impresión ── */
        .print-page {
            width: 100%;
            display: grid;
            gap: 20px;
            margin: 0 auto 30px auto;
            background: transparent;
        }

        .print-page.layout-1 {
            grid-template-columns: 1fr;
            max-width: 20cm;
        }

        .print-page.layout-1 .rotulo, .print-page.layout-1 .items-picking-section {
            max-width: 20cm;
        }

        .print-page.layout-2 {
            grid-template-columns: 1fr;
            max-width: 20cm;
            gap: 10px;
        }

        .print-page.layout-2 .rotulo, .print-page.layout-2 .items-picking-section {
            zoom: 0.75;
            max-width: 20cm;
            margin-bottom: 0;
        }

        .print-page.layout-4 {
            grid-template-columns: 1fr 1fr;
            max-width: 100%;
            gap: 10px;
        }

        .print-page.layout-4 .rotulo, .print-page.layout-4 .items-picking-section {
            zoom: 0.48;
            margin-bottom: 0;
            border-width: 4px;
        }

        .print-page.layout-6 {
            grid-template-columns: 1fr 1fr;
            max-width: 28cm;
            gap: 5px;
        }

        .print-page.layout-6 .rotulo, .print-page.layout-6 .items-picking-section {
            zoom: 0.50;
            margin-bottom: 0;
            border-width: 3px;
        }

        @media print {
            @page {
                margin: 0.5cm;
            }

            body {
                background: transparent;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .print-page {
                page-break-after: always;
                break-after: page;
                margin-bottom: 0 !important;
                max-width: 100% !important;
                gap: 15px !important;
            }

            .print-page:last-child {
                page-break-after: avoid;
                break-after: avoid;
            }
        }

        /* ── Rótulo ── */
        .rotulo {
            width: 100%;
            max-width: 20cm;
            background: white;
            border: 6px solid #000;
            border-radius: 20px;
            margin: 0 auto 20px auto;
            box-sizing: border-box;
            padding: 15px 20px;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* ── Header / Remitente ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .remitente {
            font-size: 16px;
            line-height: 1.4;
        }

        .remitente-title {
            color: #e53935;
            font-weight: bold;
            font-size: 26px;
            margin-bottom: 5px;
        }

        .remitente-line {
            text-decoration: underline;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .logo-container {
            text-align: right;
            width: 125px;
        }

        .logo-container img {
            width: 100%;
            height: auto;
        }

        /* ── Frágil Banner ── */
        .fragil-banner {
            background-color: #ffeb3b;
            color: #e53935;
            text-align: center;
            font-weight: 900;
            font-size: 80px;
            padding: 10px 20px;
            margin: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* ── Bottom Section ── */
        .bottom-section {
            display: flex;
            gap: 20px;
        }

        /* ── Indicaciones ── */
        .indicaciones {
            flex: 0 0 35%;
            display: flex;
            flex-direction: column;
        }

        .ind-title {
            background-color: #e53935;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 20px;
            padding: 5px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            text-transform: uppercase;
        }

        .ind-body {
            border: 2px solid #e53935;
            border-top: none;
            padding: 10px;
            flex-grow: 1;
            font-size: 16px;
        }

        .ind-label {
            margin-bottom: 2px;
        }

        .ind-line {
            border-bottom: 2px solid #000;
            min-height: 22px;
            margin-bottom: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .pagar-box {
            display: flex;
            align-items: center;
            font-weight: bold;
            font-size: 22px;
            margin-top: 10px;
        }

        .pagar-box span.rojo {
            color: #e53935;
            margin-right: 5px;
        }

        .pagar-box span.verde {
            color: #43a047;
            margin-right: 5px;
        }

        .pagar-box span.azul {
            color: #1e88e5;
            margin-right: 5px;
            margin-left: 10px;
        }

        .check-box {
            border: 2px solid #000;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
            vertical-align: middle;
            margin-left: 5px;
            box-sizing: border-box;
        }

        .observaciones {
            border: 2px solid #1e88e5;
            border-radius: 5px;
            padding: 5px 8px;
            margin-top: 10px;
            height: 80px;
            position: relative;
        }

        .obs-title {
            color: #e53935;
            font-weight: bold;
            font-size: 13px;
            position: absolute;
            top: 5px;
            left: 5px;
        }

        .obs-content {
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.2;
            text-transform: uppercase;
            font-weight: bold;
        }

        .obs-line {
            border-bottom: 1px solid #e53935;
            position: absolute;
            width: 95%;
            left: 2.5%;
        }

        .obs-line-1 {
            top: 40px;
        }

        .obs-line-2 {
            top: 65px;
        }

        /* ── Destinatario ── */
        .destinatario {
            flex: 0 0 62%;
            display: flex;
            flex-direction: column;
            font-size: 18px;
            line-height: 2;
        }

        .dest-title {
            color: #e53935;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .d-line-group {
            display: flex;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .d-label {
            margin-right: 5px;
            white-space: nowrap;
        }

            .d-value {
            flex-grow: 1;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
            min-height: 22px;
        }

        /* ── SECCIÓN ITEMS (TIPO FALABELLA) ── */
        .items-picking-section {
            max-width: 20cm;
            margin: 0 auto;
            margin-top: 5px;
        }

        .cut-line-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin-bottom: 8px;
            position: relative;
        }

        .cut-line {
            width: 100%;
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #333;
            white-space: nowrap;
        }

        .cut-line::before, .cut-line::after {
            content: "";
            flex-grow: 1;
            border-bottom: 1.5px dashed #666;
        }

        .cut-line::before { margin-right: 15px; }
        .cut-line::after { margin-left: 15px; }

        .cut-line-text {
            text-align: center;
            line-height: 1.2;
        }

        .items-box {
            border: 1.5px solid #000;
            border-radius: 8px;
            padding: 0;
            background: #fff;
            overflow: hidden;
        }

        .items-title {
            font-size: 22px;
            font-weight: 900;
            padding: 8px 15px;
            border-bottom: 1.5px solid #000;
            text-transform: uppercase;
        }

        .items-list {
            padding: 10px 15px;
            font-size: 13px;
            color: #000;
            font-family: Arial, sans-serif;
        }

        .item-line {
            margin-bottom: 6px;
        }
    </style>
</head>

<body>

    {{-- ── BARRA DE CONTROLES (no imprimible) ── --}}
    <div class="no-print controls-bar">
        <div class="controls-left">
            <h4>Configuración de Impresión</h4>
            <span>Selecciona remitente y cantidad de rótulos por hoja:</span>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">

            {{-- Selector de Remitente --}}
            <select class="select-remitente" id="select-remitente" onchange="cambiarRemitente(this.value)">
                <option value="1">📦 FLOR DE MARIA QUIÑONES ENRIQUEZ</option>
                <option value="2">🏢 UNIK TECHNOLOGY S.A.C.</option>
            </select>

            {{-- Botones de Layout --}}
            <div>
                <button onclick="changeLayout(1)" id="btn-l1" class="btn-layout active">1 por Hoja</button>
                <button onclick="changeLayout(2)" id="btn-l2" class="btn-layout">2 por Hoja</button>
                <button onclick="changeLayout(4)" id="btn-l4" class="btn-layout">4 por Hoja</button>
                <button onclick="changeLayout(6)" id="btn-l6" class="btn-layout">6 por Hoja</button>
            </div>

            <button onclick="window.print()" class="btn-print">🖨️ Imprimir</button>
        </div>
    </div>

    {{-- ── RÓTULOS ── --}}
    @if($envios->isEmpty())
    <div style="text-align: center; padding: 50px; background: white; font-size: 20px; font-weight: bold;">
        No hay envíos registrados para esta fecha.
    </div>
    @else
    <div id="rotulos-container">
        @foreach($envios as $envio)
        <div class="rotulo-wrapper">
            <div class="rotulo">

                {{-- HEADER --}}
                <div class="header">
                    <div class="remitente">
                        <div class="remitente-title">Remitente:</div>
                        <div class="remitente-line rem-nombre">FLOR DE MARIA QUIÑONES ENRIQUEZ</div>
                        <div class="remitente-line rem-dir">Dir.: AV. RAMON CARCAMO 785 - E203</div>
                        <div class="remitente-line rem-ciudad">Ciu.: LIMA - LIMA</div>
                        <div style="display: flex; gap: 30px;">
                            <div class="remitente-line rem-cel">Cel.: 949064561</div>
                            <div class="remitente-line rem-doc">DNI/RUC: 10424505787</div>
                        </div>
                    </div>
                    <div class="logo-container">
                        <img src="{{ asset('storage/logos/logoheader.png') }}" alt="Unik Store Logo">
                    </div>
                </div>

                {{-- FRÁGIL BANNER --}}
                <div class="fragil-banner">
                    <span>FRÁGIL</span>
                    <span style="font-size: 80px;">🍷</span>
                </div>

                {{-- BOTTOM SECTION --}}
                <div class="bottom-section">

                    {{-- INDICACIONES --}}
                    <div class="indicaciones">
                        <div class="ind-title">INDICACIONES</div>
                        <div class="ind-body">
                            <div class="ind-label">Agencia/Courier:</div>
                            <div class="ind-line">{{ $envio->Agencia->nombre ?? '' }}</div>

                            <div class="ind-label">Remito/Guía:</div>
                            <div class="ind-line">{{ $envio->numero_guia ?? '' }}</div>

                            <div class="pagar-box">
                                <span class="rojo">Pagar:</span>
                                <span class="verde">Si</span>
                                <div class="check-box">{{ $envio->pago_destino ? 'X' : '' }}</div>
                                <span class="azul">No</span>
                                <div class="check-box">{{ !$envio->pago_destino ? 'X' : '' }}</div>
                            </div>

                            <div class="observaciones">
                                <div class="obs-title">Observaciones:</div>
                                <div class="obs-content">
                                    {{ mb_substr($envio->dato_adicional ?? '', 0, 80) }}
                                </div>
                                <div class="obs-line obs-line-1"></div>
                                <div class="obs-line obs-line-2"></div>
                            </div>
                        </div>
                    </div>

                    {{-- DESTINATARIO --}}
                    <div class="destinatario">
                        <div class="dest-title">Destinatario:</div>

                        <div class="d-line-group">
                            <div class="d-value" style="font-size: 22px;">{{ $envio->Cliente->nombre ?? '' }} {{ $envio->Cliente->apellidoPaterno ?? '' }} {{ $envio->Cliente->apellidoMaterno ?? '' }}</div>
                        </div>

                        <div class="d-line-group">
                            <div class="d-label">Dir.:</div>
                            <div class="d-value" style="font-size: 12px;">{{ $envio->Detalle->dir ?? '' }}</div>
                        </div>

                        <div class="d-line-group">
                            <div class="d-label">(Ref.:</div>
                            <div class="d-value" style="font-size: 12px;">{{ $envio->Detalle->ref ?? '' }}</div>
                            <div class="d-label">)</div>
                        </div>

                        <div class="d-line-group">
                            <div class="d-label">Dist.:</div>
                            <div class="d-value" style="flex: 1;">{{ abreviarNombre($envio->Destino->nombre ?? '') }}</div>
                            <div class="d-label" style="margin-left: 10px;">Prov.:</div>
                            <div class="d-value" style="flex: 1;">{{ abreviarNombre($envio->Destino->Provincia->nombre ?? '') }}</div>
                        </div>

                        <div class="d-line-group">
                            <div class="d-label">Dpto.:</div>
                            <div class="d-value" style="flex: 1;">{{ abreviarNombre($envio->Destino->Provincia->nombre ?? '') }}</div>
                            <div class="d-label" style="margin-left: 10px;">Ciu.:</div>
                            <div class="d-value" style="flex: 1;">{{ abreviarNombre($envio->Destino->nombre ?? '') }}</div>
                        </div>

                        <div class="d-line-group">
                            <div class="d-label">Cel.:</div>
                            <div class="d-value" style="flex: 1;">{{ $envio->Cliente->telefono ?? '' }}</div>
                            <div class="d-label" style="margin-left: 10px;">DNI/RUC:</div>
                            <div class="d-value" style="flex: 1;">{{ $envio->Cliente->numeroDocumento ?? '' }}</div>
                        </div>
                    </div>

                </div>{{-- end bottom-section --}}
            </div>{{-- end rotulo --}}

            {{-- ── SECCIÓN DE ITEMS TIPO FALABELLA ── --}}
            <div class="items-picking-section">
                <div class="cut-line-container">
                    <div class="cut-line">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 10px; transform: rotate(-90deg);">
                            <path d="M3.5 3.5c-.614-.884-.074-1.962.858-2.5L8 7.226 11.642 1c.932.538 1.472 1.616.858 2.5L8.81 8.61l1.556 2.661a2.5 2.5 0 1 1-.794.637L8 9.73l-1.572 2.177a2.5 2.5 0 1 1-.794-.637L7.19 8.61 3.5 3.5zm2.5 0c-.614-.884-.074-1.962.858-2.5L8 7.226 11.642 1c.932.538 1.472 1.616.858 2.5L8.81 8.61l1.556 2.661a2.5 2.5 0 1 1-.794.637L8 9.73l-1.572 2.177a2.5 2.5 0 1 1-.794-.637L7.19 8.61 6 3.5z"/>
                        </svg>
                        <div class="cut-line-text">
                            <div>Etiqueta para pegar en el paquete</div>
                            <div>Información para el armado del paquete</div>
                        </div>
                    </div>
                </div>
                <div class="items-box">
                    <div class="items-title">ÍTEMS</div>
                    <div class="items-list">
                        @php
                            $agrupadosPdf = [];
                            foreach($envio->Productos as $item) {
                                $idProd = $item->idProducto;
                                if (!isset($agrupadosPdf[$idProd])) {
                                    $agrupadosPdf[$idProd] = [
                                        'producto' => $item->Producto,
                                        'cantidad' => 0
                                    ];
                                }
                                $agrupadosPdf[$idProd]['cantidad'] += $item->cantidad;
                            }
                        @endphp
                        @forelse($agrupadosPdf as $data)
                            <div class="item-line">
                                {{ $data['producto']->nombreProducto ?? 'Producto Desconocido' }} SKU: <strong>{{ $data['producto']->modelo ?? 'N/A' }}</strong> / Cant: <strong>{{ $data['cantidad'] }}</strong>
                            </div>
                        @empty
                            <div class="item-line text-muted">No hay productos registrados en este envío.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>{{-- end rotulo-wrapper --}}
        @endforeach
    </div>{{-- end rotulos-container --}}
    @endif

    {{-- ── LÓGICA JS (separada) ── --}}
    @include('envios.logic.pdf')

</body>

</html>