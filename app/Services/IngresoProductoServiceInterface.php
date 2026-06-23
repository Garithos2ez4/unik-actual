<?php
namespace App\Services;

interface IngresoProductoServiceInterface
{
    public function getByComprobante($idComprobante);
    public function getByMonth($date,$cant,$querys);
    public function searchAjaxIngreso($data);
    public function deleteIngreso($id);
    public function getAllLabelProveedor();
    public function getAllTipoComprobante();
    public function getAllAlmacen();
    public function filtroUsuario($date);
    public function filtroAlmacen($date);
    public function filtroProveedor($date);
    public function filtroEstado($date);
    public function updateRegistro($idRegistro,$estado,$observacion,$ubicacion_especifica=null);
    public function getOneIngreso($data);
}