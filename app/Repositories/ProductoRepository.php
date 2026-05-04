<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Producto;
use App\Models\MarcaProducto;

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
        return Producto::where($column, '=', $data)->first();
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
        return Producto::where($column, '=', $data)->get();
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

        $query = Producto::where($column, 'LIKE', '%' . $data . '%');

        $this->applyFilters($query, $filtros);

        return $query->paginate($perPage);
    }

    //Devuelve el primer producto donde la columna contiene el termino de la busqueda.
    public function searchOne($column, $data)
    {
        $this->validateColumn($column);
        return Producto::where($column, 'LIKE', '%' . $data . '%')->first();
    }

    //Devuelve todos los productos donde la columna contiene el termino de la busqueda.
    public function searchList($column, $data)
    {
        $this->validateColumn($column);
        return Producto::where($column, 'LIKE', '%' . $data . '%')->get();
    }

    //Devuelve los primeros 'cont' productos donde la columna contiene el termino de la busqueda.
    public function searchTakeList($column, $data, $cont)
    {
        $this->validateColumn($column);
        return Producto::where($column, 'LIKE', '%' . $data . '%')->take($cont)->get();
    }

    //Devuelve IDs de marcas distintas para productos que coinciden con un filtro de columna y dato
    public function getMarcasByColumn($column, $data)
    {
        $this->validateColumn($column);
        return Producto::select('idMarca')->distinct()
            ->where($column, '=', $data)->get();
    }

    //Devuelve marcas asociadas a productos que coinciden con un termino de busqueda
    public function getMarcasBySearchTerm($query)
    {
        return MarcaProducto::whereIn('idMarca', function ($subquery) use ($query) {
            $subquery->select('idMarca')
                ->from('Producto')
                ->where('nombreProducto', 'LIKE', '%' . $query . '%')
                ->orWhere('modelo', 'LIKE', '%' . $query . '%')
                ->orWhere('codigoProducto', 'LIKE', '%' . $query . '%')
                ->orWhere('partNumber', 'LIKE', '%' . $query . '%');
        })->get()->sortBy('nombreMarca');
    }

    //Devuelve estados distintos para productos que coinciden con un filtro de columna y dato
    public function getEstadosByColumn($column, $data)
    {
        $this->validateColumn($column);
        return Producto::select('estadoProductoWeb')->distinct()
            ->where($column, '=', $data)->get();
    }

    /**
     * ELIMINADO: getCodes() era un duplicado exacto de getProductsCodes().
     * Se mantiene solo getProductsCodes(). El servicio que usaba getCodes()
     * ahora apunta a getProductsCodes().
     */
    public function getProductsCodes()
    {
        return Producto::select('idGrupo', DB::raw('MAX(codigoProducto) as codigoProducto'))
            ->groupBy('idGrupo')->get();
    }

    //Devuelve el total de productos disponibles
    public function total()
    {
        return Producto::where('estadoProductoWeb', '=', 'DISPONIBLE')->count();
    }

    /**
     * Usaba raw SQL con JOIN y GROUP BY.
     * AHORA: Se convierte a Query Builder con whereHas.
     * BENEFICIO: Más legible, mantenible, y aprovecha el query builder de Laravel.
     */
    public function getStockMinProducts()
    {
        return Producto::where('estadoProductoWeb', 'DISPONIBLE')
            ->whereHas('Inventario', function ($query) {
                $query->select(DB::raw('SUM(stock)'))
                    ->from('Inventario')
                    ->whereColumn('Inventario.idProducto', 'Producto.idProducto')
                    ->havingRaw('SUM(stock) > 0')
                    ->havingRaw('SUM(stock) < Producto.stockMin');
            })
            ->paginate(50);
    }

    /**
     * Ejecutaba raw SQL, luego hacía Producto::find() por cada resultado (problema N+1).
     * AHORA: whereHas con eager loading de Inventario.
     * BENEFICIO: De 101 queries se reduce a 2 queries.
     */
    public function getProductsWithStock()
    {
        return Producto::where('estadoProductoWeb', 'DISPONIBLE')
            ->whereHas('Inventario', function ($query) {
                $query->where('stock', '>', 0);
            })
            ->with('Inventario')
            ->orderBy('codigoProducto')
            ->get();
    }

    public function getMostStockProducts($limit = 5)
    {
        return Producto::where('estadoProductoWeb', 'DISPONIBLE')
            ->withSum('Inventario as total_stock', 'stock')
            ->orderByDesc('total_stock')
            ->take($limit)
            ->get();
    }

    //Valida si un producto tiene un numero de serie registrado
    public function validateSerial($id, $serial)
    {
        return Producto::join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
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
        $query = Producto::query();

        $query->where(function ($q) use ($searchTerm) {
            $q->where('codigoProducto', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('partNumber', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('modelo', 'LIKE', '%' . $searchTerm . '%');
        });

        $this->applyFilters($query, $filtros);

        return $query->paginate($perPage);
    }

    /**
     * getPaginationNull() usaba whereRaw('1=0') — hack confuso.
     * getEmptyPagination() crea un LengthAwarePaginator vacío explícitamente.
     * Sin queries innecesarios, intención clara.
     */
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
            $query->where('idMarca', '=', $filtros['marca']);
        }

        if (isset($filtros['estado'])) {
            $query->where('estadoProductoWeb', '=', $filtros['estado']);
        }

        if (isset($filtros['almacen'])) {
            $query->whereHas('Inventario', function ($q) use ($filtros) {
                $q->where('idAlmacen', $filtros['almacen'])
                    ->where('stock', '>', 0);
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
