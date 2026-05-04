<?php
namespace App\Services;

interface DashboardServiceInterface 
{
    public function getRegistrosXEstados();
    public function getAllInventory();
    public function getInventoryByAlmacen($idAlmacen);
    public function getTotalProducts();
    public function getStockMinProducts();
    public function getOldPublicaciones();
    public function getMostStockProducts($limit = 5);
    public function getNuevosInventario();
    public function getEntregadosInventario();
    public function getDevolucionesInventario();
    public function getAbiertosInventario();
    public function getDefectuososInventario();
    public function getGarantiaInventario();
}