<?php
namespace App\Services;

interface ConfiguracionServiceInterface
{
    public function getAllAlmacenes();
    public function getAllProveedores();
    public function getAllCategorias();
    public function getAllMarcas();
    public function getAllRangos();
    public function getAllEmpresas();
    public function getAllPlataformas();
    public function getAllTipoProductos();
    public function getOneCaracteristica($idCaracteristica);
    public function getOneCategoria($idCategoria);
    public function updateCorreoEmpresa($id,$correo);
    public function updateCuentaBancaria($id,$titular,$cuenta);
    public function createCuentaBancaria($data);
    public function createMetodoPago($data);
    public function createTipoMetodoPago($data);
    public function updateMetodoPago($id, $data);
    public function updateTipoMetodoPago($id, $data);
    public function updateComisionEmpresa($id,$comision);
    public function updateCalculadora($igv,$fact);
    public function updateCalculadoraTasaFija($igv,$fact,$tc);
    public function updateComisionValue($idGrupo,$idRango,$comision);
    public function getAllEspecificaciones();
    public function createCaracteristica($descripcion,$tipo,$sugerencias);
    public function removeCaracteristica($idCaracteristica);
    public function updateOrCreateCaracteristica($id,$tipo,$updates,$creates);
    public function insertCaracteristicaXGrupo($idGrupo,$idCaracteristica);
    public function deleteCaracteristicaXGrupo($idGrupo,$idCaracteristica);
    public function createAlmacen($desc);
    public function createProveedor($razonSocial,$nombreComercial,$ruc);
    public function createComisionPlataforma($idPlataforma,$comision,$flete);
    public function deleteComisionPlataforma($idComisionPlataforma);
    public function createMarcaProducto($nombre,$img);
    public function createGrupoProducto($categoria,$grupo,$tipo,$img);
    public function removeSugerencia($idSugerencia,$tipo);
}
