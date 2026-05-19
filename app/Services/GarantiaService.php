<?php
namespace App\Services;

use App\Repositories\GarantiaRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;
use Carbon\Carbon;

class GarantiaService implements GarantiaServiceInterface
{
    protected $garantiaRepository;
    protected $tipoDocumentoRepository;
    protected $registroRepository;

    public function __construct(GarantiaRepositoryInterface $garantiaRepository,
                                TipoDocumentoRepositoryInterface $tipoDocumentoRepository,
                                RegistroProductoRepositoryInterface $registroRepository)
    {
        $this->garantiaRepository = $garantiaRepository;
        $this->tipoDocumentoRepository = $tipoDocumentoRepository;
        $this->registroRepository = $registroRepository;
    }

    public function getGarantiasByMonth(Carbon $date, int $cant)
    {
        return $this->garantiaRepository->paginateAllByMonth($date,$cant);
    }

    public function getAllTipoDocumentos(){
        return $this->tipoDocumentoRepository->all();
    }

    public function insertGarantia($idRegistro,$idCliente,$numeroComprobante,$recepcion,$estado,$fallo){
        $data = ['idGarantia' => $this->getNewIdGarantia(),
                'idRegistro' => $idRegistro,
                'idCliente' => $idCliente,
                'fechaGarantia' => now(),
                'numeroComprobante' => $numeroComprobante,
                'recepcion' => $recepcion,
                'estado' => $estado,
                'falla' => $fallo
                ];
        
        $this->garantiaRepository->create($data);
        $this->updateStateGarantia($data['idRegistro'], $fallo);
        $response = $this->garantiaRepository->getOne($data['idGarantia']);
        return $response;
    }

    public function searchAjaxRegistro($serial)
    {
        $egresos = $this->registroRepository->searchByGarantia($serial, 5);
        $result = $egresos->map(function ($details) {
            return [
                'nombreProducto' => $details->DetalleComprobante->Producto->nombreProducto,
                'codigoProducto' => $details->DetalleComprobante->Producto->codigoProducto,
                'idRegistroProducto' => $details->idRegistro,
                'numeroSerie' => $details->numeroSerie,
                'estado' => $details->estado,
                'modelo' => $details->DetalleComprobante->Producto->modelo,
                'image' => $details->DetalleComprobante->Producto->imagenProducto1,
                'marca' => $details->DetalleComprobante->Producto->MarcaProducto->nombreMarca
            ];
        });
        return $result;
    }

    public function getOneAjaxRegistro($serial)
    {
        $egreso = $this->registroRepository->getByGarantia($serial);

        if ($egreso) {
            $details = $egreso->DetalleComprobante->Producto;

            $result = [
                'nombreProducto' => $details->nombreProducto,
                'codigoProducto' => $details->codigoProducto,
                'idRegistroProducto' => $egreso->idRegistro,
                'numeroSerie' => $egreso->numeroSerie,
                'estado' => $egreso->estado,
                'modelo' => $details->modelo,
                'image' => $details->imagenProducto1,
                'marca' => $details->MarcaProducto->nombreMarca
            ];
            return $result;
        }

        return [];
    }

    private function updateStateGarantia($idRegistro, $falla){
        if(!empty($idRegistro)){
            $data = [
                'estado' => 'GARANTIA',
                'observacion' => $falla
            ];
            $this->registroRepository->update($idRegistro,$data);
        }
    }

    private function getNewIdGarantia(){
        $garantia = $this->garantiaRepository->getLast();
        $id = $garantia ? $garantia->idGarantia : 0;
        return $id + 1;
    } 
}