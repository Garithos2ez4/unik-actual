<?php
namespace App\Services;

interface ClienteServiceInterface
{
    public function getAllTipoDocumentos();
    public function createCliente($nombre,$apellidoPaterno,$apellidoMaterno,$tipoDocumento,$numeroDocumento,$telefono,$correo);
    public function updateCliente($id,$nombre,$apellidoPaterno,$apellidoMaterno,$tipoDocumento,$numeroDocumento,$telefono,$correo);
    public function paginateAllCliente($cant);
    public function searchAjaxCLiente($doc);
    public function searchClientePaginated($doc, $cant);
}