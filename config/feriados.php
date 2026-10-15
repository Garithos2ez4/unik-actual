<?php

/*
|--------------------------------------------------------------------------
| Feriados nacionales del Perú
|--------------------------------------------------------------------------
| Usados por resolver_fecha_envio() (app/Helpers/ShippingHelpers.php) para
| que un envío registrado en feriado o después del corte pase al siguiente
| día hábil. Formato: 'Y-m-d' => 'Nombre del feriado'.
|
| Actualizado al 2026-10-14. Agregar cada año los feriados nuevos y los días
| no laborables que decrete el Ejecutivo si la tienda no atiende ese día.
*/

return [
    // ===== 2026 =====
    '2026-01-01' => 'Año Nuevo',
    '2026-04-02' => 'Jueves Santo',
    '2026-04-03' => 'Viernes Santo',
    '2026-05-01' => 'Día del Trabajo',
    '2026-06-07' => 'Batalla de Arica y Día de la Bandera',
    '2026-06-29' => 'San Pedro y San Pablo',
    '2026-07-23' => 'Día de la Fuerza Aérea del Perú',
    '2026-07-28' => 'Fiestas Patrias',
    '2026-07-29' => 'Fiestas Patrias',
    '2026-08-06' => 'Batalla de Junín',
    '2026-08-30' => 'Santa Rosa de Lima',
    '2026-10-08' => 'Combate de Angamos',
    '2026-11-01' => 'Todos los Santos',
    '2026-12-08' => 'Inmaculada Concepción',
    '2026-12-09' => 'Batalla de Ayacucho',
    '2026-12-25' => 'Navidad',

    // ===== 2027 =====
    '2027-01-01' => 'Año Nuevo',
    '2027-03-25' => 'Jueves Santo',
    '2027-03-26' => 'Viernes Santo',
    '2027-05-01' => 'Día del Trabajo',
    '2027-06-07' => 'Batalla de Arica y Día de la Bandera',
    '2027-06-29' => 'San Pedro y San Pablo',
    '2027-07-23' => 'Día de la Fuerza Aérea del Perú',
    '2027-07-28' => 'Fiestas Patrias',
    '2027-07-29' => 'Fiestas Patrias',
    '2027-08-06' => 'Batalla de Junín',
    '2027-08-30' => 'Santa Rosa de Lima',
    '2027-10-08' => 'Combate de Angamos',
    '2027-11-01' => 'Todos los Santos',
    '2027-12-08' => 'Inmaculada Concepción',
    '2027-12-09' => 'Batalla de Ayacucho',
    '2027-12-25' => 'Navidad',
];
