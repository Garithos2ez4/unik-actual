<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Usuarios\Usuario;
use Exception;
use Illuminate\Support\Facades\Hash;

class UsuarioRepository implements UsuarioRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Usuario())->getFillable();
    }
    
    public function all()
    {
        return Usuario::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Usuario::where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Usuario::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Usuario::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Usuario::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function getLast()
    {
        $user = Usuario::select('idUser')->orderBy('idUser','desc')->first();
        return $user;
    }
    
    
    public function create(array $data)
    {
        try{
            DB::beginTransaction();
            
            $usuario = new Usuario();
            $usuario->idUser = $data['idUser'];
            $usuario->user = $data['user'];
            $usuario->pass = Hash::make($data['pass']);
            $usuario->tokenSesion = 0;
            $usuario->registroSesion = false;
            $usuario->horaSesion = date('Y-m-d H:i:s');
            $usuario->estadoUsuario = true;
            
            $usuario->save();
            
            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            throw new \InvalidArgumentException("Error al crear Usuario");
        }
    }
    
    
    public function update($id, array $data)
    {
        $user = Usuario::findOrFail($id);
        $user->update($data);
        
    }

    public function updatePass($id, $pass)
    {
        try{
            DB::beginTransaction();
            $user = Usuario::findOrFail($id);
            $user->pass = Hash::make($pass);

            $user->save();
            DB::commit();
            return $user;
        }catch(Exception $e){
            DB::rollBack();
            throw new \InvalidArgumentException("Error al actualizar la contraseña");
        }
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}