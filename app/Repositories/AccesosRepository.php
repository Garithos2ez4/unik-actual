<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Usuarios\Accesos;
use Exception;

class AccesosRepository implements AccesosRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Accesos())->getFillable();
    }
    
    public function all()
    {
        return Accesos::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Accesos::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Accesos::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Accesos::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Accesos::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function deleteByUser($id){
        if (!empty($id)) {
            try{
                Accesos::where('idUser', $id)->delete();
            }catch(Exception $e){
                throw new \InvalidArgumentException("No hay usuarios.");
            }
            
        }
    }

    public function create($idVista,$idUser){
        try{
            DB::beginTransaction();
            $access = new Accesos();
            $access->idVista = $idVista;
            $access->idUser = $idUser;
            
            $access->save();
            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            throw new \InvalidArgumentException("Error al crear usuarios.");
        }
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}