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
        return EgresoProducto::query()->where($column,'=', $data, 'and')->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::query()->where($column,'=', $data, 'and')->get();
    }
    
    public function getAllByMonth($year, $month, $cant){
        return EgresoProducto::query()->whereYear('fechaDespacho', '=', $year, 'and')
                                    ->whereMonth('fechaDespacho', '=', $month, 'and')
                                    ->orderBy('fechaDespacho','desc')
                                    ->paginate($cant);
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return EgresoProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function getEgresoBySerial($serial,$cant)
    {
        return EgresoProducto::query()->join('RegistroProducto','RegistroProducto.idRegistro','=','EgresoProducto.idRegistro', 'inner', false)
                            ->where(function($query){
                                $query->where('RegistroProducto.estado','=','ENTREGADO', 'and')
                                        ->orWhere('RegistroProducto.estado','=','DEVOLUCION', 'and')
                                        ->orWhere('RegistroProducto.estado','=','GARANTIA', 'and');
                            })
                            ->where('RegistroProducto.numeroSerie','LIKE', '%' . $serial . '%', 'and')
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