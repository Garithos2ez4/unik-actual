<?php

namespace App\Repositories;

interface VentaRepositoryInterface
{
    public function create(array $data);
    public function getOne($key, $value);
    public function getAll($paginate);
}
