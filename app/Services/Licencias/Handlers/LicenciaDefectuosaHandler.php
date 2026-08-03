<?php

namespace App\Services\Licencias\Handlers;

use App\Models\Licencias\Licencia;
use App\Models\Licencias\LicenciaDefectuosa;
use App\Models\Licencias\LicenciaRecuperada;
class LicenciaDefectuosaHandler implements CambioEstadoHandlerInterface
{ 
     public function handle(Licencia $licencia, array $datosForm): void
    {
        LicenciaDefectuosa::create([
            'clave_key'   => $licencia->voucher_code,
            'id_licencia' => $licencia->id,
            ...$datosForm
        ]);

        if (!empty($datosForm['idRecuperada'])) {
            LicenciaRecuperada::where('id', $datosForm['idRecuperada'])->delete();
        }

        $licencia->estado = 'DEFECTUOSA';
        $licencia->save();
    }
}
