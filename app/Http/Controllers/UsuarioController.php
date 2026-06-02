<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\UsuarioServiceInterface;

class UsuarioController extends Controller
{
    protected $headerService;
    protected $usuarioService;


    public function __construct(HeaderServiceInterface $headerService,
                                UsuarioServiceInterface $usuarioService)
    {
     $this->headerService = $headerService;
     $this->usuarioService = $usuarioService;
    }
    public function index(){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        //Variables propias del controlador
        $usuarios = $this->usuarioService->allUsers();
        $vistas = $this->usuarioService->getAllViews();
        
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 6){
                return view('usuarios',['user' => $userModel,
                'usuarios' => $usuarios,
                'vistas' => $vistas
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
        
    }
    
    public function create(){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 6){
                return view('createuser',['user' => $userModel]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
    
    public function createUser(Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();
        
        //Variables propias del controlador
        $user = $request->input('user');
        $pass = $request->input('pass');

        $validateUser = $this->usuarioService->getUser($user);
        if($validateUser){
            $this->headerService->sendFlashAlerts('Usuario existente','Verificar si el usuario existe en la base','error','btn-danger');
            return redirect()->back();
        }

        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 6){
                $array = ['user' => $user,
                        'pass'=>$pass];

                $this->usuarioService->createUser($array);

                $this->headerService->sendFlashAlerts('Usuario registrado','Operacion realizada correctamente','success','btn-success');
                return redirect()->back();
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->back();return redirect()->route('dashboard',['user' => $userModel]);

    }
    
    public function updatePass(Request $request){
        //Variables propias del controlador
        $id = $request->input('id');
        $pass = $request->input('pass');

        $this->usuarioService->updatePass($id,$pass);
        
        $this->headerService->sendFlashAlerts('Contraseña actualizada','Actualizacion correcta','success','btn-success');
        return redirect()->back();
    }

    public function updateBandeja(Request $request){
        $id = $request->input('id');
        $bandeja = $request->input('bandeja');

        
        if(isset($id)){
            $this->usuarioService->updateBandeja($id,$bandeja);
        }
        return  response()->json(['message' => 'Datos enviados correctamente.']);
    }

    public function colaboradoresIndex(){
        $userModel = $this->headerService->getModelUser();
        
        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 15){ // 15: Asignar Pendientes
                $usuarios = $this->usuarioService->allUsers()->where('estadoUsuario', 1);
                return view('colaboradores.index',[
                    'user' => $userModel,
                    'usuarios' => $usuarios
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }

    public function getBandejaColaborador(Request $request){
        $id = $request->input('id');
        if(isset($id)){
            $usuario = $this->usuarioService->getUserId($id);
            if($usuario){
                return response()->json(['bandeja' => $usuario->bandeja]);
            }
        }
        return response()->json(['bandeja' => '']);
    }
    
    public function updateUser(Request $request){
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //Variables propias del controlador
        $user = $request->input('id');
        $state = $request->input('estado');
        $accesos = $request->input('access');
        
        $boolEstado = false;
        if($state == 'true'){
            $boolEstado = true;
        }

        foreach($userModel->Accesos as $acceso){
            if($acceso->idVista == 6){
                if(!is_null($user) && !is_null($boolEstado)){
                    $this->usuarioService->updateAccesos($user,$boolEstado,$accesos);
                    $this->headerService->sendFlashAlerts('Permisos actualizados','Operacion realizada correctamente','success','btn-success');
                    return redirect()->back();  
                }
                $this->headerService->sendFlashAlerts('Faltan Datos','Operacion fallida','success','btn-danger');
                return redirect()->back(); 
            }
        }
        
        $this->headerService->sendFlashAlerts('Acceso denegado','No tienes permiso para ingresar a esta pestaña','warning','btn-danger');
        return redirect()->route('dashboard',['user' => $userModel]);
    }
}