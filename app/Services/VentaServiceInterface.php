<?php

namespace App\Services;

interface VentaServiceInterface
{
    
    public function createVenta(array $ventaData, array $detallesData);

    public function getAllVentas($paginate = 50);

    public function getVentaById($idVenta);
}
