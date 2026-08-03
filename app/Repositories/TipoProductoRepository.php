<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\TipoProducto;

class TipoProductoRepository implements TipoProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new TipoProducto())->getFillable();
    }
    
    public function all()
    {
        return TipoProducto::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return TipoProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return TipoProducto::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return TipoProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return TipoProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return TipoProducto::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = TipoProducto::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}