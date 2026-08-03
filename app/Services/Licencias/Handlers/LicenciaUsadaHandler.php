<?php

namespace App\Services\Licencias\Handlers;
use App\Models\Licencias\Licencia;
use App\Models\Licencias\LicenciaUsada;
use App\Models\Licencias\LicenciaRecuperada;
use Illuminate\Http\UploadedFile;

class LicenciaUsadaHandler implements CambioEstadoHandlerInterface
{
    public function handle(Licencia $licencia, array $datosForm): void
    {
        $archivo = null;

        if (!empty($datosForm['archivo']) && $datosForm['archivo'] instanceof UploadedFile) {
            $nombreOriginal = preg_replace(
                '/[^A-Za-z0-9\-]/',
                '_',
                pathinfo($datosForm['archivo']->getClientOriginalName(), PATHINFO_FILENAME)
            );

            $extension = $datosForm['archivo']->getClientOriginalExtension();
            $nombreUnico = $nombreOriginal . '_' . uniqid() . '.' . $extension;

            $archivo = $datosForm['archivo']
                ->storeAs('licencias_usadas', $nombreUnico, 'public');
        }

        unset($datosForm['archivo']);

        LicenciaUsada::create([
            'clave_key'   => $licencia->voucher_code,
            'id_licencia' => $licencia->id,
            'archivo'     => $archivo,
            ...$datosForm,
        ]);

        if (!empty($datosForm['idRecuperada'])) {
            LicenciaRecuperada::where('id', $datosForm['idRecuperada'])->delete();
        }

        $licencia->estado = 'USADA';
        $licencia->save();
    }
}
