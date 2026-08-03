<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\GrupoProducto;

class GrupoProductoRepository implements GrupoProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new GrupoProducto())->getFillable();
    }
    
    public function all()
    {
        return GrupoProducto::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return GrupoProducto::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return GrupoProducto::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return GrupoProducto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return GrupoProducto::where($column, 'LIKE', '%' . $data . '%')->get();
    }
    
    public function getSpecs($idGrupo){
        $caracteristicasGrupo = DB::select('CALL sp_get_caracteristicaxgrupo(?)', [$idGrupo]);
        return $caracteristicasGrupo;
    }

    public function create(array $productoData)
    {
        return GrupoProducto::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $producto = GrupoProducto::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }

    public function getLast(){
        return GrupoProducto::orderBy('idGrupoProducto', 'desc')->first();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}