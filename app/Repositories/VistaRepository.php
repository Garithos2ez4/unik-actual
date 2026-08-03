<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Usuarios\Vista;

class VistaRepository implements VistaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Vista())->getFillable();
    }
    
    public function all()
    {
        return Vista::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Vista::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Vista::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Vista::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Vista::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    
    public function create(array $data)
    {
        return Vista::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $vista = Vista::findOrFail($id);
        $vista->update($data);
        return $vista;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}