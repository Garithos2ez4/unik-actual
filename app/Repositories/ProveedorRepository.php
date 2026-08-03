<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Empresa\Preveedor;

class ProveedorRepository implements ProveedorRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Preveedor())->getFillable();
    }
    
    public function all()
    {
        return Preveedor::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Preveedor::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Preveedor::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Preveedor::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Preveedor::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return Preveedor::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = Preveedor::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }

    public function getLast(){
        return Preveedor::orderBy('idProveedor', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}