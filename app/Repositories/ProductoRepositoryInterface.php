<?php
namespace App\Repositories;

interface ProductoRepositoryInterface
{
    public function all();
    public function getOne($column, $data);
    public function getLast();
    public function getAllByColumn($column, $data);
    public function searchOne($column, $data);
    public function searchList($column, $data);
    public function searchTakeList($column, $data, $cont);
    /** AHORA: $cont → $perPage */
    public function searchPaginateList($column, $perPage, $data, $filtros = null);
    /** AHORA: $cant → $perPage, $querys → $filtros */
    public function paginateAllByColumn($column, $data, $perPage, $filtros);
    /** AHORA: $query → $searchTerm, $cant → $perPage */
    public function searchIntensiveProducts($searchTerm, $perPage, $filtros);
    public function create(array $productoData);
    public function update($idProducto, array $data);
    /** ELIMINADO: getCodes() — duplicado de getProductsCodes() */
    public function getProductsCodes();
    public function validateSerial($id, $serial);
    public function total();
    public function getStockMinProducts();
    public function getProductsWithStock();
    public function getMostSoldProducts($limit = 5);
    public function getMarcasByColumn($column, $data);
    public function getMarcasBySearchTerm($query);
    public function getEstadosByColumn($column, $data);
    /** RENOMBRADO: getPaginationNull() → getEmptyPagination() */
    public function getEmptyPagination($perPage = 10);
}