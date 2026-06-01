<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de Envío</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .etiqueta-container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto 30px auto;
            background: #fff;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 20px 25px;
            box-sizing: border-box;
            page-break-inside: avoid;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .remitente {
            font-size: 16px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .tienda {
            font-size: 24px;
            font-weight: bold;
        }
        .para-label {
            font-size: 16px;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .cliente-nombre {
            font-size: 32px;
            font-weight: 900;
            margin: 0;
            text-transform: uppercase;
        }
        .cliente-tel {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0 20px 0;
        }
        .destino-label {
            font-size: 16px;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .agencia-texto {
            font-size: 18px;
            margin: 0 0 8px 0;
            line-height: 1.5;
        }
        .dni-texto {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 20px 0;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }
        .agencia-badge {
            background-color: #eee;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 22px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .fecha {
            font-size: 18px;
            font-weight: bold;
        }

        @media print {
            body {
                background-color: transparent;
                padding: 0;
            }
            .etiqueta-container {
                box-shadow: none;
                margin-bottom: 20px;
                border: 2px solid #000;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn {
            display: block;
            width: 250px;
            margin: 0 auto 30px auto;
            padding: 12px;
            background: #2563eb;
            color: white;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .print-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir Etiquetas</button>

    <div class="no-print" style="text-align: center; margin-bottom: 25px; background: white; padding: 15px; border-radius: 8px; border: 2px solid #ccc; max-width: 650px; margin-left: auto; margin-right: auto;">
        <label for="select-remitente" style="font-weight: bold; font-size: 16px; color: #333;">Cambiar Remitente:</label>
        <select class="select-remitente" id="select-remitente" onchange="cambiarRemitente(this.value)" style="padding: 8px 12px; font-size: 15px; border-radius: 5px; margin-left: 10px; border: 1px solid #aaa; cursor: pointer;">
            <option value="1">📦 FLOR DE MARIA QUIÑONES ENRIQUEZ</option>
            <option value="2">🏢 UNIK TECHNOLOGY S.A.C.</option>
        </select>
    </div>

    @if($envios->isEmpty())
        <div style="text-align: center; font-size: 20px; margin-top: 50px;">No hay envíos seleccionados.</div>
    @endif

    @foreach($envios as $envio)
    @php
        // Formatear destino
        $ubicacion = [];
        if(isset($envio->Destino->Provincia->Departamento->nombre)) $ubicacion[] = $envio->Destino->Provincia->Departamento->nombre;
        if(isset($envio->Destino->Provincia->nombre)) $ubicacion[] = $envio->Destino->Provincia->nombre;
        $ubicacion[] = $envio->Destino->nombre ?? '';
        
        $destinoCompleto = implode(' / ', array_filter($ubicacion));
        
        $subagenciaInfo = '';
        if($envio->SubAgencia) {
            $subagenciaInfo = ' | ' . $envio->SubAgencia->nombre_oficina;
            if ($envio->SubAgencia->direccion) {
                $subagenciaInfo .= ' - ' . $envio->SubAgencia->direccion;
            }
        }
        
        if($envio->Detalle && $envio->Detalle->dir) {
            $subagenciaInfo .= ' | ' . $envio->Detalle->dir;
            if($envio->Detalle->ref) {
                $subagenciaInfo .= ', Ref. ' . $envio->Detalle->ref;
            }
        }

        // Formatear fecha a español
        $fechaFormateada = \Carbon\Carbon::parse($envio->fecha_envio)->locale('es')->translatedFormat('l d/m');
        $fechaFormateada = ucfirst($fechaFormateada);
    @endphp

    <div class="etiqueta-container">
        <div class="header">
            <span class="remitente">REMITENTE</span>
            <span class="tienda rem-nombre" style="font-size: 18px;">FLOR DE MARIA QUIÑONES ENRIQUEZ</span>
        </div>

        <div class="para-label">PARA:</div>
        <p class="cliente-nombre">{{ $envio->Cliente->nombre ?? '' }} {{ $envio->Cliente->apellidoPaterno ?? '' }}</p>
        <p class="cliente-tel">{{ $envio->Cliente->telefono ?? '' }}</p>

        <div class="destino-label">DESTINO:</div>
        <p class="agencia-texto">
            <strong>AGENCIA:</strong> {{ $destinoCompleto }}{{ $subagenciaInfo }}
        </p>
        <p class="dni-texto">
            <strong>DNI:</strong> {{ $envio->Cliente->numeroDocumento ?? '' }}
        </p>

        <div class="footer">
            <span class="agencia-badge">{{ $envio->Agencia->nombre ?? 'AGENCIA' }}</span>
            <span class="fecha">{{ $fechaFormateada }}</span>
        </div>
    </div>
    @endforeach

    <script>
        const REMITENTES = {
            1: { nombre: 'FLOR DE MARIA QUIÑONES ENRIQUEZ' },
            2: { nombre: 'UNIK TECHNOLOGY S.A.C.' }
        };

        function cambiarRemitente(id) {
            const r = REMITENTES[id];
            if (!r) return;
            document.querySelectorAll('.rem-nombre').forEach(el => el.textContent = r.nombre);
        }

        document.addEventListener("DOMContentLoaded", function() {
            cambiarRemitente(1);
        });
    </script>
</body>
</html>
