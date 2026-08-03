<?php

namespace App\Services\Licencias\Handlers;

use App\Models\Licencias\Licencia;
use App\Models\Licencias\LicenciaUsada;

class UsarLicenciaCompletaHandler
{
    public function handle(Licencia $licencia, array $datos): void
    {
        if (!$licencia->tieneUsosDisponibles()) {
            throw new \Exception('La licencia ya no tiene usos disponibles');
        }

        $usosRestantes = $licencia->cantidad_usos;

        for ($i = 0; $i < $usosRestantes; $i++) {
            LicenciaUsada::create([
                'clave_key'   => $licencia->voucher_code,
                'id_licencia' => $licencia->id,
                ...$datos,
            ]);
        }

        // Consumimos todos los usos
        $licencia->update([
            'usos_disponibles' => 0,
            'estado' => 'USADA'
        ]);
    }
}
