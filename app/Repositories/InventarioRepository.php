<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Inventario;
use Exception;

class InventarioRepository implements InventarioRepositoryInterface
{
    /** @var array<string> */
    protected array $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Inventario())->getFillable();
    }

    public function all()
    {
        return Inventario::all();
    }

    public function getOne(string $column, mixed $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data, 'and')->first();
    }

    public function getAllByColumn(string $column, mixed $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data, 'and')->get();
    }

    public function getAllWhereFindStock()
    {
        return Inventario::where('stock', '>', 0, 'and')->get();
    }

    public function getAllByColumnWhereFindStock(string $column, mixed $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, '=', $data, 'and')->where('stock', '>', 0, 'and')->get();
    }

    public function searchOne(string $column, mixed $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList(string $column, mixed $data)
    {
        $this->validateColumns($column);
        return Inventario::where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function create(array $productoData)
    {
        return Inventario::create($productoData);
    }

    public function update(mixed $idProducto, array $data)
    {
        $inventarios = Inventario::where('idProducto', '=', $idProducto, 'and')->get();
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

    public function addStock(mixed $idProducto, mixed $idAlmacen)
    {
        try {
            $inventario = Inventario::where('idProducto', '=', $idProducto, 'and')
                ->where('idAlmacen', '=', $idAlmacen, 'and')
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

    public function removeStock(mixed $idProducto, mixed $idAlmacen)
    {
        try {
            $inventario = Inventario::where('idProducto', '=', $idProducto, 'and')
                ->where('idAlmacen', '=', $idAlmacen, 'and')
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

    private function validateColumns(string $column)
    {
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}
