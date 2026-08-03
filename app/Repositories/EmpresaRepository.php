<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Empresa\Empresa;

class EmpresaRepository implements EmpresaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Empresa())->getFillable();
    }
    
    public function all()
    {
        return Empresa::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Empresa::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Empresa::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Empresa::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Empresa::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    public function create(array $data)
    {
        return Empresa::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->update($data);
        return $empresa;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}