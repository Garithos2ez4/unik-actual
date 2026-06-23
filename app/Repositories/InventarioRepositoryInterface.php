<?php
namespace App\Repositories;

interface InventarioRepositoryInterface
{
    public function all();
    public function getOne(string $column, mixed $data);
    public function getAllByColumn(string $column, mixed $data);
    public function getAllWhereFindStock();
    public function getAllByColumnWhereFindStock(string $column, mixed $data);
    public function searchOne(string $column, mixed $data);
    public function searchList(string $column, mixed $data);
    public function create(array $data);
    public function update(mixed $id, array $data);
    public function updateUbicacion(mixed $idProducto, array $data);
    public function addStock(mixed $idProducto, mixed $idAlmacen);
    public function removeStock(mixed $idProducto, mixed $idAlmacen);
}