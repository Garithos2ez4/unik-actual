<?php

namespace App\Repositories;

use App\Models\Venta;

class VentaRepository implements VentaRepositoryInterface
{
    public function create(array $data)
    {
        return Venta::create($data);
    }

    public function getOne($key, $value)
    {
        return Venta::where($key, $value)->first();
    }

    public function getAll($paginate = 50)
    {
        return Venta::orderBy('idVenta', 'desc')->paginate($paginate);
    }
}
