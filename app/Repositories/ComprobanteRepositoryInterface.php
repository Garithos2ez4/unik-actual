<?php
namespace App\Repositories;

interface ComprobanteRepositoryInterface
{
    public function getOne($column,$data);
    public function getAllByColumn($column,$data);
    public function getAllByMonth(\Carbon\Carbon $month,$cant,$querys);
    public function searchOne($column,$data);
    public function searchList($column,$data);
    public function searchTakeList($column, $data,$cant);
    public function searchByInvoiceOrSerial($data, $cant);
    public function create(array $data);
    public function update($id, array $data);
    public function remove($id);
    public function validateDuplicity($number,$type,$idProveedor);
    public function getLast();
    public function getAllRegistrosByComprobanteId($id);
    public function getUsuariosByMonth(\Carbon\Carbon $month);
    public function getProveedoresByMonth(\Carbon\Carbon $month);
    public function getDocumentosByMonth(\Carbon\Carbon $month);
    public function getEstadosByMonth(\Carbon\Carbon $month);
}