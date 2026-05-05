<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\EgresoProducto;

class EgresoProductoRepository implements EgresoProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new EgresoProducto())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::where($column,'=', $data)->get();
    }
    
    public function getAllByMonth($year, $month, $cant){
        return EgresoProducto::whereYear('fechaDespacho', $year)
                                    ->whereMonth('fechaDespacho', $month)
                                    ->orderBy('fechaDespacho','desc')
                                    ->paginate($cant);
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function getEgresoBySerial($serial,$cant)
    {
        return EgresoProducto::join('RegistroProducto','RegistroProducto.idRegistro','=','EgresoProducto.idRegistro')
                            ->where(function($query){
                                $query->where('RegistroProducto.estado','=','ENTREGADO')
                                        ->orWhere('RegistroProducto.estado','=','DEVOLUCION')
                                        ->orWhere('RegistroProducto.estado','=','GARANTIA');
                            })
                            ->where('RegistroProducto.numeroSerie','LIKE', '%' . $serial . '%')
                            ->take($cant)
                            ->get();
    }
    
    public function create(array $data)
    {
        return EgresoProducto::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $ingreso = EgresoProducto::findOrFail($id);
        $ingreso->update($data);
        return $ingreso;
    }
    
    public function getLast(){
        return EgresoProducto::orderBy('idEgreso', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}