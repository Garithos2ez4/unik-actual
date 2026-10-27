<?php

namespace App\Services;

use App\Models\Precios\Calculadora;
use App\Models\Precios\Comision;
use App\Models\Empresa\Empresa;

class PreciosService
{
    private static $calculadoraCache = null;
    private static $comisionesCache = [];
    private static $empresasCache = null;

    public static function clearCache()
    {
        self::$calculadoraCache = null;
        self::$comisionesCache = [];
        self::$empresasCache = null;
    }

    public static function preloadGrupos($gruposIds)
    {
        $comisiones = Comision::with('RangoPrecio')
            ->whereIn('idGrupoProducto', $gruposIds)
            ->get()
            ->groupBy('idGrupoProducto');

        foreach ($gruposIds as $id) {
            if (!isset(self::$comisionesCache[$id])) {
                self::$comisionesCache[$id] = $comisiones->get($id, collect());
            }
        }
    }

    public function getIgv($precio){
        $calculadora = $this->getCalculadora();
        $precioIgv = $precio * $this->porcent($calculadora->igv);
        return $precioIgv;
    }

    public function getPrecioSinFacturar($precio,$grupo,$tipo){
        if (!isset(self::$comisionesCache[$grupo])) {
            self::$comisionesCache[$grupo] = Comision::with('RangoPrecio')
                ->where('idGrupoProducto', '=', $grupo)
                ->get();
        }
        $comisiones = self::$comisionesCache[$grupo];

        foreach($comisiones as $comision){
            if (!$comision->RangoPrecio) {
                continue;
            }
            if($precio > $this->validateMoney($comision->RangoPrecio->rangoMin,$tipo) && $precio < $this->validateMoney($comision->RangoPrecio->rangoMax,$tipo)){
                return $this->getIgv($precio) * $this->porcent($comision->comision);
            }
        }
        return null;
    }

    public function getPrecioFacturado($precio,$grupo,$tipo){
        $calculadora = $this->getCalculadora();
        return $this->getPrecioSinFacturar($precio,$grupo,$tipo) * $this->porcent($calculadora->facturacion);
    }

    public function getPromedio($precio,$grupo,$tipo){
        $sinFacturar = $this->getPrecioSinFacturar($precio,$grupo,$tipo);
        $facturado = $this->getPrecioFacturado($precio,$grupo,$tipo);
        return ($sinFacturar + $facturado) / 2;
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
        if (self::$empresasCache === null) {
            self::$empresasCache = Empresa::select('idEmpresa','nombreComercial','comision')->get();
        }
        $empresas = self::$empresasCache;
        $calculadora = $this->getCalculadora();

        // Convertir ganancia a SOLES si el tipo de moneda es SOL
        // ya que $ganancia siempre viene en dolares desde el frontend
        $gananciaConvertida = $ganancia;
        if($tipo == 'SOL'){
            $gananciaConvertida = $ganancia * $calculadora->tasaCambio;
        }

        foreach($empresas as $empresa){
            $totales[] = ['id' => $empresa->idEmpresa,
                        'empresa' => $empresa->nombreComercial,
                        'precio' => ($this->getPrecioCalculado($precio,$grupo,$tipo,$estado) * $this->porcent($empresa->comision)) + $gananciaConvertida];
        }
        return $totales;
    }

    private function getCalculadora(){
        if (self::$calculadoraCache === null) {
            self::$calculadoraCache = Calculadora::first();
        }
        return self::$calculadoraCache;
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
