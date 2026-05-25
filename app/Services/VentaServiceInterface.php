<?php

namespace App\Services;

interface VentaServiceInterface
{
    
    public function createVenta(array $ventaData, array $detallesData, array $pagos = []);

    public function getAllVentas($paginate = 50);

    public function getVentaById($idVenta);
}
