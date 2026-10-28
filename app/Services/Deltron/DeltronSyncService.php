<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\DetalleProducto;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\DeltronPromocion;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeltronSyncService
{
    protected ?DeltronApiService $apiService;
    protected int $idProveedor;

    public function __construct(?DeltronApiService $apiService = null, int $idProveedor = 1)
    {
        $this->apiService = $apiService;
        $this->idProveedor = $idProveedor;
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        return $this;
    }

    protected function getApiService(): DeltronApiService
    {
        if (!$this->apiService) {
            $this->apiService = app(DeltronApiService::class);
        }
        return $this->apiService;
    }

    /**
     * Busca un producto existente en el catalogo de Unike Store usando cascada de identificadores:
     * 1. Vinculacion previa en inventario_proveedor_skus (SKU Deltron)
     * 2. Part Number exacto y normalizado (sin guiones/espacios)
     * 3. Modelo Real extraido de specs_all_html (exacto y normalizado)
     * 4. Modelo exacto y normalizado
     * 5. SKU Deltron exacto y normalizado (contra modelo, partNumber o codigoProducto)
     */
    public function buscarProductoEnUnike(string $sku, ?string $partNumber = null, ?string $modeloReal = null): ?Producto
    {
        $cleanSku = strtoupper(trim($sku));

        // 1. Verificar vinculacion previa en InventarioProveedorSku
        if (!empty($cleanSku)) {
            $skuLink = InventarioProveedorSku::where('sku_proveedor', $cleanSku)->first();
            if ($skuLink) {
                $invProv = Inventario_Proveedor::find($skuLink->idInventarioProveedor);
                if ($invProv && $invProv->idProducto) {
                    $p = Producto::find($invProv->idProducto);
                    if ($p) return $p;
                }
            }
        }

        // 2. Modelo de Deltron exacto y normalizado (contra modelo y partNumber de Unike)
        if (!empty($modeloReal) && $modeloReal !== '-' && strtoupper($modeloReal) !== 'MODELO') {
            $exactMod = trim($modeloReal);
            $cleanMod = preg_replace('/[^A-Z0-9]/', '', strtoupper($exactMod));

            $p = Producto::where('modelo', $exactMod)->first();
            if ($p) return $p;

            if (!empty($cleanMod)) {
                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(modelo, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanMod])->first();
                if ($p) return $p;

                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanMod])->first();
                if ($p) return $p;
            }
        }

        // 3. Part Number exacto y normalizado
        if (!empty($partNumber) && $partNumber !== '-') {
            $exactPn = strtoupper(trim($partNumber));
            $cleanPn = preg_replace('/[^A-Z0-9]/', '', $exactPn);

            $p = Producto::where('partNumber', $exactPn)->first();
            if ($p) return $p;

            if (!empty($cleanPn)) {
                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanPn])->first();
                if ($p) return $p;

                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(modelo, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanPn])->first();
                if ($p) return $p;
            }
        }

        // 4. SKU Deltron contra campos principales
        if (!empty($cleanSku)) {
            $p = Producto::where('modelo', $cleanSku)
                ->orWhere('codigoProducto', $cleanSku)
                ->orWhere('partNumber', $cleanSku)
                ->first();
            if ($p) return $p;

            $cleanSkuNorm = preg_replace('/[^A-Z0-9]/', '', $cleanSku);
            if (!empty($cleanSkuNorm)) {
                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanSkuNorm])->first();
                if ($p) return $p;

                $p = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(modelo, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanSkuNorm])->first();
                if ($p) return $p;
            }
        }

        return null;
    }

    /**
     * Sincroniza la lista de promociones oficiales de Deltron en la base de datos local
     * (tabla deltron_promociones) y en snapshot de disco.
     * Preserva los detalles ya consultados y añade las promociones nuevas sin saturar la API.
     */
    public function syncPromociones(): array
    {
        try {
            $promos = $this->getApiService()->getPromociones();
            if (!empty($promos) && is_array($promos)) {
                $incomingIds = [];

                // Obtener promociones existentes en base de datos para no borrar detalles ya guardados
                $existentes = DeltronPromocion::all()->keyBy('promocion_id');

                foreach ($promos as $p) {
                    $promoId = trim($p['promocion_id'] ?? ($p['id'] ?? 'PROMO'));
                    if (empty($promoId)) continue;
                    $incomingIds[] = $promoId;

                    $desc = DeltronTextHelper::stripEmojis($p['promocion_descripcion'] ?? ($p['descripcion'] ?? ''));
                    $sku = $p['sku'] ?? null;
                    $skuDesc = DeltronTextHelper::stripEmojis($p['sku_descripcion'] ?? ($p['producto'] ?? null));

                    if (isset($existentes[$promoId])) {
                        // Si ya existe, actualizar descripcion o SKU si cambiaron
                        $existente = $existentes[$promoId];
                        $cambio = false;
                        if ($existente->promocion_descripcion !== $desc) {
                            $existente->promocion_descripcion = $desc;
                            $cambio = true;
                        }
                        if ($existente->sku !== $sku) {
                            $existente->sku = $sku;
                            $cambio = true;
                        }
                        if ($existente->sku_descripcion !== $skuDesc) {
                            $existente->sku_descripcion = $skuDesc;
                            $cambio = true;
                        }
                        if ($cambio) {
                            $existente->save();
                        }
                    } else {
                        // Nueva promocion detectada: insertar para posterior consulta de detalle
                        DeltronPromocion::create([
                            'promocion_id' => $promoId,
                            'promocion_descripcion' => $desc,
                            'sku' => $sku,
                            'sku_descripcion' => $skuDesc,
                            'detalle_cargado' => false,
                        ]);
                    }
                }

                // Eliminar promociones locales que ya no existen en el listado activo de Deltron
                if (!empty($incomingIds)) {
                    DeltronPromocion::whereNotIn('promocion_id', $incomingIds)->delete();
                }

                @file_put_contents(storage_path('app/deltron_promociones_snapshot.json'), json_encode($promos));
                Cache::put('deltron_promociones_list', $promos, now()->addHours(6));
                return $promos;
            }
        } catch (Exception $e) {
            Log::warning("Deltron API syncPromociones: " . $e->getMessage() . ". Leyendo de base de datos local.");
        }

        // Recuperar de la base de datos local
        $dbPromos = DeltronPromocion::all()->map(function($p) {
            return [
                'promocion_id' => $p->promocion_id,
                'promocion_descripcion' => $p->promocion_descripcion,
                'sku' => $p->sku,
                'sku_descripcion' => $p->sku_descripcion,
                'precio_promocion' => $p->precio_promocion,
                'fecha_fin' => $p->fecha_fin ? $p->fecha_fin->format('d/m/Y') : null,
                'stock_total_promo' => (int) $p->stock_total_promo,
                'detalle_cargado' => (bool) $p->detalle_cargado
            ];
        })->toArray();

        if (!empty($dbPromos)) {
            Cache::put('deltron_promociones_list', $dbPromos, now()->addHours(3));
            return $dbPromos;
        }

        return [];
    }

    /**
     * Obtiene el detalle de una promocion respetando el limite estricto de 30 llamadas/hora.
     * Consulta primero la base de datos local (deltron_promociones); si ya fue consultada
     * y no ha vencido, retorna los datos locales sin consumir cupo de la API.
     * Si no existe o se fuerza actualizacion, consulta la API oficial y persiste los productos del combo,
     * precios unitarios, precio promocional, fecha fin y stock por almacen.
     */
    public function getOrFetchPromocionDetalle(string $promoId, bool $forceRefresh = false): ?array
    {
        $promoId = trim($promoId);
        $record = DeltronPromocion::where('promocion_id', $promoId)->first();

        // Si ya esta en la base de datos, tiene el detalle cargado, no ha vencido y no se fuerza refresco
        if ($record && $record->detalle_cargado && !$forceRefresh) {
            $haVencido = $record->fecha_fin && $record->fecha_fin->isPast();
            if (!$haVencido) {
                return [
                    'promocion_id' => $record->promocion_id,
                    'promocion_descripcion' => $record->promocion_descripcion,
                    'sku' => $record->sku,
                    'sku_descripcion' => $record->sku_descripcion,
                    'precio_promocion' => $record->precio_promocion ? (float) $record->precio_promocion : null,
                    'fecha_fin' => $record->fecha_fin ? $record->fecha_fin->format('Y-m-d H:i:s') : null,
                    'fecha_fin_formateada' => $record->fecha_fin ? $record->fecha_fin->format('d/m/Y h:i A') : null,
                    'ha_vencido' => false,
                    'principal' => $record->principal_json,
                    'items' => $record->items_combo_json ?? [],
                    'stock_promocion' => $record->stock_promocion_json ?? [],
                    'stock_total_promo' => (int) $record->stock_total_promo,
                    'origen' => 'base_de_datos',
                    'actualizado_at' => $record->detalle_actualizado_at ? $record->detalle_actualizado_at->format('d/m/Y h:i A') : null
                ];
            }
        }

        // Consultar el endpoint oficial API-0000025
        $apiData = $this->getApiService()->getPromocionDetalle($promoId);
        if (!$apiData) {
            return null;
        }

        // Extraer y procesar stock por almacen (cuantos combos completos se pueden atender)
        $stockPromocion = $apiData['stock_promocion'] ?? ($apiData['stock'] ?? []);
        $totalStockCombo = 0;
        if (is_array($stockPromocion)) {
            foreach ($stockPromocion as $alm) {
                $totalStockCombo += (int) ($alm['cantidad'] ?? 0);
            }
        }

        $itemsCombo = $apiData['items'] ?? [];
        $principal = $apiData['principal'] ?? null;
        $precioPromo = isset($apiData['precio_promocion']) ? (float) $apiData['precio_promocion'] : null;
        $fechaFin = !empty($apiData['fecha_fin']) ? Carbon::parse($apiData['fecha_fin']) : null;

        // Persistir en base de datos local para reutilizarlo hasta que cambie o venza
        if (!$record) {
            $record = new DeltronPromocion();
            $record->promocion_id = $promoId;
        }

        $record->promocion_descripcion = DeltronTextHelper::stripEmojis($apiData['promocion_descripcion'] ?? ($record->promocion_descripcion ?: 'Promocion Deltron'));
        $record->sku = $principal['sku'] ?? ($record->sku ?: null);
        $record->sku_descripcion = DeltronTextHelper::stripEmojis($principal['sku_descripcion'] ?? ($record->sku_descripcion ?: null));
        $record->precio_promocion = $precioPromo;
        $record->fecha_fin = $fechaFin;
        $record->principal_json = $principal;
        $record->items_combo_json = $itemsCombo;
        $record->stock_promocion_json = $stockPromocion;
        $record->stock_total_promo = $totalStockCombo;
        $record->detalle_cargado = true;
        $record->detalle_actualizado_at = now();
        $record->save();

        return [
            'promocion_id' => $record->promocion_id,
            'promocion_descripcion' => $record->promocion_descripcion,
            'sku' => $record->sku,
            'sku_descripcion' => $record->sku_descripcion,
            'precio_promocion' => $precioPromo,
            'fecha_fin' => $fechaFin ? $fechaFin->format('Y-m-d H:i:s') : null,
            'fecha_fin_formateada' => $fechaFin ? $fechaFin->format('d/m/Y h:i A') : null,
            'ha_vencido' => $fechaFin ? $fechaFin->isPast() : false,
            'principal' => $principal,
            'items' => $itemsCombo,
            'stock_promocion' => $stockPromocion,
            'stock_total_promo' => $totalStockCombo,
            'origen' => 'api_deltron',
            'actualizado_at' => $record->detalle_actualizado_at->format('d/m/Y h:i A')
        ];
    }

    /**
     * Sincroniza un listado de productos de Deltron con Inventario_Proveedor y el catalogo de Unik
     * utilizando primordialmente el parametro part_number.
     *
     * @param array $deltronItems Listado devuelto por /api/inicio-dia o /api/catalogo
     * @return array Resumen de sincronizacion
     */
    public function syncInventario(array $deltronItems): array
    {
        $updatedCount = 0;
        $detallesActualizados = [];
        $recomendados = [];

        // Precargar mapas de productos en memoria para busqueda ultra rapida O(1)
        $productosPorParte = [];
        $productosPorParteLimpia = [];

        $todosProductos = Producto::whereNotNull('partNumber')
            ->where('partNumber', '!=', '')
            ->where('partNumber', '!=', '0')
            ->get();

        foreach ($todosProductos as $p) {
            $rawPn = strtoupper(trim($p->partNumber));
            $cleanPn = preg_replace('/[^A-Z0-9]/', '', $rawPn);

            $productosPorParte[$rawPn] = $p;
            if (!empty($cleanPn)) {
                $productosPorParteLimpia[$cleanPn] = $p;
            }
        }

        $productosPorModelo = [];
        $productosPorModeloLimpio = [];
        $todosModelos = Producto::whereNotNull('modelo')
            ->where('modelo', '!=', '')
            ->get();
        foreach ($todosModelos as $p) {
            $rawMod = strtoupper(trim($p->modelo));
            $cleanMod = preg_replace('/[^A-Z0-9]/', '', $rawMod);
            $productosPorModelo[$rawMod] = $p;
            if (!empty($cleanMod)) {
                $productosPorModeloLimpio[$cleanMod] = $p;
            }
        }

        // Precargar mapa de SKUs ya vinculados en inventario_proveedor_skus
        $productosPorSkuProveedor = [];
        $skusMapeados = InventarioProveedorSku::where('idProveedor', $this->idProveedor)
            ->with('inventarioProveedor.producto')
            ->get();
        foreach ($skusMapeados as $sm) {
            if (!empty($sm->sku_proveedor) && $sm->inventarioProveedor && $sm->inventarioProveedor->producto) {
                $productosPorSkuProveedor[strtoupper(trim($sm->sku_proveedor))] = $sm->inventarioProveedor->producto;
            }
        }

        foreach ($deltronItems as $item) {
            // Normalizar campos segun la estructura de Deltron Partner API
            $sku = $item['sku'] ?? ($item['codigo'] ?? null);
            $partNumber = $item['part_number'] ?? ($item['partNumber'] ?? ($item['numero_parte'] ?? null));
            $titulo = DeltronTextHelper::stripEmojis($item['name'] ?? ($item['descripcion'] ?? ($item['titulo'] ?? ($item['nombre'] ?? 'Producto Deltron'))));

            // Precio regular (USD sin IGV)
            $precio = isset($item['price']) ? (float) $item['price'] : (isset($item['precio']) ? (float) $item['precio'] : (isset($item['precio_lista']) ? (float) $item['precio_lista'] : null));

            // Precio de promocion (USD sin IGV)
            $precioPromo = isset($item['price_promo']) ? (float) $item['price_promo'] : (isset($item['precio_promocion']) ? (float) $item['precio_promocion'] : (isset($item['precio_promo']) ? (float) $item['precio_promo'] : (isset($item['promo_price']) ? (float) $item['promo_price'] : null)));

            // Calcular stock total sumando los almacenes de Deltron
            $stock = 0;
            $hayMas = !empty($item['hay_mas']) || !empty($item['stock']['hay_mas']);
            if (isset($item['stock']['almacenes']) && is_array($item['stock']['almacenes'])) {
                foreach ($item['stock']['almacenes'] as $alm) {
                    $stock += (int) ($alm['cantidad'] ?? 0);
                    if (!empty($alm['hay_mas'])) {
                        $hayMas = true;
                    }
                }
            } elseif (is_numeric($item['stock'] ?? null)) {
                $stock = (int) $item['stock'];
            } elseif (isset($item['stock_total']) && is_numeric($item['stock_total'])) {
                $stock = (int) $item['stock_total'];
            }

            // Mercaderia en transito (especifica del endpoint de catalogo Deltron)
            $transito = 0;
            if (isset($item['transito']) && is_numeric($item['transito'])) {
                $transito = (int) $item['transito'];
            } elseif (isset($item['en_transito']) && is_numeric($item['en_transito'])) {
                $transito = (int) $item['en_transito'];
            } elseif (isset($item['stock']['transito']) && is_numeric($item['stock']['transito'])) {
                $transito = (int) $item['stock']['transito'];
            }

            if (empty($partNumber) && empty($sku)) {
                continue;
            }

            // 1. Buscar producto en Unike principalmente por part_number (exacto o limpio alfanumerico)
            $producto = null;
            if (!empty($partNumber)) {
                $claveExacta = strtoupper(trim($partNumber));
                $claveLimpia = preg_replace('/[^A-Z0-9]/', '', $claveExacta);

                if (isset($productosPorParte[$claveExacta])) {
                    $producto = $productosPorParte[$claveExacta];
                } elseif (!empty($claveLimpia) && isset($productosPorParteLimpia[$claveLimpia])) {
                    $producto = $productosPorParteLimpia[$claveLimpia];
                }
            }

            // 2. Si no coincidio en partNumber, buscar si el partNumber de Deltron fue guardado como modelo en Unike
            if (!$producto && !empty($partNumber)) {
                $claveExacta = strtoupper(trim($partNumber));
                $claveLimpia = preg_replace('/[^A-Z0-9]/', '', $claveExacta);

                if (isset($productosPorModelo[$claveExacta])) {
                    $producto = $productosPorModelo[$claveExacta];
                } elseif (!empty($claveLimpia) && isset($productosPorModeloLimpio[$claveLimpia])) {
                    $producto = $productosPorModeloLimpio[$claveLimpia];
                }
            }

            // 3. Si no coincidio por part_number, intentar por SKU Deltron (modelo)
            if (!$producto && !empty($sku)) {
                $claveSku = strtoupper(trim($sku));
                $claveSkuLimpia = preg_replace('/[^A-Z0-9]/', '', $claveSku);

                if (isset($productosPorModelo[$claveSku])) {
                    $producto = $productosPorModelo[$claveSku];
                } elseif (!empty($claveSkuLimpia) && isset($productosPorModeloLimpio[$claveSkuLimpia])) {
                    $producto = $productosPorModeloLimpio[$claveSkuLimpia];
                }

                // 4. Si no coincidio, buscar en nuestra tabla relacional de SKUs Deltron (inventario_proveedor_skus)
                if (!$producto && isset($productosPorSkuProveedor[$claveSku])) {
                    $producto = $productosPorSkuProveedor[$claveSku];
                }
            }

            // Si el corte matutino (inicio-dia) no envio part_number, pero el producto ya existe en la BD local:
            if ($producto && empty($partNumber) && !empty($producto->partNumber) && $producto->partNumber !== '0') {
                $partNumber = $producto->partNumber;
            }

            // Si el producto en Unike tenia partNumber en '0' o vacio, pero Deltron provee el oficial:
            if ($producto && ($producto->partNumber === '0' || empty($producto->partNumber)) && !empty($partNumber) && $partNumber !== '0') {
                $producto->partNumber = $partNumber;
                $producto->save();
            }

            if ($producto) {
                // Actualizar timestamp en DetalleProducto
                DetalleProducto::where('idProducto', $producto->idProducto)->update(['updated_at' => now()]);

                // Actualizar o crear registro en Inventario_Proveedor (1:1 con Producto)
                $invProveedor = Inventario_Proveedor::where('idProducto', $producto->idProducto)->first();
                if (!$invProveedor) {
                    $invProveedor = new Inventario_Proveedor();
                    $invProveedor->idInventarioProveedor = $producto->idProducto;
                    $invProveedor->idProducto = $producto->idProducto;
                }
                $invProveedor->idProveedor = $this->idProveedor;
                $invProveedor->stock = $stock;
                $invProveedor->estado = 1;
                $invProveedor->save();
                $invProveedor->touch();

                // Almacenar SKU, Mini Codigo y Precios oficiales de Deltron en la tabla relacional
                if (!empty($sku)) {
                    $skuValues = [
                        'sku_proveedor' => strtoupper(trim($sku)),
                        'part_number' => $partNumber ?: $producto->partNumber,
                        'hay_mas' => $hayMas,
                    ];
                    if ($precio !== null && $precio > 0) {
                        $skuValues['precio_b2b'] = $precio;
                    }
                    if ($precioPromo !== null && $precioPromo > 0) {
                        $skuValues['precio_promo'] = $precioPromo;
                    }
                    $miniCodeItem = !empty($item['mini_code']) ? trim($item['mini_code']) : (!empty($item['mini_codigo']) ? trim($item['mini_codigo']) : null);
                    if (!empty($miniCodeItem)) {
                        $skuValues['mini_codigo'] = $miniCodeItem;
                    }
                    $skuRecord = InventarioProveedorSku::updateOrCreate(
                        [
                            'idInventarioProveedor' => $invProveedor->idInventarioProveedor,
                            'idProveedor' => $this->idProveedor,
                        ],
                        $skuValues
                    );
                    $skuRecord->touch();
                }

                // Preservar intactos los precios publicados en la tienda web

                $detallesActualizados[] = [
                    'idProducto' => $producto->idProducto,
                    'part_number' => $partNumber,
                    'sku' => $sku,
                    'stock' => $stock,
                    'transito' => $transito,
                    'precio' => $precio,
                    'precio_promocion' => $precioPromo,
                ];
                $updatedCount++;
            } else {
                // Si el producto no esta en el catalogo pero tiene stock destacado o mercaderia en transito
                if ($stock >= 50 || $transito >= 20) {
                    $pnRec = $partNumber;
                    if (empty($pnRec) && !empty($sku)) {
                        $skuPrevio = InventarioProveedorSku::where('sku_proveedor', $sku)->first();
                        if ($skuPrevio && !empty($skuPrevio->part_number)) {
                            $pnRec = $skuPrevio->part_number;
                        }
                    }

                    $recomendados[] = [
                        'titulo' => $titulo,
                        'part_number' => $pnRec,
                        'sku' => $sku,
                        'stock' => $stock,
                        'transito' => $transito,
                        'precio' => $precio,
                        'precio_promocion' => $precioPromo,
                    ];
                }
            }
        }

        // Ordenar recomendados por stock descendente y limitar a los 50 principales
        usort($recomendados, function ($a, $b) {
            return ($b['stock'] ?? 0) <=> ($a['stock'] ?? 0);
        });
        $recomendadosTop = array_slice($recomendados, 0, 50);

        // Limitar detalles de auditoria a un resumen de maximo 100 items
        $detallesResumen = array_slice($detallesActualizados, 0, 100);

        return [
            'count' => $updatedCount,
            'detalles' => $detallesResumen,
            'recomendados' => $recomendadosTop
        ];
    }
}
