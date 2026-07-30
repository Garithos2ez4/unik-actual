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
                    $oldStock = $inventario->stock;
                    $array = array();
                    $array['idAlmacen'] = $almacen;
                    $array['stock'] = $stock;

                    $inventario->update($array);

                    // Sincronizar series genéricas (RegistroProducto)
                    if ($stock > $oldStock) {
                        $diff = $stock - $oldStock;
                        $detalle = \App\Models\DetalleComprobante::where('idProducto', $idProducto)->latest('idDetalleComprobante')->first();
                        if ($detalle) {
                            $lastRegistro = \App\Models\RegistroProducto::orderBy('idRegistro', 'desc')->first();
                            $nextIdRegistro = $lastRegistro ? $lastRegistro->idRegistro + 1 : 1;

                            for ($i = 0; $i < $diff; $i++) {
                                \App\Models\RegistroProducto::create([
                                    'idRegistro' => $nextIdRegistro++,
                                    'idDetalleComprobante' => $detalle->idDetalleComprobante,
                                    'idAlmacen' => $almacen,
                                    'numeroSerie' => 'nulo',
                                    'estado' => 'NUEVO',
                                    'fechaMovimiento' => now(),
                                ]);
                            }
                        }
                    } elseif ($stock < $oldStock) {
                        $diff = $oldStock - $stock;
                        // Eliminar series genéricas sobrantes
                        $seriesToDelete = \App\Models\RegistroProducto::whereHas('DetalleComprobante', function ($q) use ($idProducto) {
                            $q->where('idProducto', $idProducto);
                        })
                            ->where('idAlmacen', $almacen)
                            ->whereIn('estado', ['NUEVO', 'ABIERTO'])
                            ->where(function ($q) {
                                $q->whereNull('numeroSerie')
                                  ->orWhere('numeroSerie', 'nulo')
                                  ->orWhere('numeroSerie', 'N/A')
                                  ->orWhere('numeroSerie', '');
                            })
                            ->limit($diff)
                            ->get();

                        foreach ($seriesToDelete as $s) {
                            $s->delete();
                        }
                    }
                }
            }
        }

        return $inventarios;
    }

    public function updateUbicacion(mixed $idProducto, array $data, $filaEstanteArray = null)
    {
        $inventarios = Inventario::where('idProducto', '=', $idProducto, 'and')->get();
        foreach ($inventarios as $inventario) {
            foreach ($data as $almacen => $idUbicacionExacta) {
                if ($inventario->idAlmacen == $almacen) {
                    $inventario->update(['idUbicacionExacta' => $idUbicacionExacta ?: null]);

                    // Asignar ubicacion_especifica a series sin asignar
                    $registrosIds = \App\Models\RegistroProducto::whereHas('DetalleComprobante', function($q) use ($idProducto) {
                            $q->where('idProducto', $idProducto);
                        })
                        ->where('idAlmacen', $almacen)
                        ->whereNotIn('estado', ['ENTREGADO', 'INVALIDO'])
                        ->whereNull('ubicacion_especifica')
                        ->pluck('idRegistro');

                    if ($registrosIds->isNotEmpty()) {
                        \App\Models\RegistroProducto::whereIn('idRegistro', $registrosIds)
                            ->update(['ubicacion_especifica' => $idUbicacionExacta ?: null]);
                    }
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
