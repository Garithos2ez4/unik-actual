<?php
namespace App\Repositories;

use App\Models\Ventas\Garantia;
use Carbon\Carbon;

class GarantiaRepository implements GarantiaRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Garantia())->getFillable();
    }

    public function paginateAllByMonth(Carbon $date,int $cant){
        return Garantia::whereMonth('fechaGarantia',$date->month)->paginate($cant);
    }

    public function getOne($id){
        return Garantia::where('idGarantia','=',$id)->first();
    }

    public function create(array $data){
        return Garantia::create($data);
    }

    public function getLast(){
        return Garantia::orderBy('idGarantia','desc')->first();
    }

    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}