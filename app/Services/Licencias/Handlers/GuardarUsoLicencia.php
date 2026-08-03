<?php

namespace App\Services\Licencias\Handlers;

use App\Models\Licencias\Licencia;
use Illuminate\Http\UploadedFile;

trait GuardarUsoLicencia
{
    protected function guardarUso(Licencia $licencia, array $datos): void
    {
        $archivo = null;

        if (!empty($datos['archivo']) && $datos['archivo'] instanceof UploadedFile) {

            $nombreOriginal = preg_replace(
                '/[^A-Za-z0-9\-]/',
                '_',
                pathinfo($datos['archivo']->getClientOriginalName(), PATHINFO_FILENAME)
            );

            $extension = $datos['archivo']->getClientOriginalExtension();
            $nombreUnico = $nombreOriginal . '_' . uniqid() . '.' . $extension;

            $archivo = $datos['archivo']
                ->storeAs('licencias_usadas', $nombreUnico, 'public');
        }

        unset($datos['archivo']);

        \App\Models\Licencias\LicenciaUsada::create([
            'clave_key'   => $licencia->voucher_code,
            'id_licencia' => $licencia->id,
            'archivo'     => $archivo,
            ...$datos,
        ]);
    }
}