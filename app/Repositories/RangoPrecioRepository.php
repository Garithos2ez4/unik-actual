<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Precios\RangoPrecio;

class RangoPrecioRepository implements RangoPrecioRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new RangoPrecio())->getFillable();
    }
    
    public function all()
    {
        return RangoPrecio::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return RangoPrecio::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return RangoPrecio::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return RangoPrecio::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return RangoPrecio::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    public function create(array $data)
    {
        return RangoPrecio::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $rango = RangoPrecio::findOrFail($id);
        $rango->update($data);
        return $rango;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}