<?php

use App\Models\Destino;
use App\Models\SubAgencia;

$idAgencia = 5; // ID DE ESPINOZA CARGO
$count = 0;

$agencias = [
    [
        'nombre' => 'SEDE PRINCIPAL',
        'direccion' => 'Jr. Carlos Zavala # 235 – Cercado de Lima',
        'referencia' => 'JUNTO A LA PUERTA DE EMERGENCIAS DEL ESSALUD DE GRAU',
        'telefono' => '944 574 333',
        'horarios' => 'Lunes a Sábado: 8AM-10PM | Domingos: 8AM-2PM',
        'ubi' => ['LIMA', 'LIMA', 'LIMA']
    ],
    [
        'nombre' => 'SEDE SAN LUIS',
        'direccion' => 'JIRON MARISCAL AGUSTÍN GAMARRA 540 - SAN LUIS',
        'referencia' => 'A ESPALDAS DE LA CLÍNICA SAN JUAN DE DIOS',
        'telefono' => '987 575 583',
        'horarios' => 'Lunes a Sábado: 8AM-8PM',
        'ubi' => ['SAN LUIS', 'LIMA', 'LIMA']
    ],
    [
        'nombre' => 'SEDE LA VICTORIA',
        'direccion' => 'JIRÓN OBREROS 439 - LA VICTORIA',
        'referencia' => 'FRENTE A RADIOPATRULLA DE LA VICTORIA',
        'telefono' => '987 575 577',
        'horarios' => 'Lunes a Sábado: 8AM-8PM',
        'ubi' => ['LA VICTORIA', 'LIMA', 'LIMA']
    ],
    [
        'nombre' => 'SEDE MANCO CAPAC',
        'direccion' => 'AV MANCO CAPAC 1198',
        'referencia' => 'CRUCE DE AV MANCO CAPAC CON FRANCIA',
        'telefono' => '986 628 006',
        'horarios' => 'Lunes a Sábado: 8AM-7PM',
        'ubi' => ['LA VICTORIA', 'LIMA', 'LIMA']
    ],
    [
        'nombre' => 'SEDE VITARTE',
        'direccion' => 'CALLE 1 MZ D LT 4 URBANIZACIÓN BARBADILLO - ATE VITARTE',
        'referencia' => 'FRENTE AL TERMINAL BARBADILLO. A ESPALDAS DE ELECTRA',
        'telefono' => '986 628 012',
        'horarios' => 'Lunes a Sábado: 9AM-6PM | Domingos: 9AM-1PM',
        'ubi' => ['ATE', 'LIMA', 'LIMA']
    ],
    [
        'nombre' => 'SEDE AYACUCHO',
        'direccion' => 'PROLONGACIÓN MANCO CAPAC 1081 - LA VICTORIA',
        'referencia' => 'A 2 CUADRAS DEL CRUCE DE LIBERTAD Y MANCO CAPAC',
        'telefono' => '944 574 336 / 944574341 / 986 628 002',
        'horarios' => 'Lunes a Sábado: 6AM-8:30PM | Domingos: 6AM-2PM',
        'ubi' => ['LA VICTORIA', 'LIMA', 'LIMA'] // Corregido a La Victoria (Lima)
    ],
    [
        'nombre' => 'SEDE TERMINAL SUR AYACUCHO',
        'direccion' => 'PROLONGACIÓN MANCO CAPAC 1081 - LA VICTORIA',
        'referencia' => 'AL COSTADO DE LA EMPRESA METAL MARK',
        'telefono' => '944 574 336 / 944574341 / 986 628 002',
        'horarios' => 'Lunes a Sábado: 7AM-3PM',
        'ubi' => ['LA VICTORIA', 'LIMA', 'LIMA'] // Corregido a La Victoria (Lima)
    ],
    [
        'nombre' => 'SEDE HUANTA',
        'direccion' => 'JIRON MÁXIMO GÓMEZ 136',
        'referencia' => 'PARQUE DE LOS HÉROES O PARQUE HOSPITAL',
        'telefono' => '944 574 343 / 986 628 014',
        'horarios' => 'Lunes a Sábado: 8AM-6PM | Domingos: 8AM-2PM',
        'ubi' => ['HUANTA', 'HUANTA', 'AYACUCHO']
    ],
    [
        'nombre' => 'SEDE HUANCAYO',
        'direccion' => 'JIRÓN AREQUIPA 1301',
        'referencia' => 'ESQUINA CON ANGARAES',
        'telefono' => '986 628 018 / 986 628 022',
        'horarios' => 'Lunes a Sábado: 7AM-7PM | Domingos: 7AM-2PM',
        'ubi' => ['HUANCAYO', 'HUANCAYO', 'JUNIN']
    ],
    [
        'nombre' => 'SEDE HUANCAYO - SAN CARLOS',
        'direccion' => 'AV. SAN CARLOS N°169 - JUNIN',
        'referencia' => 'CERCA AL MALLPLAZA HUANCAYO',
        'telefono' => '977 600 966',
        'horarios' => 'Lunes a Sábado: 9AM-6PM',
        'ubi' => ['HUANCAYO', 'HUANCAYO', 'JUNIN']
    ],
    [
        'nombre' => 'SEDE HUANCAVELICA',
        'direccion' => 'AV ANDRÉS AVELINO CÁCERES 680',
        'referencia' => 'PASANDO EL HOSPITAL DEPARTAMENTAL. LOCAL MISTY PATA. PORTÓN AMARILLO',
        'telefono' => '986 628 020 / 986 628 015',
        'horarios' => 'Lunes a Sábado: 8AM-6PM | Domingos: 8AM-2PM',
        'ubi' => ['HUANCAVELICA', 'HUANCAVELICA', 'HUANCAVELICA']
    ],
    [
        'nombre' => 'SEDE PAMPAS',
        'direccion' => 'AV AREQUIPA 254 PAMPAS',
        'referencia' => 'A MEDIA CUADRA DE LA PARROQUIA DE PAMPAS',
        'telefono' => '944 574 347',
        'horarios' => 'Lunes a Sábado: 7AM-6PM | Domingos: 7AM-2PM',
        'ubi' => ['PAMPAS', 'TAYACAJA', 'HUANCAVELICA']
    ],
    [
        'nombre' => 'SEDE ANDAHUAYLAS',
        'direccion' => 'JIRÓN PICAFLOR 153',
        'referencia' => 'ANTES DE LLEGAR AL HOTEL LOS PINOS',
        'telefono' => '908 935 592',
        'horarios' => 'Lunes a Sábado: 8AM-5PM',
        'ubi' => ['ANDAHUAYLAS', 'ANDAHUAYLAS', 'APURIMAC']
    ],
    [
        'nombre' => 'SEDE ABANCAY',
        'direccion' => 'AV PANAMERICANA S/N BELLAVISTA BAJA ABANCAY',
        'referencia' => 'A MEDIA CUADRA DE LA COMISARIA DE ABANCAY',
        'telefono' => '908 935 582',
        'horarios' => 'Miércoles a Sábado: 9AM-5PM',
        'ubi' => ['ABANCAY', 'ABANCAY', 'APURIMAC']
    ],
    [
        'nombre' => 'SEDE EL TAMBO',
        'direccion' => 'JIRÓN LOS MANZANOS 156',
        'referencia' => 'AL LADO DEL MINIMARKET JYJ PASANDO EL FERROCARRIL',
        'telefono' => '986 627 993',
        'horarios' => 'Lunes a Sábado: 9AM-6PM',
        'ubi' => ['EL TAMBO', 'HUANCAYO', 'JUNIN']
    ],
    [
        'nombre' => 'SEDE ACOBAMBA',
        'direccion' => 'JIRON MANCO CAPAC S/N',
        'referencia' => 'A MEDIA CUADRA DE LEONCIO PRADO. PARADERO DE BUSES INTERPROVINCIALES',
        'telefono' => '987 575 576',
        'horarios' => 'Lunes a Sábado: 8AM-6PM',
        'ubi' => ['ACOBAMBA', 'ACOBAMBA', 'HUANCAVELICA']
    ],
    [
        'nombre' => 'SEDE PAUCARÁ',
        'direccion' => 'JIRÓN 28 DE JULIO S/N',
        'referencia' => 'PASANDO CALLE LIMA',
        'telefono' => '986 628 017',
        'horarios' => 'Lunes a Sábado: 7AM-7PM | Domingos: 7AM-2PM',
        'ubi' => ['PAUCARA', 'ACOBAMBA', 'HUANCAVELICA']
    ],
    [
        'nombre' => 'SEDE SAN MIGUEL (LA MAR)',
        'direccion' => 'JIRÓN TÚPAC AMARU LT 11',
        'referencia' => 'A MEDIA CUADRA DEL MERCADO DE SAN MIGUEL',
        'telefono' => '994 852 718',
        'horarios' => 'Lunes a Viernes: 8:30AM-6:30PM | Sábados: 8:30AM-12PM',
        'ubi' => ['SAN MIGUEL', 'LA MAR', 'AYACUCHO']
    ],
    [
        'nombre' => 'SEDE TAMBO (LA MAR)',
        'direccion' => 'MARISCAL SUCRE S/N',
        'referencia' => 'SALIDA SAN MIGUEL',
        'telefono' => '944 574 339',
        'horarios' => 'Lunes a Sábado: 8AM-5PM | Domingos: 8AM-12PM',
        'ubi' => ['TAMBO', 'LA MAR', 'AYACUCHO']
    ],
    [
        'nombre' => 'SEDE HUANDO',
        'direccion' => 'AV HUANCAYO S/N PUEBLO HUANDO',
        'referencia' => 'A LADO DE LA IE26022',
        'telefono' => '979 326 734',
        'horarios' => 'Lunes a Sábado: 8AM-6PM',
        'ubi' => ['HUANDO', 'HUANCAVELICA', 'HUANCAVELICA']
    ],
    [
        'nombre' => 'SEDE CASA BLANCA',
        'direccion' => 'AV ANDRÉS AVELINO CÁCERES LT. 42',
        'referencia' => 'CARRETERA CENTRAL HUANCAYO HUANCAVELICA AL FRENTE DEL COLEGIO SAN IGNACIO DE LOYOLA',
        'telefono' => '929 949 640',
        'horarios' => 'Lunes a Sábado: 8AM-5PM',
        'ubi' => ['HUANCAYO', 'HUANCAYO', 'JUNIN']
    ]
];

echo "Iniciando sincronización de ESPINOZA CARGO...\n\n";

foreach ($agencias as $agencia) {
    list($distrito, $provincia, $departamento) = $agencia['ubi'];
    
    // Búsqueda ESTRICTA por Distrito -> Provincia -> Departamento
    $destino = Destino::whereHas('Provincia', function($q) use ($provincia, $departamento) {
        $q->where('nombre', 'LIKE', '%' . $provincia . '%')
          ->whereHas('Departamento', function($q2) use ($departamento) {
              $q2->where('nombre', 'LIKE', '%' . $departamento . '%');
          });
    })->where('nombre', 'LIKE', '%' . $distrito . '%')->first();
    
    if (!$destino) {
        echo "[ERROR] Destino no encontrado en BD: {$distrito} -> {$provincia} -> {$departamento}\n";
        continue;
    }
    
    $idDestino = $destino->idDestino;
    $direccionCompleta = $agencia['direccion'] . ' | REF: ' . $agencia['referencia'] . ' | HORARIO: ' . $agencia['horarios'];
    
    // Update or Create
    $sub = SubAgencia::updateOrCreate(
        [
            'idAgencia' => $idAgencia,
            'nombre_oficina' => $agencia['nombre']
        ],
        [
            'idDestino' => $idDestino,
            'direccion' => $direccionCompleta,
            'telefono' => $agencia['telefono'],
            'estado' => 1
        ]
    );

    if ($sub->wasRecentlyCreated) {
        echo "[CREADO] " . $agencia['nombre'] . " -> Destino ID: " . $idDestino . " (" . $distrito . ")\n";
    } else {
        echo "[ACTUALIZADO] " . $agencia['nombre'] . " -> Destino ID: " . $idDestino . " (" . $distrito . ")\n";
    }
    
    $count++;
}

echo "\nTOTAL IMPORTADOS O ACTUALIZADOS: $count\n";
