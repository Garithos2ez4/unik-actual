<?php

namespace App\Repositories;

interface HistorialTipoCambioRepositoryInterface
{
    public function updateOrCreateByDate(string $fecha, float $tasaCambio);
    public function getByDate(string $fecha);
}
