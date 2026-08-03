<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Inventario\Almacen;

class AlmacenRepository implements AlmacenRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Almacen())->getFillable();
    }
    
    public function all()
    {
        return Almacen::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Almacen::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Almacen::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Almacen::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Almacen::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return Almacen::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = Almacen::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }

    public function getLast(){
        return Almacen::orderBy('idAlmacen', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}