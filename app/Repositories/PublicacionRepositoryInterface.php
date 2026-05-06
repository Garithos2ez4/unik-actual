<?php
namespace App\Repositories;

interface PublicacionRepositoryInterface
{
    public function all();
    public function getOne($column,$data);
    public function getAllByColumn($column,$data);
    public function searchOne($column,$data);
    public function searchList($column,$data);
    public function getByMonth($month,$year);
    public function validateSkuDuplicity($sku,$idPlataforma);
    public function create(array $data);
    public function update($id, array $data);
    public function getLast();
    public function searchByEgreso($serial,$cant);
    public function getOldPublicaciones($cantidad);
    public function getMostSoldPublicaciones($limit = 5);
}