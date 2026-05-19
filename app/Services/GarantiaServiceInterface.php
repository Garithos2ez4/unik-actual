<?php
namespace App\Services;

use Carbon\Carbon;

interface GarantiaServiceInterface
{
    public function getGarantiasByMonth(Carbon $date,int $cant);
    public function getAllTipoDocumentos();
    public function insertGarantia($idRegistro,$idCliente,$numeroComprobante,$recepcion,$estado,$fallo);
    public function searchAjaxRegistro($serial);
    public function getOneAjaxRegistro($serial);
}