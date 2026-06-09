<?php
namespace App\Repositories;

interface ClienteRepositoryInterface
{
    public function all($cant);
    public function getOne($column,$data);
    public function validateDuplicity($type,$number);
    public function searchCliente($doc,$cant);
    public function searchClientePaginated($doc,$cant);
    public function create(array $data);
    public function update(array $data, $id);
    public function getLast();
}