<?php
namespace App\Repositories;

interface EgresoProductoRepositoryInterface
{
    public function getOne($column,$data);
    public function getAllByColumn($column,$data);
    public function getAllByMonth($year, $month, $cant);
    public function searchOne($column,$data);
    public function searchList($column,$data);
    public function getEgresoBySerial($serial,$cant);
    public function create(array $data);
    public function update($id, array $data);
    public function getLast();
}