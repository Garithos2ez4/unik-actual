<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\MarcaProducto;

class MarcaProductoRepository implements MarcaProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new MarcaProducto())->getFillable();
    }
    
    public function all()
    {
        return MarcaProducto::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return MarcaProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return MarcaProducto::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return MarcaProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return MarcaProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return MarcaProducto::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = MarcaProducto::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }

    public function getLast(){
        return MarcaProducto::orderBy('idMarca', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}