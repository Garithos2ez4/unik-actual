<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Empresa\CuentasPlataforma;

class CuentasPlataformaRepository implements CuentasPlataformaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new CuentasPlataforma())->getFillable();
    }
    
    public function all()
    {
        return CuentasPlataforma::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return CuentasPlataforma::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return CuentasPlataforma::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return CuentasPlataforma::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return CuentasPlataforma::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    public function getByRelation($table){
        return CuentasPlataforma::whereHas($table)->get();
    }

    public function getLast()
    {
        $product = CuentasPlataforma::select('idCuentaPlataforma')->orderBy('idCuentaPlataforma','desc')->first();
        return $product;
    }
    
    public function create(array $data)
    {
        return CuentasPlataforma::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $cuenta = CuentasPlataforma::findOrFail($id);
        $cuenta->update($data);
        return $cuenta;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}