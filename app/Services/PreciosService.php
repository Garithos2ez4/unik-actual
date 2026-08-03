<?php

namespace App\Services;

use App\Models\Precios\Calculadora;
use App\Models\Precios\Comision;
use App\Models\Empresa\Empresa;

class PreciosService
{

    public function getIgv($precio){
        $calculadora = $this->getCalculadora();
            $precioIgv = $precio * $this->porcent($calculadora->igv);
        return $precioIgv;
    }

    public function getPrecioSinFacturar($precio,$grupo,$tipo){
        $comisiones = Comision::where('idGrupoProducto','=',$grupo)->get();

        foreach($comisiones as $comision){
            if($precio > $this->validateMoney($comision->RangoPrecio->rangoMin,$tipo) && $precio < $this->validateMoney($comision->RangoPrecio->rangoMax,$tipo)){
                return $this->getIgv($precio) * $this->porcent($comision->comision);
            }
        }
    }

    public function getPrecioFacturado($precio,$grupo,$tipo){
        $calculadora = $this->getCalculadora();
        return $this->getPrecioSinFacturar($precio,$grupo,$tipo) * $this->porcent($calculadora->facturacion);
    }

    public function getPromedio($precio,$grupo,$tipo){
        return ($this->getPrecioSinFacturar($precio,$grupo,$tipo) + $this->getPrecioFacturado($precio,$grupo,$tipo))/2;
    }

    public function getEspecialPrice($precio){
            $totalPrecio = $this->getIgv($precio) ;
        return $totalPrecio;
    }

    public function getPrecioCalculado($precio,$grupo,$tipo,$estado){
        if($estado == 'EXCLUSIVO' || $estado == 'OFERTA'){
            $total =  $this->getEspecialPrice($precio);
        }else{
            $total = $this->getPromedio($precio,$grupo,$tipo);
        }

         return $total;
    }

    public function getPrecioTotal($precio,$grupo,$tipo,$estado,$ganancia){
        $totales = array();
        $empresas = Empresa::select('idEmpresa','nombreComercial','comision')->get();
        foreach($empresas as $empresa){
            $totales[] = ['id' => $empresa->idEmpresa,
                        'empresa' => $empresa->nombreComercial,
                        'precio' => ($this->getPrecioCalculado($precio,$grupo,$tipo,$estado) * $this->porcent($empresa->comision)) + $ganancia];
        }
        return $totales;
    }

    private function getCalculadora(){
        return Calculadora::first();
    }

    private function validateMoney($precio,$tipo){
         $calculadora = $this->getCalculadora();
        if($tipo == 'SOL'){
            return $precio * $calculadora->tasaCambio;
        }else{
            return $precio;
        }
    }

    private function porcent($number){
        return 1 + ($number / 100);
    }
}
