<?php

namespace App\Http\Controllers;


use App\Services\HeaderService;
use App\Models\Inventario\Almacen;

class AlmacenController extends Controller
{
    public function index(){
        //variables de la cabecera
        $serviceHeader = new HeaderService;
        $userModel = $serviceHeader->getModelUser();
        
        //Variables propias del controlador
        $categorias = Almacen::all();
        dd($categorias);
            return redirect()->route('dashboard',['user' => $userModel]);

    }
}