<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Empresa\CuentasTransferencia;

class CuentasTransferenciaRepository implements CuentasTransferenciaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new CuentasTransferencia())->getFillable();
    }
    
    public function all()
    {
        return CuentasTransferencia::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return CuentasTransferencia::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return CuentasTransferencia::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return CuentasTransferencia::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return CuentasTransferencia::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    
    public function create(array $data)
    {
        return CuentasTransferencia::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $cuenta = CuentasTransferencia::findOrFail($id);
        $cuenta->update($data);
        return $cuenta;
    }
    
    public function getLast()
    {
        return CuentasTransferencia::orderBy('idCuentaBancaria', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}