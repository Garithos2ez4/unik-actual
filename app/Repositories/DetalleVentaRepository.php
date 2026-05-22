<?php

namespace App\Repositories;

use App\Models\DetalleVenta;

class DetalleVentaRepository implements DetalleVentaRepositoryInterface
{
    public function create(array $data)
    {
        return DetalleVenta::create($data);
    }
}
