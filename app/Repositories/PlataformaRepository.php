<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Empresa\Plataforma;

class PlataformaRepository implements PlataformaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Plataforma())->getFillable();
    }
    
    public function all()
    {
        return Plataforma::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Plataforma::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Plataforma::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Plataforma::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Plataforma::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    public function getByRelation($table){
        return Plataforma::whereHas($table)->get();
    }
    
    public function create(array $data)
    {
        return Plataforma::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $plat = Plataforma::findOrFail($id);
        $plat->update($data);
        return $plat;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}