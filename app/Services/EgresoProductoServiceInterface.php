<?php
namespace App\Services;

interface EgresoProductoServiceInterface
{
    public function getEgresosByMonth($date,$cant);
    public function searchAjaxRegistro($serial);
    public function getOneAjaxRegistro($serial);
    public function searchAjaxEgreso($serie,$cant);
    public function getRegistro($serial);
    public function getPublicacion($sku);
    public function createEgreso(array $data, array $registros);
    public function getAllAlmacenes();
    public function updateEgreso($transaction, $idEgreso, $observacion, $plataforma = null, $fechaDevolucion = null, $dataEgreso = []);
}