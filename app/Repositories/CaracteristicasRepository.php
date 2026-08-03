<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Catalogo\Caracteristicas;

class CaracteristicasRepository implements CaracteristicasRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Caracteristicas())->getFillable();
    }
    
    public function all()
    {
        return Caracteristicas::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Caracteristicas::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return Caracteristicas::create($productoData);
    }

    public function update($idProducto, array $productoData)
    {
        $car = Caracteristicas::findOrFail($idProducto);
        $car->update($productoData);
        return $car;
    }

    public function remove($id){
        $model = Caracteristicas::findOrFail($id);
        $model->delete();
    }

    public function getLast(){
        $caracteristica = Caracteristicas::select('idCaracteristica')->orderBy('idCaracteristica','desc')->first();
        return $caracteristica;
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}