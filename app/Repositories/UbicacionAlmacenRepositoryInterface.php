<?php
namespace App\Repositories;

interface UbicacionAlmacenRepositoryInterface
{
    public function all();
    public function getOne($id);
    public function getByAlmacen($idAlmacen);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
