<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Ventas\DetalleComprobante;

class DetalleComprobanteRepository implements DetalleComprobanteRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new DetalleComprobante())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return DetalleComprobante::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return DetalleComprobante::where($column,'=', $data)->get();
    }
    
    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return DetalleComprobante::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return DetalleComprobante::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    
    public function create(array $data)
    {
        return DetalleComprobante::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $comprobante = DetalleComprobante::findOrFail($id);
        $comprobante->update($data);
        return $comprobante;
    }
    
    public function getLast(){
        return DetalleComprobante::orderBy('idDetalleComprobante', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}