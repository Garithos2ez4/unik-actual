<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Precios\Comision;

class ComisionRepository implements ComisionRepositoryInterface
{
    protected $modelColumns;
    
    public function __construct(){
        
        $this->modelColumns = (new Comision())->getFillable();
    }

    public function all(){
        
        return Comision::all();
    }

    public function getOne($column, $data){
        $this->validateColumns($column);
        return Comision::where($column,'=',$data)->first();
        
    }

    public function getAllByColumn($column, $data){
        $this->validateColumns($column);
        return Comision::where($column, '=',$data)->get();
    }

    public function searchOne($column, $data){
        $this->validateColumns($column);
        return Comision::where($column, 'LIKE' , '%' . $data . '%')->first();
    }

    public function searchList($column, $data){
        $this->validateColumns($column);
        return Comision::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $comisionData){
        return Comision::create($comisionData);
    }

    public function update($idRango,$idGrupo, array $comisionData){
        $comision = Comision::where('idGrupoProducto','=',$idGrupo)
                            ->where('idRango','=', $idRango)->first();
        $comision->update($comisionData);
        return $comision;
    }

    private function validateColumns($column){
        if(!in_array($column, $this->modelColumns)){
            throw new \InvalidArgumentException("La Columna o fila '$column' no es válida");
        }

    }
}