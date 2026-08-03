<?php
namespace App\Repositories;

use App\Models\Usuarios\Cliente;

class ClienteRepository implements ClienteRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        $this->modelColumns = (new Cliente())->getFillable();
    }

    public function all($cant)
    {
        return Cliente::with('TipoDocumento')->orderBy('idCliente', 'desc')->paginate($cant);
    }

    public function getOne($column,$data)
    {
        $this->validateColumns($column);
        return Cliente::where($column,'=',$data)->first();
    }

    public function validateDuplicity($type,$number)
    {
        return Cliente::where('idTipoDocumento','=',$type)->where('numeroDocumento','=',$number)->first();
    }

    public function searchCliente($doc,$cant)
    {
        return Cliente::with('TipoDocumento')->where(function($query) use ($doc) {
            $query->where('numeroDocumento', 'LIKE', '%'.$doc.'%')
                  ->orWhere('nombre', 'LIKE', '%'.$doc.'%')
                  ->orWhere('apellidoPaterno', 'LIKE', '%'.$doc.'%')
                  ->orWhere('apellidoMaterno', 'LIKE', '%'.$doc.'%');
        })->take($cant)->get();
    }

    public function searchClientePaginated($doc,$cant)
    {
        return Cliente::with('TipoDocumento')->where(function($query) use ($doc) {
            $query->where('numeroDocumento', 'LIKE', '%'.$doc.'%')
                  ->orWhere('nombre', 'LIKE', '%'.$doc.'%')
                  ->orWhere('apellidoPaterno', 'LIKE', '%'.$doc.'%')
                  ->orWhere('apellidoMaterno', 'LIKE', '%'.$doc.'%')
                  ->orWhere('correo', 'LIKE', '%'.$doc.'%')
                  ->orWhere('telefono', 'LIKE', '%'.$doc.'%');
        })->orderBy('idCliente', 'desc')->paginate($cant);
    }

    public function create(array $data)
    {
        return Cliente::create($data);
    }

    public function update(array $data, $id)
    {
        $cliente = Cliente::find($id);
        if ($cliente) {
            $cliente->update($data);
            return $cliente;
        }
        return null;
    }

    public function getLast(){
        return Cliente::orderBy('idCliente', 'desc')->first();
    }

    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}