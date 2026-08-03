<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Ventas\TipoComprobante;

class TipoComprobanteRepository implements TipoComprobanteRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new TipoComprobante())->getFillable();
    }
    
    public function all()
    {
        return TipoComprobante::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return TipoComprobante::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return TipoComprobante::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return TipoComprobante::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return TipoComprobante::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    
    public function create(array $data)
    {
        return TipoComprobante::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $tipComp = TipoComprobante::findOrFail($id);
        $tipComp->update($data);
        return $tipComp;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}