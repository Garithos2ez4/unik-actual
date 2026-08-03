<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\Caracteristicas_Grupo;

class CaracteristicasGrupoRepository implements CaracteristicasGrupoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Caracteristicas_Grupo())->getFillable();
    }
    
    public function getOne($idGrupo,$idCaracteristica)
    {
        return Caracteristicas_Grupo::where('idGrupoProducto','=', $idGrupo)->where('idCaracteristica','=',$idCaracteristica)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas_Grupo::where($column,'=', $data)->get();
    }


    public function create(array $data)
    {
        return Caracteristicas_Grupo::create($data);
    }

    public function remove($idGrupo,$idCaracteristica){
        $model = $this->getOne($idGrupo,$idCaracteristica);
        $model->delete();
    }

    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}