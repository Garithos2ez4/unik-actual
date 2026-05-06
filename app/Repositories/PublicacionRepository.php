<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Publicacion;

class PublicacionRepository implements PublicacionRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Publicacion())->getFillable();
    }

    public function all()
    {
        return Publicacion::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Publicacion::where($column, '=', $data, 'and')->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Publicacion::where($column, '=', $data, 'and')->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Publicacion::where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Publicacion::where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function getByMonth($month, $year)
    {
        return Publicacion::whereMonth('fechaPublicacion', $month, 'and', 'inner')
            ->whereYear('fechaPublicacion', $year, 'and')
            ->get();
    }

    public function searchByEgreso($data, $cant)
    {
        return Publicacion::where('estado', '<>', -1, 'and')->where('sku', 'LIKE', "%{$data}%", "and")->take($cant)->get();
    }

    public function validateSkuDuplicity($sku, $idPlataforma)
    {
        return Publicacion::join('CuentasPlataforma', 'CuentasPlataforma.idCuentaPlataforma', '=', 'Publicacion.idCuentaPlataforma', 'inner', false)
            ->where('Publicacion.sku', '=', $sku)
            ->where('CuentasPlataforma.idPlataforma', '=', $idPlataforma)->first();
    }

    public function getOldPublicaciones($cantidad)
    {
        return Publicacion::orderBy('fechaPublicacion', 'asc')
            ->take($cantidad)
            ->get();
    }

    public function getMostSoldPublicaciones($limit = 5)
    {
        return Publicacion::query()
            ->join('EgresoProducto', 'EgresoProducto.idPublicacion', '=', 'Publicacion.idPublicacion', 'inner', false)
            ->select('Publicacion.*', DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->groupBy('Publicacion.idPublicacion')
            ->orderBy('total_ventas', 'desc')
            ->take($limit)
            ->get();
    }

    public function create(array $data)
    {
        return Publicacion::create($data);
    }

    public function update($id, array $data)
    {
        $publicacion = Publicacion::findOrFail($id);
        $publicacion->update($data);
        return $publicacion;
    }

    public function getLast()
    {
        return Publicacion::select('idPublicacion')->orderBy('idPublicacion', 'desc')->first();
    }

    private function validateColumns($column)
    {
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
