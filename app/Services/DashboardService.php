<?php
namespace App\Services;

use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\ProductoRepositoryInterface;
use App\Repositories\PublicacionRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;

class DashboardService implements DashboardServiceInterface
{
    protected $registroRepository;
    protected $inventarioRepository;
    protected $productoRepository;
    protected $publicacionRepository;

    public function __construct(RegistroProductoRepositoryInterface $registroRepository,
                                InventarioRepositoryInterface $inventarioRepository,
                                ProductoRepositoryInterface $productoRepository,
                                PublicacionRepositoryInterface $publicacionRepository)
    {
        $this->registroRepository = $registroRepository;
        $this->inventarioRepository = $inventarioRepository;
        $this->productoRepository = $productoRepository;
        $this->publicacionRepository = $publicacionRepository;
    }

    public function getRegistrosXEstados(){
        $array = array();
        $array[] = ['estado' => 'NUEVO', 'titulo' => 'Nuevos' , 'cantidad' => $this->registroRepository->getAllByColumnByThisMonth('estado','NUEVO',100)->total(),'bg' => 'bg-sistema-uno','icon' => 'boxes','fecha' => 'Este mes'];
        $array[] = ['estado' => 'ENTREGADO', 'titulo' => 'Entregados' , 'cantidad' => $this->registroRepository->getAllByColumnByThisMonth('estado','ENTREGADO',100)->total(),'bg' => 'bg-green','icon' => 'cart','fecha' => 'Este mes'];
        $array[] = ['estado' => 'DEVOLUCION', 'titulo' => 'Devoluciones' , 'cantidad' => $this->registroRepository->paginateAllByColumn('estado','DEVOLUCION',50)->total(),'bg' => 'bg-warning','icon' => 'truck','fecha' => 'Hasta la fecha'];
        $array[] = ['estado' => 'GARANTIA','titulo' => 'Garantía' ,'cantidad' => $this->registroRepository->paginateAllByColumn('estado','GARANTIA',50)->total(),'bg' => 'bg-marron','icon' => 'award','fecha' => 'Hasta la fecha'];
        $array[] = ['estado' => 'ABIERTO', 'titulo' => 'Abiertos' , 'cantidad' => $this->registroRepository->paginateAllByColumn('estado','ABIERTO',50)->total(),'bg' => 'bg-purple','icon' => 'dropbox','fecha' => 'Hasta la fecha'];
        $array[] = ['estado' => 'DEFECTUOSO', 'titulo' => 'Defectuosos' , 'cantidad' => $this->registroRepository->paginateAllByColumn('estado','DEFECTUOSO',50)->total(),'bg' => 'bg-danger','icon' => 'x-lg','fecha' => 'Hasta la fecha'];

        
        return $array;
    }

    public function getAllInventory(){
        return $this->inventarioRepository->getAllWhereFindStock();
    }

    public function getInventoryByAlmacen($idAlmacen){
        return $this->inventarioRepository->getAllByColumnWhereFindStock('idAlmacen',$idAlmacen);
    }

    public function getTotalProducts(){
        return $this->productoRepository->total();
    }

    public function getStockMinProducts(){
        return $this->productoRepository->getStockMinProducts();
    }

    public function getOldPublicaciones(){
        return $this->publicacionRepository->getOldPublicaciones(5);
    }

    public function getMostStockProducts($limit = 5){
        return $this->productoRepository->getMostStockProducts($limit);
    }

    public function getNuevosInventario(){
        return $this->registroRepository->getAllByColumnByThisMonth('estado','NUEVO',250);
    }
    public function getEntregadosInventario(){
        return $this->registroRepository->getAllByColumnByThisMonth('estado','ENTREGADO',250);
    }
    public function getDevolucionesInventario(){
        return $this->registroRepository->paginateAllByColumn('estado','DEVOLUCION',50);
    }
    public function getAbiertosInventario(){
        return $this->registroRepository->paginateAllByColumn('estado','ABIERTO',50);
    }
    public function getDefectuososInventario(){
        return $this->registroRepository->paginateAllByColumn('estado','DEFECTUOSO',50);
    }
    public function getGarantiaInventario(){
        return $this->registroRepository->paginateAllByColumn('estado','GARANTIA',50);
    }
}