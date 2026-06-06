<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\IngresoProducto;

class IngresoProductoRepository implements IngresoProductoRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new IngresoProducto())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::query()->where($column, '=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::query()->where($column, '=', $data)->get();
    }

    public function getAllByComprobante($idComprobante)
    {
        return IngresoProducto::query()->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro', 'inner', false)
            ->join('DetalleComprobante', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante', 'inner', false)
            ->join('Comprobante', 'Comprobante.idComprobante', '=', 'DetalleComprobante.idComprobante', 'inner', false)
            ->where('Comprobante.idComprobante', '=', $idComprobante, 'and')->select('IngresoProducto.*')->get();
    }

    public function getAllByMonth($month, $cant, $querys)
    {
        $query = IngresoProducto::query();
        $query->select('IngresoProducto.*');

        $query->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro', 'inner', false)
            ->join('DetalleComprobante', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante', 'inner', false)
            ->join('Comprobante', 'Comprobante.idComprobante', '=', 'DetalleComprobante.idComprobante', 'inner', false);

        $query->whereYear('IngresoProducto.fechaIngreso', '=', $month->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month->month, 'and');

        if (isset($querys)) {
            if (isset($querys['usuario'])) {
                $query->where('IngresoProducto.idUser', '=', $querys['usuario']);
            }

            if (isset($querys['proveedor'])) {
                $query->where('Comprobante.idProveedor', '=', $querys['proveedor']);
            }

            if (isset($querys['almacen'])) {
                $query->where('RegistroProducto.idAlmacen', '=', $querys['almacen']);
            }

            if (isset($querys['estado'])) {
                $query->where('RegistroProducto.estado', '=', $querys['estado']);
            }
        }

        return $query->with('RegistroProducto.DetalleComprobante.Producto.packHijos.ProductoHijo')
            ->orderBy('IngresoProducto.fechaIngreso', 'desc')->paginate($cant);
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return IngresoProducto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function getOneBySerialNumber($data)
    {
        return IngresoProducto::query()->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro', 'inner', false)
            ->where('RegistroProducto.estado', '<>', 'ENTREGADO', 'and')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO', 'and')
            ->where('RegistroProducto.numeroSerie', '=', $data, 'and')
            ->first();
    }

    public function searchBySerialNumber($data, $cant)
    {
        return IngresoProducto::query()->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro', 'inner', false)
            ->where('RegistroProducto.estado', '<>', 'ENTREGADO', 'and')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO', 'and')
            ->where('RegistroProducto.numeroSerie', 'LIKE', '%' . $data . '%', 'and')
            ->take($cant)
            ->get();
    }

    public function getUsersByMonth($month)
    {
        return IngresoProducto::select('IngresoProducto.idUser')->distinct()
            ->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro')
            ->whereYear('IngresoProducto.fechaIngreso', '=', $month->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month->month, 'and')
            ->get();
    }

    public function getProveedoresByMonth($month)
    {
        return IngresoProducto::select('Comprobante.idProveedor')->distinct()
            ->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro')
            ->join('DetalleComprobante', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->join('Comprobante', 'Comprobante.idComprobante', '=', 'DetalleComprobante.idComprobante')
            ->whereYear('IngresoProducto.fechaIngreso', '=', $month->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month->month, 'and')
            ->get();
    }

    public function getAlmacenesByMonth($month)
    {
        return IngresoProducto::select('RegistroProducto.idAlmacen')->distinct()
            ->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro')
            ->whereYear('IngresoProducto.fechaIngreso', '=', $month->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month->month, 'and')
            ->get();
    }

    public function getEstadosByMonth($month)
    {
        return IngresoProducto::select('RegistroProducto.estado')->distinct()
            ->join('RegistroProducto', 'RegistroProducto.idRegistro', '=', 'IngresoProducto.idRegistro')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO')
            ->whereYear('IngresoProducto.fechaIngreso', '=', $month->year, 'and')
            ->whereMonth('IngresoProducto.fechaIngreso', '=', $month->month, 'and')
            ->get();
    }

    public function create(array $data)
    {
        return IngresoProducto::create($data);
    }


    public function update($id, array $data)
    {
        $ingreso = IngresoProducto::findOrFail($id);
        $ingreso->update($data);
        return $ingreso;
    }

    public function getLast()
    {
        return IngresoProducto::orderBy('idIngreso', 'desc')->first();
    }

    private function validateColumns($column)
    {
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
