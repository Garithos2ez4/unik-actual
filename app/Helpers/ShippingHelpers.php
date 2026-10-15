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

if (!function_exists('es_feriado')) {
    /**
     * Indica si la fecha es feriado nacional (config/feriados.php).
     */
    function es_feriado(Carbon $fecha): bool
    {
        return array_key_exists($fecha->format('Y-m-d'), config('feriados', []));
    }
}

if (!function_exists('es_dia_habil')) {
    /**
     * Día hábil de la tienda: lunes a sábado que no sea feriado.
     */
    function es_dia_habil(Carbon $fecha): bool
    {
        return $fecha->dayOfWeek !== Carbon::SUNDAY && !es_feriado($fecha);
    }
}

if (!function_exists('calcular_fecha_envio')) {
    /**
     * Calcula la fecha de envío real como Carbon.
     * - Si pasa el corte de 5pm, empieza a contar desde el día siguiente.
     * - Si ese día es domingo o feriado, avanza hasta el siguiente día hábil.
     *
     * @param Carbon|null $fechaBase Si es null usa la hora actual de Lima.
     */
    function calcular_fecha_envio(?Carbon $fechaBase = null): Carbon
    {
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        if (es_despues_del_corte($fecha)) {
            $fecha->addDay();
        }

        // Límite de seguridad para evitar un bucle infinito por datos mal cargados
        for ($i = 0; $i < 15 && !es_dia_habil($fecha); $i++) {
            $fecha->addDay();
        }

        return $fecha->startOfDay();
    }
}

if (!function_exists('resolver_fecha_envio')) {
    /**
     * Calcula la fecha de envío correcta (siguiente día hábil si pasa el corte
     * o si se registra en domingo/feriado).
     *
     * @param Carbon|null $fechaBase Si es null usa la hora actual de Lima.
     * @return string Fecha en formato Y-m-d
     */
    function resolver_fecha_envio(?Carbon $fechaBase = null): string
    {
        return calcular_fecha_envio($fechaBase)->format('Y-m-d');
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
        return calcular_fecha_envio($fechaBase)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }
}

if (!function_exists('nombre_feriado')) {
    /**
     * Devuelve el nombre del feriado si la fecha es feriado, o null si no lo es.
     */
    function nombre_feriado(Carbon $fecha): ?string
    {
        $feriados = config('feriados', []);
        return $feriados[$fecha->format('Y-m-d')] ?? null;
    }
}

if (!function_exists('tiene_postergacion_envio')) {
    /**
     * Determina si la fecha de envío se postergará (por corte de 5pm, domingo o feriado).
     */
    function tiene_postergacion_envio(?Carbon $fechaBase = null): bool
    {
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        return calcular_fecha_envio($fecha)->format('Y-m-d') !== $fecha->format('Y-m-d');
    }
}

if (!function_exists('motivo_postergacion_envio')) {
    /**
     * Explica por qué se posterga el envío (feriado, domingo o después de las 5pm).
     */
    function motivo_postergacion_envio(?Carbon $fechaBase = null): ?string
    {
        $fecha = $fechaBase
            ? $fechaBase->copy()->setTimezone('America/Lima')
            : Carbon::now('America/Lima');

        if (!tiene_postergacion_envio($fecha)) {
            return null;
        }

        $feriado = nombre_feriado($fecha);
        $esDespues5 = es_despues_del_corte($fecha);
        $esDomingo = $fecha->dayOfWeek === Carbon::SUNDAY;

        if ($feriado && $esDespues5) {
            return "hoy es día feriado ({$feriado}) y ya pasó el horario de corte (5:00 PM)";
        }

        if ($feriado) {
            return "hoy es día feriado ({$feriado})";
        }

        if ($esDomingo) {
            return "hoy es domingo (día no laborable)";
        }

        if ($esDespues5) {
            return "son las 5:00 PM o más (fuera de horario de atención)";
        }

        return "es día no laborable";
    }
}

if (!function_exists('get_store_coordinates')) {
    /**
     * Obtiene las coordenadas geográficas de la tienda matriz (Av. Bolivia).
     *
     * @return array
     */
    function get_store_coordinates(): array
    {
        return [
            'lat' => -12.055775793937439,
            'lng' => -77.03773311047287
        ];
    }
}
