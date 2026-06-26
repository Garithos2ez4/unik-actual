<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\RegistroProducto;

class RegistroProductoRepository implements RegistroProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new RegistroProducto())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, '=', $data, 'and')->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, '=', $data, 'and')->get();
    }

    public function paginateAllByColumn($column, $data, $cant)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, '=', $data, 'and')
            ->orderBy('fechaMovimiento', 'desc')->paginate($cant);
    }

    public function getAllByColumnByThisMonth($column, $data, $cant)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, '=', $data, 'and')
            ->whereYear('fechaMovimiento', '=', now()->year, 'and')
            ->whereMonth('fechaMovimiento', '=', now()->month, 'and')
            ->orderBy('fechaMovimiento', 'desc')->paginate($cant);
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return RegistroProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function getByIngreso($month)
    {
        return RegistroProducto::query()->join('IngresoProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro', 'inner', false)
            ->select('RegistroProducto.*')
            ->whereYear('IngresoProducto.fechaIngreso', '=', now()->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month, 'and')
            ->orderBy('IngresoProducto.fechaIngreso', 'desc')
            ->get();
    }

    public function searchByEgreso($serial, $cant, $excludeArray = [])
    {
        // 1. Obtener 1 registro por cada producto distinto (Diversidad)
        $query1 = RegistroProducto::selectRaw('MIN(RegistroProducto.idRegistro) as idRegistro')
            ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->where('RegistroProducto.estado', '!=', 'ENTREGADO')
            ->where('RegistroProducto.estado', '!=', 'INVALIDO')
            ->where('RegistroProducto.estado', '!=', 'DIVIDIDO')
            ->where('RegistroProducto.estado', '!=', 'REUNIDO')
            ->where('RegistroProducto.numeroSerie', 'LIKE', "%{$serial}%");

        if (!empty($excludeArray)) {
            $query1->whereNotIn('RegistroProducto.idRegistro', $excludeArray);
        }

        $diverseIds = $query1->groupBy('DetalleComprobante.idProducto')
            ->take($cant)
            ->pluck('idRegistro')
            ->toArray();

        $finalResults = collect();

        if (!empty($diverseIds)) {
            $diverseRecords = RegistroProducto::whereIn('idRegistro', $diverseIds)->get();
            foreach ($diverseRecords as $rec) {
                $finalResults->push($rec);
            }
        }

        // 2. Rellenar los cupos faltantes SOLO si la búsqueda fue muy específica (coincidió con 1 solo producto)
        if (count($diverseIds) === 1) {
            $faltan = $cant - count($diverseIds);
            
            if ($faltan > 0) {
                $allExcluded = array_merge($excludeArray, $diverseIds);
                
                $query2 = RegistroProducto::where('estado', '!=', 'ENTREGADO')
                    ->where('estado', '!=', 'INVALIDO')
                    ->where('estado', '!=', 'DIVIDIDO')
                    ->where('estado', '!=', 'REUNIDO')
                    ->where('numeroSerie', 'LIKE', "%{$serial}%")
                    ->whereNotIn('idRegistro', $allExcluded)
                    ->take($faltan);
                    
                $fillRecords = $query2->get();
                foreach ($fillRecords as $rec) {
                    $finalResults->push($rec);
                }
            }
        }

        // Ordenar alfabéticamente por numeroSerie para agrupar visualmente los productos
        return $finalResults->sortBy('numeroSerie')->values();
    }

    public function getByEgreso($serial)
    {
        return RegistroProducto::where('estado', '!=', 'ENTREGADO', 'and')
            ->where('estado', '!=', 'INVALIDO', 'and')
            ->where('estado', '!=', 'DIVIDIDO', 'and')
            ->where('estado', '!=', 'REUNIDO', 'and')
            ->where('numeroSerie', '=', $serial, 'and')
            ->first();
    }

    public function searchByGarantia($serial, $cant)
    {
        return RegistroProducto::where('estado', '=', 'ENTREGADO')
            ->where('numeroSerie', 'LIKE', "%{$serial}%")
            ->take($cant)
            ->get();
    }

    public function getByGarantia($serial)
    {
        return RegistroProducto::where('estado', '=', 'ENTREGADO')
            ->where('numeroSerie', '=', $serial)
            ->first();
    }

    public function validateSerie($idProveedor, $serie)
    {
        $response = RegistroProducto::join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante', 'inner', false)
            ->join('Comprobante', 'DetalleComprobante.idComprobante', '=', 'Comprobante.idComprobante')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO', 'and')
            ->where('Comprobante.idProveedor', '=', $idProveedor, 'and')
            ->where('RegistroProducto.numeroSerie', '=', $serie)->first();
        return $response;
    }

    public function getSerialsByProduct($idProduct, $idAlmacen = null)
    {
        return RegistroProducto::join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante', 'inner', false)
            ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO')
            ->where('RegistroProducto.estado', '<>', 'ENTREGADO')
            ->where('RegistroProducto.estado', '<>', 'GARANTIA')
            ->where('RegistroProducto.estado', '<>', 'DIVIDIDO')
            ->when($idAlmacen, function ($query) use ($idAlmacen) { // Filtro dinámico
                $query->where('RegistroProducto.idAlmacen', $idAlmacen);
            })
            ->where('Producto.idProducto', $idProduct)
            ->get();
    }

    public function create(array $data)
    {
        return RegistroProducto::create($data);
    }

    public function update($id, array $data)
    {
        $reg = RegistroProducto::findOrFail($id);
        $reg->update($data);
        return $reg;
    }

    public function getLast()
    {
        return RegistroProducto::orderBy('idRegistro', 'desc')->first();
    }

    private function validateColumns($column)
    {
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
