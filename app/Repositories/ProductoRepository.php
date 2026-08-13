<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Catalogo\Producto;
use App\Models\Catalogo\MarcaProducto;

class ProductoRepository implements ProductoRepositoryInterface
{
    /**
     * Columnas válidas para búsquedas.
     * ANTES: Se usaba getFillable() que mezcla columnas con columnas de búsqueda.
     * AHORA: Lista explícita de columnas realmente usables en filtros/búsquedas.
     */
    protected $searchableColumns = [
        'idProducto',
        'idMarca',
        'idGrupo',
        'nombreProducto',
        'codigoProducto',
        'UPC',
        'partNumber',
        'modelo',
        'estadoProductoWeb',
        'slugProducto',
    ];

    //Devuelve todos los productos
    public function all()
    {
        return Producto::all();
    }

    //Devuelve un producto por columna y dato
    public function getOne($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->where($column, '=', $data)->first();
    }

    //Devuelve el producto mas recientemente creado (Id mas alto)
    public function getLast()
    {
        return Producto::select('idProducto')->orderBy('idProducto', 'desc')->first();
    }

    //Devuelve todos los productos por columna y dato
    public function getAllByColumn($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->where($column, '=', $data)->get();
    }

    /**
     * ANTES: paginateAllByColumn($column, $data, $cant, $querys)
     * AHORA: paginateAllByColumn($column, $data, $perPage, $filtros)
     * 
     * CAMBIO: $cant → $perPage, $querys → $filtros para consistencia semántica.
     * EXTRACCIÓN: Filtros delegados a applyFilters().
     */
    public function paginateAllByColumn($column, $data, $perPage, $filtros)
    {
        $this->validateColumn($column);

        $query = Producto::query();
        $query->where($column, '=', $data);

        $this->applyFilters($query, $filtros);

        return $query->paginate($perPage);
    }

    /**
     * ANTES: searchPaginateList($column, $cont, $data, $filtros = null)
     * AHORA: searchPaginateList($column, $perPage, $data, $filtros = null)
     * 
     * CAMBIO: $cont → $perPage. Orden ajustado: $perPage antes de $data.
     * EXTRACCIÓN: Filtros delegados a applyFilters().
     */
    public function searchPaginateList($column, $perPage, $data, $filtros = null)
    {
        $this->validateColumn($column);

        // Si la columna es nombreProducto, usamos la lógica de búsqueda intensiva/flexible
        if ($column === 'nombreProducto') {
            return $this->searchIntensiveProducts($data, $perPage, $filtros);
        }

        $query = Producto::query()->where($column, 'LIKE', '%' . $data . '%');

        $this->applyFilters($query, $filtros);

        return $query->paginate($perPage);
    }

    //Devuelve el primer producto donde la columna contiene el termino de la busqueda.
    public function searchOne($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->where($column, 'LIKE', '%' . $data . '%')->first();
    }

    //Devuelve todos los productos donde la columna contiene el termino de la busqueda.
    public function searchList($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->where($column, 'LIKE', '%' . $data . '%')->get();
    }

    //Devuelve los primeros 'cont' productos donde la columna contiene el termino de la busqueda.
    public function searchTakeList($column, $data, $cont)
    {
        $this->validateColumn($column);
        return Producto::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->take($cont)->get();
    }

    //Devuelve IDs de marcas distintas para productos que coinciden con un filtro de columna y dato
    public function getMarcasByColumn($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->select('idMarca')->distinct()
            ->where($column, '=', $data, 'and')->get();
    }

    //Devuelve marcas asociadas a productos que coinciden con un termino de busqueda
    public function getMarcasBySearchTerm($query)
    {
        return MarcaProducto::query()->whereIn('idMarca', function ($subquery) use ($query) {
            $subquery->select('idMarca')
                ->from('Producto')
                ->where('nombreProducto', 'LIKE', '%' . $query . '%', 'and')
                ->orWhere('modelo', 'LIKE', '%' . $query . '%', 'and')
                ->orWhere('codigoProducto', 'LIKE', '%' . $query . '%', 'and')
                ->orWhere('partNumber', 'LIKE', '%' . $query . '%', 'and');
        }, 'and', false)->get()->sortBy('nombreMarca');
    }

    //Devuelve estados distintos para productos que coinciden con un filtro de columna y dato
    public function getEstadosByColumn($column, $data)
    {
        $this->validateColumn($column);
        return Producto::query()->select('estadoProductoWeb')->distinct()
            ->where($column, '=', $data, 'and')->get();
    }

    public function getProductsCodes()
    {
        return Producto::select(['idGrupo', DB::raw('MAX(codigoProducto) as codigoProducto')])
            ->groupBy('idGrupo')->get();
    }

    //Devuelve el total de productos disponibles
    public function total()
    {
        return Producto::query()->where('estadoProductoWeb', '=', 'DISPONIBLE', 'and')->count();
    }


    public function getStockMinProducts()
    {
        return Producto::query()
            ->where('estadoProductoWeb', '=', 'DISPONIBLE')
            ->select('Producto.*')

            // 1. Calculamos el stock TOTAL sumando absolutamente todos los almacenes
            ->selectRaw("COALESCE((SELECT SUM(stock) FROM Inventario WHERE Inventario.idProducto = Producto.idProducto), 0) as stock_total")

            // 2. Filtramos: Que el stock total sea menor o igual al Stock Mínimo del producto, 
            // y que sea mayor a 0 (para que los que están en 0 se vayan a una lista de "Agotados").
            ->havingRaw('stock_total <= Producto.stockMin AND stock_total > 0')

            // 3. Ordenamos para ver primero los que tienen menos stock
            ->orderBy('stock_total', 'asc')

            ->paginate(50);
    }


    public function getProductsWithStock()
    {
        return Producto::query()->where('estadoProductoWeb', '=', 'DISPONIBLE', 'and')
            ->whereHas('Inventario', function ($query) {
                $query->where('stock', '>', 0, 'and');
            }, '>=', 1)
            ->with(['Inventario' => function ($query) {
                $query->where('stock', '>', 0, 'and');
            }])
            ->paginate(50);
    }

    public function getMostSoldProducts($limit = 5)
    {
        return Producto::query()->join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto', 'inner', false)
            ->join('RegistroProducto', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante', 'inner', false)
            ->join('EgresoProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro', 'inner', false)
            ->select('Producto.*', DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('devoluciones')
                    ->whereRaw('devoluciones.idEgreso = EgresoProducto.idEgreso');
            })
            ->groupBy('Producto.idProducto')
            ->orderBy('total_ventas', 'desc')
            ->take($limit)
            ->get();
    }


    //Valida si un producto tiene un numero de serie registrado
    public function validateSerial($id, $serial)
    {
        return Producto::join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto', 'inner', false)
            ->join('RegistroProducto', 'DetalleComprobante.idDetalleComprobante', '=', 'RegistroProducto.idDetalleComprobante')
            ->where('RegistroProducto.estado', '<>', 'INVALIDO')
            ->where('Producto.idProducto', '=', $id)
            ->where('RegistroProducto.numeroSerie', '=', $serial)
            ->first();
    }

    /**
     * $query → $searchTerm (evita confusión con query builder),
     * $cant → $perPage. EXTRACCIÓN: Filtros delegados a applyFilters().
     */
    public function searchIntensiveProducts($searchTerm, $perPage, $filtros)
    {
        $query = Producto::query()
            ->leftJoin('MarcaProducto', 'Producto.idMarca', '=', 'MarcaProducto.idMarca')
            ->select('Producto.*');

        // Limpiar el término y separar por espacios
        $words = array_filter(explode(' ', trim($searchTerm)));

        if (empty($words)) {
            $this->applyFilters($query, $filtros);
            return $query->paginate($perPage);
        }

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $q->where(function ($sq) use ($word) {
                    $sq->where('Producto.nombreProducto', 'LIKE', '%' . $word . '%')
                        ->orWhere('Producto.modelo', 'LIKE', '%' . $word . '%')
                        ->orWhere('Producto.codigoProducto', 'LIKE', '%' . $word . '%')
                        ->orWhere('Producto.partNumber', 'LIKE', '%' . $word . '%')
                        ->orWhere('Producto.UPC', 'LIKE', '%' . $word . '%')
                        ->orWhere('MarcaProducto.nombreMarca', 'LIKE', '%' . $word . '%');
                });
            }
        });

        $this->applyFilters($query, $filtros);

        return $query->paginate($perPage);
    }

    public function getEmptyPagination($perPage = 10)
    {
        return new LengthAwarePaginator(
            new Collection(),
            0,
            $perPage,
            1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    // Verifica si ya existe un producto con el mismo modelo (case-insensitive)
    // $excludeId permite excluir un producto al editar (para que no se compare consigo mismo)
    public function existsByModelo(string $modelo, $excludeId = null): bool
    {
        if (empty($modelo)) {
            return false;
        }

        $query = Producto::query()->whereRaw('LOWER(modelo) = ?', [strtolower($modelo)], 'and');

        if ($excludeId) {
            $query->where('idProducto', '!=', $excludeId, 'and');
        }

        return $query->exists();
    }

    //Crea un nuevo producto
    public function create(array $productoData)
    {
        return Producto::create($productoData);
    }

    //Actualiza un producto
    public function update($idProducto, array $productoData)
    {
        $producto = Producto::findOrFail($idProducto);
        $producto->update($productoData);
        return $producto;
    }

    /**
     * Método privado centralizado para aplicar filtros de marca, estado y almacén.
     * ANTES: Esta lógica se repetía en paginateAllByColumn, searchPaginateList y
     * searchIntensiveProducts (3 duplicaciones).
     * Un solo punto de mantenimiento.
     */
    private function applyFilters($query, $filtros)
    {
        if (!isset($filtros)) {
            return;
        }

        if (isset($filtros['marca'])) {
            $query->where('Producto.idMarca', '=', $filtros['marca']);
        }

        if (isset($filtros['estado'])) {
            if ($filtros['estado'] === 'ACTIVO') {
                // Activos: productos con al menos 1 unidad en stock (cualquier almacén)
                $query->whereHas('Inventario', function ($q) {
                    $q->where('stock', '>', 0);
                });
            } elseif ($filtros['estado'] === 'INACTIVO') {
                // Inactivos: agotados o descontinuados
                $query->whereIn('Producto.estadoProductoWeb', ['AGOTADO', 'DESCONTINUADO']);
            } else {
                $query->where('Producto.estadoProductoWeb', '=', $filtros['estado']);
            }
        }

        if (isset($filtros['almacen'])) {
            $query->whereHas('Inventario', function ($q) use ($filtros) {
                $q->where('idAlmacen', $filtros['almacen'])
                    ->where('stock', '>', 0);

                if (isset($filtros['rack']) && $filtros['rack'] !== '') {
                    $q->where(function ($subQ) use ($filtros) {
                        $subQ->where('idUbicacionExacta', $filtros['rack'])
                            ->orWhereExists(function ($query) use ($filtros) {
                                $query->select(\DB::raw(1))
                                    ->from('RegistroProducto')
                                    ->join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalle')
                                    ->whereColumn('DetalleComprobante.idProducto', 'Inventario.idProducto')
                                    ->whereColumn('RegistroProducto.idAlmacen', 'Inventario.idAlmacen')
                                    ->whereNotIn('RegistroProducto.estado', ['ENTREGADO', 'INVALIDO'])
                                    ->where('RegistroProducto.ubicacion_especifica', $filtros['rack']);
                            });
                    });
                }
            });
        }
    }

    /**
     * CAMBIO: Son conceptos distintos. fillable ≠ columnas de búsqueda.
     */
    private function validateColumn($column)
    {
        if (!in_array($column, $this->searchableColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida para búsquedas.");
        }
    }
}
