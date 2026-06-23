<?php
namespace App\Services;

use App\Repositories\AlmacenRepositoryInterface;
use App\Repositories\ComprobanteRepositoryInterface;
use App\Repositories\DetalleComprobanteRepositoryInterface;
use Carbon\Carbon;
use App\Repositories\IngresoProductoRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\ProveedorRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use App\Repositories\TipoComprobanteRepositoryInterface;
use Illuminate\Support\Arr;

class IngresoProductoService implements IngresoProductoServiceInterface
{
    protected $ingresoRepository;
    protected $tipoComprobanteRepository;
    protected $proveedorRepository;
    protected $registroProductoRepository;
    protected $headerService;
    protected $inventarioRepository;
    protected $almacenRepository;
    protected $detalleComprobanteRepository;
    protected $comprobanteRepository;

    public function __construct(IngresoProductoRepositoryInterface $ingresoRepository,
                                ProveedorRepositoryInterface $proveedorRepository,
                                TipoComprobanteRepositoryInterface $tipoComprobanteRepository,
                                RegistroProductoRepositoryInterface $registroProductoRepository,
                                HeaderServiceInterface $headerService,
                                InventarioRepositoryInterface $inventarioRepository,
                                AlmacenRepositoryInterface $almacenRepository,
                                DetalleComprobanteRepositoryInterface $detalleComprobanteRepository,
                                ComprobanteRepositoryInterface $comprobanteRepository
                                )
    {
        $this->ingresoRepository = $ingresoRepository;
        $this->proveedorRepository = $proveedorRepository;
        $this->tipoComprobanteRepository = $tipoComprobanteRepository;
        $this->registroProductoRepository = $registroProductoRepository;
        $this->headerService = $headerService;
        $this->inventarioRepository = $inventarioRepository;
        $this->almacenRepository = $almacenRepository;
        $this->detalleComprobanteRepository = $detalleComprobanteRepository;
        $this->comprobanteRepository = $comprobanteRepository;
    }
    
    public function getByComprobante($idComprobante){
        return $this->ingresoRepository->getAllByComprobante($idComprobante);
    }
    
    public function getByMonth($date,$cant,$querys){
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->ingresoRepository->getAllByMonth($carbonMonth,$cant,$querys);
    }

    public function searchAjaxIngreso($data){
        $ingreso = $this->ingresoRepository->searchBySerialNumber($data,5)
                    ->map(function($x){
                        $x->fechaIngresoPerso = Carbon::parse($x->fechaIngreso)->format('d-m-Y');
                        $x->fechaMovimiento = Carbon::parse($x->fechaMovimiento)->format('d-m-Y');
                        $x->Usuario = $x->Usuario->makeHidden(['pass','tokenSesion','registroSesion','estadoUsuario','horaSesion','bandeja']);
                        $x->Registro = $x->RegistroProducto;
                        $x->Producto = $x->RegistroProducto ? $x->RegistroProducto->DetalleComprobante->Producto->makeHidden(['descripcionProducto','imagenProducto1','imagenProducto2','imagenProducto3','imagenProducto4']) : null;
                        $x->Proveedor = $x->RegistroProducto ? $x->RegistroProducto->DetalleComprobante->Comprobante->Preveedor : null;
                        $x->Almacen = $x->RegistroProducto ? $x->RegistroProducto->Almacen : null;
                        return $x;
                    });
        return $ingreso;
    }
    
    public function deleteIngreso($id){
        if($id){
            $ingreso = $this->ingresoRepository->getOne('idIngreso',$id);
            $dataRegistro = ['estado' => 'INVALIDO',
                            'fechaMovimiento' => now()];
            $dataIngreso = ['idUser' => $this->headerService->getModelUser()->idUser];

            $registro = $ingreso->RegistroProducto;
            $detalle = $registro->DetalleComprobante;
            $comprobante = $detalle->Comprobante;

            $dataDetalle = ['precioCompra' => $detalle->precioCompra - $detalle->precioUnitario];
            $dataComprobante = ['totalCompra' => $comprobante->totalCompra - $detalle->precioUnitario];
            $this->registroProductoRepository->update($ingreso->idRegistro,$dataRegistro);
            $this->ingresoRepository->update($ingreso->idIngreso,$dataIngreso);
            $this->inventarioRepository->removeStock($registro->DetalleComprobante->idProducto,$registro->idAlmacen);
            $this->detalleComprobanteRepository->update($detalle->idDetalleComprobante,$dataDetalle);
            $this->comprobanteRepository->update($comprobante->idComprobante,$dataComprobante);
        }
    }

    public function updateRegistro($idRegistro,$estado,$observacion,$ubicacion_especifica=null){
        $data = ['estado' => (is_null($estado) ? 'NUEVO' : $estado) ,
                'fechaMovimiento' => now(),
                'observacion' => $observacion,
                'ubicacion_especifica' => $ubicacion_especifica];
        $this->registroProductoRepository->update($idRegistro,$data);
    }
    
    public function getAllLabelProveedor(){
        return $this->proveedorRepository->all();
    }

    public function getAllTipoComprobante(){
        $tipoComprobante = $this->tipoComprobanteRepository->all();
        return $tipoComprobante;
    }

    public function getAllAlmacen(){
        return $this->almacenRepository->all();
    }

    public function filtroUsuario($date){
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->ingresoRepository->getUsersByMonth($carbonMonth);
    }

    public function filtroAlmacen($date){
        $almacenes = array();
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        $idAlmacenes = $this->ingresoRepository->getAlmacenesByMonth($carbonMonth);
        
        foreach($idAlmacenes as $id){
            $almacenes[] = $this->almacenRepository->getOne('idAlmacen',$id->idAlmacen);
        }

        return $almacenes;
    }

    public function filtroEstado($date){
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->ingresoRepository->getEstadosByMonth($carbonMonth);
    }

    public function filtroProveedor($date){
        $proveedores = array();
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        $idProveedores = $this->ingresoRepository->getProveedoresByMonth($carbonMonth);

        foreach($idProveedores as $id){
            $proveedores[] = $this->proveedorRepository->getOne('idProveedor',$id->idProveedor);
        }

        return $proveedores; 
    }

    public function getOneIngreso($data)
    {
        $ingreso = $this->ingresoRepository->getOneBySerialNumber($data);

        if ($ingreso) {
            // Formatear fechas
            $ingreso->fechaIngresoPerso = Carbon::parse($ingreso->fechaIngreso)->format('d-m-Y');
            $ingreso->fechaMovimiento = Carbon::parse($ingreso->fechaMovimiento)->format('d-m-Y');

            // Ocultar atributos sensibles del usuario
            $ingreso->Usuario = $ingreso->Usuario->makeHidden(['pass', 'tokenSesion', 'registroSesion', 'estadoUsuario', 'horaSesion', 'bandeja']);

            // Asignar relaciones
            $ingreso->Registro = $ingreso->RegistroProducto;
            $ingreso->Producto = $ingreso->RegistroProducto ? $ingreso->RegistroProducto->DetalleComprobante->Producto->makeHidden(['descripcionProducto', 'imagenProducto1', 'imagenProducto2', 'imagenProducto3', 'imagenProducto4']) : null;
            $ingreso->Proveedor = $ingreso->RegistroProducto ? $ingreso->RegistroProducto->DetalleComprobante->Comprobante->Preveedor : null;
            $ingreso->Almacen = $ingreso->RegistroProducto ? $ingreso->RegistroProducto->Almacen : null;
        }

        return $ingreso;
    }

}