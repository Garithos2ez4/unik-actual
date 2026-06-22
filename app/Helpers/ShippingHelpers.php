<?php

use Carbon\Carbon;

if (!function_exists('es_despues_del_corte')) {
    /**
     * Determina si una fecha superó la hora de corte de la tienda (17:00 Lima).
     *
     * @param Carbon|null $fechaBase Si es null usa la hora actual de Lima.
     */
    function es_despues_del_corte(?Carbon $fechaBase = null): bool
    {
        $horaCorte = 17; // 17:00 = 5 PM
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        return $fecha->hour >= $horaCorte;
    }
}

if (!function_exists('resolver_fecha_envio')) {
    /**
     * Calcula la fecha de envío correcta.
     * Si pasa el corte de 5pm, salta al día siguiente (omitiendo domingos → va al lunes).
     *
     * @param Carbon|null $fechaBase Si es null usa la hora actual de Lima.
     * @return string Fecha en formato Y-m-d
     */
    function resolver_fecha_envio(?Carbon $fechaBase = null): string
    {
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        if (es_despues_del_corte($fecha)) {
            $siguiente = $fecha->copy()->addDay();

            // Si el siguiente día es domingo, avanzar al lunes
            if ($siguiente->dayOfWeek === Carbon::SUNDAY) {
                $siguiente->addDay();
            }

            return $siguiente->format('Y-m-d');
        }

        return $fecha->format('Y-m-d');
    }
}

if (!function_exists('calcular_fecha_real_legible')) {
    /**
     * Devuelve la fecha de registro real en formato legible para español.
     * Usada para mostrar mensajes al colaborador y al cliente.
     *
     * @param Carbon|null $fechaBase Si es null usa la hora actual de Lima.
     * @return string Ej: "23 de junio de 2026"
     */
    function calcular_fecha_real_legible(?Carbon $fechaBase = null): string
    {
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        if (es_despues_del_corte($fecha)) {
            $siguiente = $fecha->copy()->addDay();

            if ($siguiente->dayOfWeek === Carbon::SUNDAY) {
                $siguiente->addDay();
            }

            return $siguiente->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        }

        return $fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }
}
