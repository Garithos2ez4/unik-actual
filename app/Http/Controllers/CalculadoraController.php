<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HeaderService;
use App\Services\PreciosService;
use App\Services\CalculadoraServiceInterface;


class CalculadoraController extends Controller
{
    protected $calculadoraService;

    public function __construct(CalculadoraServiceInterface $calculadoraService)
    {
        $this->calculadoraService = $calculadoraService;
    }

    public function index(Request $request){
        //variables de la cabecera
        $serviceHeader = new HeaderService;
        $userModel = $serviceHeader->getModelUser();

        //variables del controlador
        $valores = $this->calculadoraService->get();
        $plataformas = $this->calculadoraService->getComisionByRelation('ComisionPlataforma');
        $categorias = $this->calculadoraService->getAllLabelCategory();
        $comisiones = $this ->calculadoraService ->allComision();

        return view('calculadora',['user' => $userModel,
                                    'valores' => $valores,
                                    'categorias' => $categorias,
                                    'comisiones' => $comisiones,
                                    'plataformas' => $plataformas
                                ]);
    }

    public function cotizador(Request $request){
        //variables de la cabecera
        $serviceHeader = new HeaderService;
        $userModel = $serviceHeader->getModelUser();

        //variables del controlador
        $valores = $this->calculadoraService->get();
        $plataformas = $this->calculadoraService->getComisionByRelation('ComisionPlataforma');
        
        return view('calculadora.cotizador',['user' => $userModel,
                                    'valores' => $valores,
                                    'plataformas' => $plataformas
                                ]);
    }

    public function calculate(Request $request)
    {
        $query = $request->input('query');
        $type = $request->input('type');
        $grup = $request->input('grup');

        $servicePrecio = new PreciosService;
        $precios = array();
        $precios = ['tsfact' => $servicePrecio->getPrecioSinFacturar($query,$grup,$type),
                    'tfact' => $servicePrecio->getPrecioFacturado($query,$grup,$type),
                    'ganancia' => ($servicePrecio->getPrecioSinFacturar($query,$grup,$type) - $servicePrecio->getIgv($query)),
                    'promedio' => $servicePrecio->getPromedio($query,$grup,$type)];

        $results = $precios;

        return response()->json($results);
    }
}
