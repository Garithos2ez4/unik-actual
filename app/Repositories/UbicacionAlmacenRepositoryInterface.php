<?php
namespace App\Repositories;

interface UbicacionAlmacenRepositoryInterface
{
    public function all();
    public function getByAlmacen($idAlmacen);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
