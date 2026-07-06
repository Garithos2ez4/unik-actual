<?php
namespace App\Services;

use App\Repositories\AlmacenRepositoryInterface;
use App\Repositories\IngresoProductoRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;

class TrasladoService implements TrasladoServiceInterface
{
    protected $almacenRepository;
    protected $registroRepository;
    protected $inventarioRepository;
    protected $headerService;
    protected $ingresoRepository;

    public function __construct(AlmacenRepositoryInterface $almacenRepository,
                                RegistroProductoRepositoryInterface $registroRepository,
                                InventarioRepositoryInterface $inventarioRepository,
                                HeaderServiceInterface $headerService,
                                IngresoProductoRepositoryInterface $ingresoRepository)
    {
        $this->almacenRepository = $almacenRepository;
        $this->registroRepository = $registroRepository;
        $this->inventarioRepository = $inventarioRepository;
        $this->headerService = $headerService;
        $this->ingresoRepository = $ingresoRepository;
    }

    public function getAllAlmacenes(){
        return $this->almacenRepository->all();
    }

    public function updateRegistros(array $array){
        foreach($array as $idRegistro => $idAlmacen){
            if(isset($idAlmacen)){
                $registro = $this->registroRepository->getOne('idRegistro', $idRegistro);
                if ($registro && $registro->idAlmacen != $idAlmacen) {
                    $data = [
                        'idAlmacen' => $idAlmacen,
                        'ubicacion_especifica' => null
                    ];
                    $this->updateStock($idRegistro, $idAlmacen);
                    $this->registroRepository->update($idRegistro, $data);
                }
            }
        }
    }

    private function updateStock($idRegistro,$idAlmacen){
        $userModel = $this->headerService->getModelUser();
        $registro = $this->registroRepository->getOne('idRegistro',$idRegistro);
        //descontar
        $this->inventarioRepository->removeStock($registro->DetalleComprobante->idProducto,$registro->idAlmacen);
        //incrementar
        $this->inventarioRepository->addStock($registro->DetalleComprobante->idProducto,$idAlmacen);
        $ingresoData = ['idUser' => $userModel->idUser];
        $this->ingresoRepository->update($registro->IngresoProducto->idIngreso,$ingresoData);
    } 
}