<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Precios\Calculadora;
use Brick\Math\Internal\Calculator;
use Illuminate\Support\Arr;

class CalculadoraRepository implements CalculadoraRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Calculadora())->getFillable();
    }

    public function get()
    {
        return Calculadora::find(1);
    }

    public function update(array $data){
        $calc = Calculadora::find(1);
        $calc->update($data);
        return $calc;
    }

    public function updateTasaFija(array $data){
        $calc = Calculadora::find(2);
        $calc->update($data);
        return $calc;
    }

    public function updateTC($tc){
        $calc = Calculadora::first();
        $calc->update(['tasaCambio' => $tc]);
        return $calc;
    }

    public function findById(){
        return Calculadora::find(2);
    }

    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
