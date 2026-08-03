<?php

namespace App\Repositories;

use App\Models\Precios\HistorialTipoCambio;

class HistorialTipoCambioRepository implements HistorialTipoCambioRepositoryInterface
{
    public function updateOrCreateByDate(string $fecha, float $tasaCambio)
    {
        return HistorialTipoCambio::updateOrCreate(
            ['fecha' => $fecha],
            ['tasa_cambio' => $tasaCambio]
        );
    }

    public function getByDate(string $fecha)
    {
        return HistorialTipoCambio::where('fecha', $fecha)->first();
    }
}
