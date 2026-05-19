<?php
namespace App\Repositories;

interface RegistroProductoRepositoryInterface
{
    public function getOne($column,$data);
    public function getAllByColumn($column,$data);
    public function paginateAllByColumn($column,$data,$cant);
    public function getAllByColumnByThisMonth($column,$data,$cant);
    public function searchOne($column,$data);
    public function searchList($column,$data);
    public function getByIngreso($month);
    public function create(array $data);
    public function update($id, array $data);
    public function getLast();
    public function searchByEgreso($serial,$cant);
    public function getByEgreso($serial);
    public function searchByGarantia($serial,$cant);
    public function getByGarantia($serial);
    public function validateSerie($idProveedor,$serie);
    public function getSerialsByProduct($idProduct);
}