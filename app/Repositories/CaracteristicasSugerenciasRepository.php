<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\Caracteristicas_Sugerencias;

class CaracteristicasSugerenciasRepository implements CaracteristicasSugerenciasRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Caracteristicas_Sugerencias())->getFillable();
    }
    
    public function all()
    {
        return Caracteristicas_Sugerencias::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas_Sugerencias::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas_Sugerencias::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas_Sugerencias::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas_Sugerencias::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $data)
    {
        return Caracteristicas_Sugerencias::create($data);
    }

    public function update($id, array $data)
    {
        $car = Caracteristicas_Sugerencias::findOrFail($id);
        $car->update($data);
        return $car;
    }

    public function remove($id){
        $model = Caracteristicas_Sugerencias::findOrFail($id);
        $model->delete();
    }

    public function getLast(){
        $caracteristica = Caracteristicas_Sugerencias::orderBy('idSugerencia','desc')->first();
        return $caracteristica;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}