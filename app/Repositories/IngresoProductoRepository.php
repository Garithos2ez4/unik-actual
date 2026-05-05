<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\IngresoProducto;

class IngresoProductoRepository implements IngresoProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new IngresoProducto())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::where($column,'=', $data)->get();
    }
    
    public function getAllByComprobante($idComprobante){
        return IngresoProducto::join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                    ->join('DetalleComprobante','DetalleComprobante.idDetalleComprobante','=','RegistroProducto.idDetalleComprobante')
                    ->join('Comprobante','Comprobante.idComprobante','=','DetalleComprobante.idComprobante')
                    ->where('Comprobante.idComprobante','=',$idComprobante)->select('IngresoProducto.*')->get();
    }
    
    public function getAllByMonth($month,$cant,$querys){
        $query = IngresoProducto::query();
        $query->select('IngresoProducto.*');

        $query->join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                ->join('DetalleComprobante','DetalleComprobante.idDetalleComprobante','=','RegistroProducto.idDetalleComprobante')
                ->join('Comprobante','Comprobante.idComprobante','=','DetalleComprobante.idComprobante');

        $query->whereYear('IngresoProducto.fechaIngreso', $month->year)->whereMonth('IngresoProducto.fechaIngreso', $month->month);

        if(isset($querys)){
            if(isset($querys['usuario'])){
                $query->where('IngresoProducto.idUser','=',$querys['usuario']);
            }

            if(isset($querys['proveedor'])){
                $query->where('Comprobante.idProveedor','=',$querys['proveedor']);
            }

            if(isset($querys['almacen'])){
                $query->where('RegistroProducto.idAlmacen','=',$querys['almacen']);
            }

            if(isset($querys['estado'])){
                $query->where('RegistroProducto.estado','=',$querys['estado']);
            }
        }

        return $query->orderBy('IngresoProducto.fechaIngreso','desc')->paginate($cant);
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function getOneBySerialNumber($data)
    {
        return IngresoProducto::join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->where('RegistroProducto.estado', '<>', 'ENTREGADO')
                                ->where('RegistroProducto.estado', '<>', 'INVALIDO')
                                ->where('RegistroProducto.numeroSerie', '=',$data)
                                ->first();
    }
    
    public function searchBySerialNumber($data,$cant)
    {
        return IngresoProducto::join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->where('RegistroProducto.estado', '<>', 'ENTREGADO')
                                ->where('RegistroProducto.estado', '<>', 'INVALIDO')
                                ->where('RegistroProducto.numeroSerie', 'LIKE', '%' . $data . '%')
                                ->take($cant)
                                ->get();
    }

    public function getUsersByMonth($month){
        return IngresoProducto::select('IngresoProducto.idUser')->distinct()
                                ->join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->whereYear('IngresoProducto.fechaIngreso', $month->year)
                                ->whereMonth('IngresoProducto.fechaIngreso', $month->month)
                                ->get();
    }

    public function getProveedoresByMonth($month){
        return IngresoProducto::select('Comprobante.idProveedor')->distinct()
                                ->join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->join('DetalleComprobante','DetalleComprobante.idDetalleComprobante','=','RegistroProducto.idDetalleComprobante')
                                ->join('Comprobante','Comprobante.idComprobante','=','DetalleComprobante.idComprobante')
                                ->whereYear('IngresoProducto.fechaIngreso', $month->year)
                                ->whereMonth('IngresoProducto.fechaIngreso', $month->month)
                                ->get();
    }

    public function getAlmacenesByMonth($month){
        return IngresoProducto::select('RegistroProducto.idAlmacen')->distinct()
                                ->join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->whereYear('IngresoProducto.fechaIngreso', $month->year)
                                ->whereMonth('IngresoProducto.fechaIngreso', $month->month)
                                ->get();
    }

    public function getEstadosByMonth($month){
        return IngresoProducto::select('RegistroProducto.estado')->distinct()
                                ->join('RegistroProducto','RegistroProducto.idRegistro','=','IngresoProducto.idRegistro')
                                ->where('RegistroProducto.estado', '<>', 'INVALIDO')
                                ->whereYear('IngresoProducto.fechaIngreso', $month->year)
                                ->whereMonth('IngresoProducto.fechaIngreso', $month->month)
                                ->get();
    }
    
    public function create(array $data)
    {
        return IngresoProducto::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $ingreso = IngresoProducto::findOrFail($id);
        $ingreso->update($data);
        return $ingreso;
    }
    
    public function getLast(){
        return IngresoProducto::orderBy('idIngreso', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}