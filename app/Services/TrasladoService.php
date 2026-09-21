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
        return \App\Models\Inventario\Almacen::with('Filas')->get();
    }

    public function updateRegistros(array $array){
        foreach($array as $idRegistro => $dataInput){
            $idAlmacen = is_array($dataInput) ? ($dataInput['idAlmacen'] ?? null) : $dataInput;
            $idUbicacionExacta = is_array($dataInput) ? ($dataInput['idUbicacionExacta'] ?? null) : null;
            
            if(isset($idAlmacen) && $idAlmacen != ''){
                $registro = $this->registroRepository->getOne('idRegistro', $idRegistro);
                if ($registro) {
                    if ($registro->idAlmacen != $idAlmacen) {
                        $data = [
                            'idAlmacen' => $idAlmacen,
                            'ubicacion_especifica' => $idUbicacionExacta
                        ];
                        $this->updateStock($idRegistro, $idAlmacen);
                        $this->registroRepository->update($idRegistro, $data);
                    } else if ($idUbicacionExacta && $registro->ubicacion_especifica != $idUbicacionExacta) {
                        $this->registroRepository->update($idRegistro, ['ubicacion_especifica' => $idUbicacionExacta]);
                    }
                    
                    if ($idUbicacionExacta) {
                        $inventario = \App\Models\Inventario\Inventario::where('idProducto', $registro->DetalleComprobante->idProducto)
                            ->where('idAlmacen', $idAlmacen)
                            ->first();
                        if ($inventario) {
                            $inventario->update(['idUbicacionExacta' => $idUbicacionExacta]);
                        }
                    }
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