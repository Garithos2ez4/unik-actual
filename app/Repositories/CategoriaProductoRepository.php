<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\CategoriaProducto;

class CategoriaProductoRepository implements CategoriaProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new CategoriaProducto())->getFillable();
    }
    
    public function all()
    {
        return CategoriaProducto::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaProducto::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return CategoriaProducto::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = CategoriaProducto::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }

    public function getLast()
    {
        return CategoriaProducto::orderBy('idCategoria', 'desc')->first();
    }
}