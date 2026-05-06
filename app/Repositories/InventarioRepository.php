<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Inventario;
use Exception;

class InventarioRepository implements InventarioRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Inventario())->getFillable();
    }

    public function all()
    {
        return Inventario::all();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data)->get();
    }

    public function getAllWhereFindStock()
    {
        return Inventario::where('stock', '>', 0)->get();
    }

    public function getAllByColumnWhereFindStock($column, $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data)->where('stock', '>', 0)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    public function create(array $productoData)
    {
        return Inventario::create($productoData);
    }

    public function update($idProducto, array $data)
    {
        $inventarios = Inventario::where('idProducto', '=', $idProducto)->get();
        foreach ($inventarios as $inventario) {
            foreach ($data as $almacen => $stock) {
                if ($inventario->idAlmacen == $almacen) {
                    $array = array();
                    $array['idAlmacen'] = $almacen;
                    $array['stock'] = $stock;

                    $inventario->update($array);
                }
            }
        }

        return $inventarios;
    }

    public function addStock($idProducto, $idAlmacen)
    {
        try {
            $inventario = Inventario::where('idProducto', $idProducto)
                ->where('idAlmacen', $idAlmacen)
                ->first();

            if (!$inventario) {
                // Crear registro si no existe
                $inventario = Inventario::create([
                    'idProducto' => $idProducto,
                    'idAlmacen' => $idAlmacen,
                    'stock' => 0
                ]);
            }

            $inventario->stock++;
            $inventario->save();
        } catch (Exception $e) {
            throw new Exception('Error en la operación: ' . $e->getMessage());
        }
    }

    public function removeStock($idProducto, $idAlmacen)
    {
        try {
            $inventario = Inventario::where('idProducto', '=', $idProducto)
                ->where('idAlmacen', '=', $idAlmacen)
                ->first();

            if ($inventario) {
                if ($inventario->stock > 0) {
                    $inventario->stock--;
                    $inventario->save();
                } else {
                    // Evitar que baje de 0
                    $inventario->stock = 0;
                    $inventario->save();
                }
            } else {
                // Manejo si no se encuentra el inventario
                throw new Exception('Inventario no encontrado.');
            }
        } catch (Exception $e) {
            throw new Exception('Error en la operacion.');
        }
    }

    private function validateColumns($column)
    {
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
