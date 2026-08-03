<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Inventario\Inventario_Proveedor;

class ProveedorInventarioRepository implements ProveedorInventarioRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Inventario_Proveedor())->getFillable();
    }
    
    public function all()
    {
        return Inventario_Proveedor::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Inventario_Proveedor::where($column,'=', $data)->first();
    }
    
    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Inventario_Proveedor::where($column,'=', $data)->get();
    }
    
    public function getLast()
    {
        $data = Inventario_Proveedor::select('idInventarioProveedor')->orderBy('idInventarioProveedor','desc')->first();
        return $data;
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Inventario_Proveedor::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Inventario_Proveedor::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $proveedor)
    {
        try{
            DB::beginTransaction();
            $model = new Inventario_Proveedor();
            $model->idInventarioProveedor = $proveedor['idInventarioProveedor'];
            $model->idProducto = $proveedor['idProducto'];
            $model->idProveedor = $proveedor['idProveedor'];
            $model->stock = $proveedor['stock'];
            $model->estado = $proveedor['estado'];
            
            $model->save();
            DB::commit();
            return $model;
        }catch(Exception $e){
            DB::rollBack();
            return null;
        }
        
    }

    public function update($idProducto, array $data)
    {
        try{
            DB::beginTransaction();
            $seguimiento = Inventario_Proveedor::where('idProducto','=',$idProducto)->first();
            $seguimiento->idProveedor = $data['idProveedor'];
            $seguimiento->stock = $data['stock'];
            
            $seguimiento->save();
            DB::commit();
            return $seguimiento;
        }catch(Exception $e){
            DB::rollBack();
            return null;
        }
        
        $seguimiento->update($data);
        return $seguimiento;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}