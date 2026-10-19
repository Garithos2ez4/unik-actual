<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Deltron\DeltronApiService;
use App\Services\HeaderServiceInterface;
use App\Models\Inventario\InventarioProveedorDetalle;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Catalogo\Producto;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class DeltronController extends Controller
{
    protected $headerService;
    protected $deltronService;

    public function __construct(HeaderServiceInterface $headerService, DeltronApiService $deltronService)
    {
        $this->headerService = $headerService;
        $this->deltronService = $deltronService;
    }

    /**
     * Muestra la vista de ofertas/stock de Deltron alimentada por Inventario_Proveedor.
     */
    public function indexStock(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $keyword = trim($request->input('q', ''));
        $page = (int) $request->input('page', 1);
        $perPage = 30;

        $productos = [];
        $alertaApi = null;
        $clientIp = Cache::get('deltron_api_client_ip', '132.251.193.20');

        try {
            $clientIp = $this->deltronService->getClientIp();
        } catch (Exception $e) {
            $alertaApi = $e->getMessage();
            if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $e->getMessage(), $matches)) {
                $clientIp = $matches[1];
            }
        }

        // Siempre consultar la base de datos local (Inventario_Proveedor) con su SKU oficial de proveedor
        $query = Inventario_Proveedor::where('Inventario_Proveedor.idProveedor', 1)
            ->join('Producto', 'Inventario_Proveedor.idProducto', '=', 'Producto.idProducto')
            ->leftJoin('inventario_proveedor_skus', function ($join) {
                $join->on('Inventario_Proveedor.idInventarioProveedor', '=', 'inventario_proveedor_skus.idInventarioProveedor')
                     ->where('inventario_proveedor_skus.idProveedor', '=', 1);
            })
            ->where('Inventario_Proveedor.stock', '>', 0);

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('Producto.nombreProducto', 'LIKE', "%{$keyword}%")
                  ->orWhere('Producto.partNumber', 'LIKE', "%{$keyword}%")
                  ->orWhere('Producto.codigoProducto', 'LIKE', "%{$keyword}%")
                  ->orWhere('Producto.modelo', 'LIKE', "%{$keyword}%")
                  ->orWhere('inventario_proveedor_skus.sku_proveedor', 'LIKE', "%{$keyword}%")
                  ->orWhere('inventario_proveedor_skus.mini_codigo', 'LIKE', "%{$keyword}%");
            });
        }

        $totalRecords = $query->count();
        $itemsDb = $query->orderBy('Inventario_Proveedor.stock', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->select(
                'Producto.idProducto as idProducto',
                'Producto.nombreProducto as titulo',
                'Producto.modelo as modelo',
                'Producto.partNumber as part_number',
                'Producto.codigoProducto as codigo_unike',
                'Producto.precioDolar as precio',
                'Inventario_Proveedor.stock as stock',
                'inventario_proveedor_skus.sku_proveedor as sku_deltron',
                'inventario_proveedor_skus.mini_codigo as mini_codigo_deltron'
            )
            ->get();

        foreach ($itemsDb as $item) {
            $productos[] = [
                'idProducto' => $item->idProducto,
                'titulo' => $item->titulo,
                'modelo' => $item->modelo ?: '-',
                'sku_deltron' => $item->sku_deltron,
                'mini_codigo_deltron' => $item->mini_codigo_deltron,
                'part_number' => $item->part_number,
                'codigo_unike' => $item->codigo_unike,
                'precio' => $item->precio,
                'stock' => $item->stock
            ];
        }

        // Historial de sincronizaciones recientes
        $historial = InventarioProveedorDetalle::orderBy('fecha_ejecucion', 'desc')->take(11)->get();
        $totalStockProveedor = Inventario_Proveedor::where('idProveedor', 1)->where('stock', '>', 0)->count();

        // Obtener promociones oficiales de Deltron desde la base de datos local (evita llamadas a la API en visitas web)
        $promociones = \App\Models\Inventario\DeltronPromocion::all()->map(function ($p) {
            return [
                'promocion_id' => $p->promocion_id,
                'promocion_descripcion' => $p->promocion_descripcion,
                'sku' => $p->sku,
                'sku_descripcion' => $p->sku_descripcion,
                'precio_promocion' => $p->precio_promocion ? (float) $p->precio_promocion : null,
                'fecha_fin' => $p->fecha_fin ? $p->fecha_fin->format('d/m/Y') : null,
                'stock_total_promo' => (int) $p->stock_total_promo,
                'detalle_cargado' => (bool) $p->detalle_cargado,
                'items_count' => is_array($p->items_combo_json) ? count($p->items_combo_json) : 0
            ];
        })->toArray();

        if (empty($promociones)) {
            try {
                $promociones = $this->deltronService->syncPromociones();
            } catch (\Exception $e) {
                // Silencioso
            }
        }

        return view('compras.deltron_stock', [
            'user' => $userModel,
            'productos' => $productos,
            'keyword' => $keyword,
            'page' => $page,
            'historial' => $historial,
            'alertaApi' => $alertaApi,
            'clientIp' => $clientIp,
            'totalStockProveedor' => $totalStockProveedor,
            'promociones' => is_array($promociones) ? $promociones : []
        ]);
    }

    /**
     * Dispara la sincronización manual vía Artisan command (deltron:sync).
     */
    public function syncManual(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(360);
        @ignore_user_abort(true);

        $tipo = $request->input('tipo', 'inicio-dia');
        if (!in_array($tipo, ['inicio-dia', 'catalogo'])) {
            $tipo = 'inicio-dia';
        }

        try {
            $exitCode = Artisan::call('deltron:sync', [
                '--tipo' => $tipo
            ]);
            $output = Artisan::output();

            $modoTexto = $tipo === 'catalogo' ? 'Catálogo Completo (con SKUs y Mini Códigos)' : 'Corte de Inicio de Día';

            if ($exitCode === 0) {
                return redirect()->route('compras.deltron')->with('success', "Sincronización oficial con Deltron ({$modoTexto}) completada exitosamente.");
            } else {
                return redirect()->route('compras.deltron')->with('error', 'Aviso al sincronizar con Deltron: ' . trim($output));
            }
        } catch (Exception $e) {
            return redirect()->route('compras.deltron')->with('error', 'Error al sincronizar con Deltron API: ' . $e->getMessage());
        }
    }

    /**
     * Consulta un SKU o Part Number en tiempo real mediante Deltron API (JSON).
     */
    public function consultarTiempoReal(Request $request)
    {
        $sku = trim($request->input('sku', ''));
        $partNumber = trim($request->input('part_number', ''));

        $query = $sku ?: $partNumber;
        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parámetro sku o part_number requerido.'
            ], 400);
        }

        try {
            $stock = $this->deltronService->getStock($query);
            if ((!$stock || ($stock['stock_total'] ?? 0) === 0) && !empty($partNumber) && $partNumber !== $query) {
                $stockAlt = $this->deltronService->getStock($partNumber);
                if ($stockAlt) {
                    $stock = $stockAlt;
                }
            }

            $precio = $this->deltronService->getPrecio($query);
            if (!$precio && !empty($partNumber) && $partNumber !== $query) {
                $precio = $this->deltronService->getPrecio($partNumber);
            }

            $detalle = $this->deltronService->getProductoPorSku($query);
            if (!$detalle && !empty($partNumber) && $partNumber !== $query) {
                $detalle = $this->deltronService->getProductoPorSku($partNumber);
            }

            // Guardar inmediatamente en la tabla Inventario_Proveedor de la base de datos
            $productoDb = null;
            if (!empty($partNumber) && $partNumber !== '-') {
                $productoDb = Producto::where('partNumber', $partNumber)->first();
            }
            if (!$productoDb && !empty($query)) {
                $productoDb = Producto::where('modelo', $query)
                    ->orWhere('codigoProducto', $query)
                    ->orWhere('partNumber', $query)
                    ->first();
            }

            $guardadoEnBd = false;
            if ($productoDb) {
                if ($stock && isset($stock['stock_total'])) {
                    $invProveedor = Inventario_Proveedor::updateOrCreate(
                        [
                            'idProducto' => $productoDb->idProducto,
                            'idProveedor' => 1
                        ],
                        [
                            'stock' => (int) $stock['stock_total'],
                            'estado' => 1
                        ]
                    );
                    \App\Models\Catalogo\DetalleProducto::where('idProducto', $productoDb->idProducto)->update(['updated_at' => now()]);

                    if (!empty($query)) {
                        \App\Models\Inventario\InventarioProveedorSku::updateOrCreate(
                            [
                                'idInventarioProveedor' => $invProveedor->idInventarioProveedor,
                                'idProveedor' => 1
                            ],
                            [
                                'sku_proveedor' => strtoupper(trim($query)),
                                'part_number' => $partNumber,
                            ]
                        );
                    }

                    $guardadoEnBd = true;
                }

                // NO modificamos precioDolar de Producto para preservar intactos los precios publicados en la tienda web
            }

            return response()->json([
                'status' => 'ok',
                'sku' => $query,
                'stock' => $stock,
                'precio' => $precio,
                'detalle' => $detalle,
                'guardado_en_bd' => $guardadoEnBd
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta el detalle de una promoción específica (API-0000025).
     * Reutiliza los datos almacenados en la base de datos local para no agotar el límite de 30 llamadas/hora.
     */
    public function consultarPromocionDetalle(Request $request, $promo_id)
    {
        $promoId = urldecode(trim($promo_id));
        $force = $request->boolean('force', false);

        if (empty($promoId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Identificador de promoción no proporcionado.'
            ], 400);
        }

        try {
            $detalle = $this->deltronService->getOrFetchPromocionDetalle($promoId, $force);

            if (!$detalle) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No se pudo obtener información detallada para la promoción {$promoId}."
                ], 404);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $detalle
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar promoción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta el precio de lista vigente y precio de promoción puntual de un producto (API-0000030).
     * Límite oficial Deltron: 60 llamadas por hora. Uso recomendado: bajo demanda antes de cotizar o confirmar venta.
     */
    public function consultarPrecioTiempoReal(Request $request)
    {
        $sku = trim($request->input('sku', ''));
        $partNumber = trim($request->input('part_number', ''));
        $query = $sku ?: $partNumber;

        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parámetro sku o part_number requerido.'
            ], 400);
        }

        try {
            $precio = $this->deltronService->getPrecio($query);
            if (!$precio && !empty($partNumber) && $partNumber !== $query) {
                $precio = $this->deltronService->getPrecio($partNumber);
            }

            if (!$precio) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No se encontró precio vigente en Deltron para {$query}."
                ], 404);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $precio
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar precio en Deltron: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta la ficha técnica completa de un producto por SKU Deltron (API-0000027).
     * Incluye especificaciones técnicas completas, imágenes de alta resolución,
     * desglose de stock por los 13 almacenes, mercadería en tránsito y promociones.
     * Guarda la ficha localmente en disco/BD para cumplir con la recomendación oficial de Deltron
     * y no saturar el límite de 300 llamadas/día (60/hora).
     */
    public function consultarFichaSku(Request $request, $sku = null)
    {
        $skuQuery = trim($sku ?: $request->input('sku', ''));
        $partNumber = trim($request->input('part_number', ''));
        $force = $request->boolean('force', false);

        $query = $skuQuery ?: $partNumber;

        if (empty($query)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Parámetro sku o part_number requerido.'
            ], 400);
        }

        try {
            $ficha = $this->deltronService->getOrFetchFichaSku($query, $force);

            if (!$ficha && !empty($partNumber) && $partNumber !== $query) {
                $ficha = $this->deltronService->getOrFetchFichaSku($partNumber, $force);
            }

            if (!$ficha) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No se encontró ficha técnica oficial en Deltron para el SKU: {$query}."
                ], 404);
            }

            return response()->json([
                'status' => 'ok',
                'data' => $ficha
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar ficha técnica en Deltron: ' . $e->getMessage()
            ], 500);
        }
    }
}
