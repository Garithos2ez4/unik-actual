<?php
namespace App\Services;

interface ProductoServiceInterface
{
    public function getAllProductsByColumn($column,$data,$cant,$querys);
    public function getOneProductByColumn($column,$data);
    public function searchProducts($input,$count,$filtros);
    public function insertProduct($array,$proveedor,$img1,$img2,$img3,$img4);
    public function updateProduct($id,$array,$img1,$img2,$img3,$img4);
    public function validateState($id);
    public function insertOrUpdateCaracteristicas($id,$arrayCreate,$arrayUpdate);
    public function validateDuplicySerial($id,$serial);
    public function getOneLabelGrupo($id);
    public function getAllLabelGrupo();
    public function getAllLabelGrupoXCategory($id);
    public function getOneLabelCategory($id);
    public function getAllLabelCategory();
    public function getAllLabelMarca();
    public function getMarcasBySearch($input);
    public function getAllLabelProveedor();
    public function getAllAlmacen();
    public function updateInventory($idProducto,$array);
    public function updateSeguimiento($idProducto,$array);
    public function getLastCodesProducts();
    public function searchAjaxProducts($column,$query);
    public function deleteCaracteristicaXProduct($idProducto,$idCaracteristica);
    public function filtroMarcas($column,$data);
    public function filtroEstados($column,$data);
    public function getYoutubeVideoId(?string $url): ?string;
    public function existsByModelo(string $modelo, $excludeId = null): bool;
}