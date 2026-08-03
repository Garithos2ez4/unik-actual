<?php

namespace App\Services\Licencias\Handlers;

use App\Models\Licencias\Licencia;
use App\Models\Licencias\LicenciaUsada;

class UsarLicenciaParcialHandler
{
    use GuardarUsoLicencia;

    public function handle(Licencia $licencia, array $datos): void
    {
        if (!$licencia->tieneUsosDisponibles()) {
            throw new \Exception('La licencia ya no tiene usos disponibles');
        }

        $this->guardarUso($licencia, $datos);

        $licencia->descontarUso(1);

        if (!$licencia->tieneUsosDisponibles()) {
            $licencia->update(['estado' => 'USADA']);
        }
    }
}
