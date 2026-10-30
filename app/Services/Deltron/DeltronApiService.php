<?php

namespace App\Services\Deltron;

use App\Models\Catalogo\DetalleProducto;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeltronApiService - Cliente de la API de Deltron Partner Service.
 * Actua como fachada principal coordinando las llamadas HTTP y delegando
 * clasificacion, sincronizacion e importacion a sus respectivos servicios modulares.
 */
class DeltronApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected ?string $manualToken;
    protected int $idProveedor = 1; // ID de Deltron en la tabla Proveedor

    protected DeltronClassifierService $classifierService;
    protected DeltronSyncService $syncService;
    protected DeltronImportService $importService;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.deltron.base_url', 'https://api.deltron.com.pe/vadhsgytGn/partner-service'), '/');
        $this->apiKey = (string) config('services.deltron.api_key', '');
        $this->apiSecret = (string) config('services.deltron.api_secret', '');
        $this->manualToken = config('services.deltron.token') ? (string) config('services.deltron.token') : null;

        $this->classifierService = new DeltronClassifierService();
        $this->syncService = new DeltronSyncService($this, $this->idProveedor);
        $this->importService = new DeltronImportService($this, $this->classifierService);
    }

    public function setClassifierService(DeltronClassifierService $svc): self
    {
        $this->classifierService = $svc;
        return $this;
    }

    public function setSyncService(DeltronSyncService $svc): self
    {
        $this->syncService = $svc;
        return $this;
    }

    public function setImportService(DeltronImportService $svc): self
    {
        $this->importService = $svc;
        return $this;
    }

    /* =========================================================================
     * ACCESORES A SUB-SERVICIOS MODULARES
     * ========================================================================= */

    public function classifier(): DeltronClassifierService
    {
        return $this->classifierService;
    }

    public function sync(): DeltronSyncService
    {
        return $this->syncService;
    }

    public function importer(): DeltronImportService
    {
        return $this->importService;
    }

    /* =========================================================================
     * AUTENTICACION Y COMUNICACION HTTP BASE
     * ========================================================================= */

    /**
     * Obtiene o renueva el token Bearer utilizando cache para respetar los limites de la API.
     */
    public function getToken(bool $forceRefresh = false): string
    {
        // 1. Si se configuro un token fijo en .env, usarlo directamente
        if (!empty($this->manualToken)) {
            return $this->manualToken;
        }

        $cacheKey = 'deltron_api_bearer_token';

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if (empty($this->apiKey) || empty($this->apiSecret)) {
            throw new Exception("Deltron API: Faltan credenciales (DELTRON_API_KEY o DELTRON_API_SECRET) en el archivo .env.");
        }

        $endpoint = $this->baseUrl . '/api/auth/token';

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'api_key' => $this->apiKey,
                    'api_secret' => $this->apiSecret,
                ]);

            if ($response->status() === 429) {
                throw new Exception("Deltron API (429): Limite de solicitudes de token alcanzado desde tu IP. Espera antes de volver a intentar.");
            }

            if ($response->status() === 403) {
                $body = $response->json();
                $msg = $body['result']['error_msg'] ?? 'Cuenta inactiva o IP no autorizada en whitelist de Deltron.';
                throw new Exception("Deltron API (403 Prohibido): {$msg}");
            }

            if ($response->status() === 401) {
                throw new Exception("Deltron API (401 No autorizado): api_key o api_secret incorrectos.");
            }

            $data = $response->json();

            if (!isset($data['status']) || $data['status'] !== 'ok' || empty($data['result']['token'])) {
                $errMsg = $data['result']['error_msg'] ?? 'Respuesta invalida al solicitar token.';
                throw new Exception("Deltron API Auth Error: {$errMsg}");
            }

            $token = $data['result']['token'];
            $clientIp = $data['result']['client_ip'] ?? 'desconocida';
            Cache::put('deltron_api_client_ip', $clientIp, now()->addDays(7));

            // Duracion del token: cacheamos por 23 horas para renovar antes de que expire
            $expiresInSeconds = 23 * 3600;
            Cache::put($cacheKey, $token, $expiresInSeconds);

            Log::info("Deltron API: Token renovado con exito para la IP cliente {$clientIp}.");

            return $token;

        } catch (Exception $e) {
            Log::error("Deltron API Auth Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realiza una peticion autenticada a la API de Deltron.
     */
    public function request(string $method, string $path, array $queryParams = [], array $body = [], int $timeout = 30): array
    {
        $token = $this->getToken();
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        $httpRequest = Http::withToken($token)
            ->timeout($timeout)
            ->withHeaders([
                'Accept' => 'application/json',
            ]);

        $response = $method === 'GET' 
            ? $httpRequest->get($url, $queryParams) 
            : $httpRequest->post($url, $body);

        // Si expiro el token (401), renovar token y reintentar una vez
        if ($response->status() === 401) {
            Log::warning("Deltron API: Token rechazado (401). Forzando renovacion...");
            $token = $this->getToken(true);
            $response = $method === 'GET' 
                ? Http::withToken($token)->timeout($timeout)->get($url, $queryParams)
                : Http::withToken($token)->timeout($timeout)->post($url, $body);
        }

        if ($response->status() === 429) {
            Log::error("Deltron API (429): Limite de tasa (Rate Limit) alcanzado para el endpoint: {$path}");
            throw new Exception("Deltron API (429): Has superado el limite de llamadas para este recurso. Por favor espera unos minutos antes de reintentar.");
        }

        if ($response->status() === 403) {
            $clientIp = Cache::get('deltron_api_client_ip', 'tu IP actual');
            throw new Exception("Deltron API (403): Acceso denegado. Asegurate de registrar la IP ({$clientIp}) en la Whitelist del panel de partners de Deltron.");
        }

        if ($response->status() === 404) {
            return [
                'status' => 'error',
                'result' => [
                    'error_id' => '404',
                    'error_msg' => 'Recurso no encontrado en Deltron.'
                ]
            ];
        }

        if (!$response->successful()) {
            $errData = $response->json();
            $msg = $errData['result']['error_msg'] ?? "HTTP Error " . $response->status();
            throw new Exception("Deltron API Error [{$response->status()}]: {$msg}");
        }

        return $response->json() ?? [];
    }

    /**
     * Obtiene la ultima IP detectada de conexion con Deltron (para verificacion de Whitelist).
     */
    public function getClientIp(): string
    {
        if (Cache::has('deltron_api_client_ip')) {
            return Cache::get('deltron_api_client_ip');
        }

        try {
            $this->getToken();
            return Cache::get('deltron_api_client_ip', 'No disponible');
        } catch (Exception $e) {
            if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $e->getMessage(), $matches)) {
                $detectedIp = $matches[1];
                Cache::put('deltron_api_client_ip', $detectedIp, now()->addMinutes(10));
                throw new Exception("IP no registrada en Deltron ({$detectedIp}).");
            }
            throw $e;
        }
    }

    /* =========================================================================
     * ENDPOINTS OFICIALES DE LA API DE DELTRON
     * ========================================================================= */

    /**
     * Consulta el stock del corte de comienzo del dia de todos los productos con sus precios.
     * Endpoint recomendado para sincronizacion diaria. Trae precio, precio_promocion y stock.
     * Deltron permite un maximo de 3 consultas cada 24 horas a este endpoint.
     */
    public function getInicioDia(int $timeout = 240, bool $forceRefresh = false): array
    {
        $cacheKey = 'deltron_inicio_dia_cache';
        $snapshotPath = storage_path('app/deltron_inicio_dia_snapshot.json');

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $res = $this->request('GET', '/api/inicio-dia', [], [], $timeout);
            $result = $res['result'] ?? ($res['data'] ?? []);
            $items = $result['items'] ?? ($result['productos'] ?? (is_array($result) ? $result : []));

            if (!empty($items) && is_array($items)) {
                Cache::put($cacheKey, $items, now()->addHours(24));
                @file_put_contents($snapshotPath, json_encode($items));
                return $items;
            }
        } catch (Exception $e) {
            Log::error("Deltron API getInicioDia: Error al consultar corte de inicio de dia: " . $e->getMessage() . ". Se evita reintentos inmediatos.");
            
            if (file_exists($snapshotPath)) {
                Log::warning("Deltron API getInicioDia: Usando snapshot local guardado en disco para no saturar la API.");
                $saved = json_decode(file_get_contents($snapshotPath), true);
                if (is_array($saved) && !empty($saved)) {
                    Cache::put($cacheKey, $saved, now()->addHours(12));
                    return $saved;
                }
            }
            throw $e;
        }

        return [];
    }

    /**
     * Descarga el catalogo completo de productos Deltron.
     * Respuesta pesada, usa timeout de hasta 300 segundos y guarda snapshot en disco.
     */
    public function getCatalogo(int $timeout = 300, bool $forceRefresh = false): array
    {
        @ini_set('memory_limit', '512M');
        set_time_limit(360);

        $cacheKey = 'deltron_catalogo_cache';
        $snapshotPath = storage_path('app/deltron_catalogo_snapshot.json');

        if (!$forceRefresh && file_exists($snapshotPath) && (time() - filemtime($snapshotPath) < 86400)) {
            $saved = @json_decode(@file_get_contents($snapshotPath), true);
            if (is_array($saved) && !empty($saved)) {
                return $saved;
            }
        }

        try {
            $res = $this->request('GET', '/api/catalogo', [], [], $timeout);
            $result = $res['result'] ?? ($res['data'] ?? []);
            $items = $result['items'] ?? ($result['productos'] ?? (is_array($result) ? $result : []));

            if (!empty($items) && is_array($items)) {
                @file_put_contents($snapshotPath, json_encode($items));
                return $items;
            }
        } catch (Exception $e) {
            if (file_exists($snapshotPath)) {
                Log::warning("Deltron API getCatalogo: Usando snapshot local guardado de catalogo.");
                $saved = json_decode(file_get_contents($snapshotPath), true);
                if (is_array($saved) && !empty($saved)) {
                    return $saved;
                }
            }
            throw $e;
        }

        return [];
    }

    /**
     * Detalle de un producto por SKU Deltron (API-0000027).
     * Endpoint oficial: GET /api/catalogo/sku/{sku}
     */
    public function getProductoPorSku(string $sku): ?array
    {
        $res = $this->request('GET', '/api/catalogo/sku/' . urlencode($sku), [], [], 15);
        if (isset($res['status']) && $res['status'] === 'ok') {
            return $res['result'] ?? null;
        }
        return null;
    }

    /**
     * Consulta y almacena la ficha completa de un producto por SKU Deltron (API-0000027).
     * Endpoint: GET /api/catalogo/sku/{sku}
     * Limite oficial Deltron: 300 llamadas por dia · 60 llamadas por hora.
     */
    public function getOrFetchFichaSku(string $sku, bool $forceRefresh = false): ?array
    {
        $cleanSku = strtoupper(trim($sku));
        if (empty($cleanSku)) {
            return null;
        }

        $safeFilename = preg_replace('/[^A-Z0-9_-]/', '_', $cleanSku);
        $cacheKey = "deltron_ficha_{$safeFilename}";
        $storageDir = storage_path('app/deltron_fichas');
        $snapshotPath = $storageDir . '/' . $safeFilename . '.json';

        // 1. Si no es refresco forzado, intentar servir desde memoria cache
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                $cached['origen'] = 'base_de_datos';
                if (empty($cached['modelo_real'])) {
                    $cached['modelo_real'] = DeltronTextHelper::extraerModeloDesdeItem($cached);
                }
                if (empty($cached['vinculado_bd']) || empty($cached['idProducto'])) {
                    $prodDb = $this->buscarProductoEnUnike($cleanSku, $cached['part_number'] ?? null, $cached['modelo_real'] ?? null);
                    if ($prodDb) {
                        $cached['vinculado_bd'] = true;
                        $cached['idProducto'] = $prodDb->idProducto;
                    }
                }
                return $cached;
            }
        }

        // 2. Si no esta en cache y no es forzado, buscar snapshot guardado en disco
        if (!$forceRefresh && file_exists($snapshotPath)) {
            $saved = @json_decode(@file_get_contents($snapshotPath), true);
            if (is_array($saved) && !empty($saved)) {
                $saved['origen'] = 'base_de_datos';
                if (empty($saved['descripcion_completa'])) {
                    $saved['descripcion_larga'] = DeltronTextHelper::cleanDeltronText($saved['descripcion_larga'] ?? '');
                    $saved['descripcion_corta'] = DeltronTextHelper::cleanDeltronText($saved['descripcion_corta'] ?? '');
                    $specsTxt = DeltronTextHelper::parseSpecsHtmlToText($saved['specs_html'] ?? '');
                    $saved['specs_texto'] = $specsTxt;
                    $comp = $saved['descripcion_larga'] ?: $saved['descripcion_corta'];
                    $saved['descripcion_completa'] = trim($comp . (!empty($specsTxt) ? "\n\n" . $specsTxt : ''));
                }
                if (empty($saved['modelo_real'])) {
                    $saved['modelo_real'] = DeltronTextHelper::extraerModeloDesdeItem($saved);
                }
                if (empty($saved['vinculado_bd']) || empty($saved['idProducto'])) {
                    $prodDb = $this->buscarProductoEnUnike($cleanSku, $saved['part_number'] ?? null, $saved['modelo_real'] ?? null);
                    if ($prodDb) {
                        $saved['vinculado_bd'] = true;
                        $saved['idProducto'] = $prodDb->idProducto;
                    }
                }
                Cache::put($cacheKey, $saved, now()->addHours(24));
                return $saved;
            }
        }

        // 3. Consultar a la API oficial de Deltron
        $raw = $this->getProductoPorSku($cleanSku);
        if (!$raw || !is_array($raw)) {
            if (file_exists($snapshotPath)) {
                $saved = @json_decode(@file_get_contents($snapshotPath), true);
                if (is_array($saved) && !empty($saved)) {
                    $saved['origen'] = 'base_de_datos';
                    $saved['aviso_api'] = 'Mostrando ficha previa almacenada localmente.';
                    if (empty($saved['descripcion_completa'])) {
                        $saved['descripcion_larga'] = DeltronTextHelper::cleanDeltronText($saved['descripcion_larga'] ?? '');
                        $saved['descripcion_corta'] = DeltronTextHelper::cleanDeltronText($saved['descripcion_corta'] ?? '');
                        $specsTxt = DeltronTextHelper::parseSpecsHtmlToText($saved['specs_html'] ?? '');
                        $saved['specs_texto'] = $specsTxt;
                        $comp = $saved['descripcion_larga'] ?: $saved['descripcion_corta'];
                        $saved['descripcion_completa'] = trim($comp . (!empty($specsTxt) ? "\n\n" . $specsTxt : ''));
                    }
                    if (empty($saved['modelo_real'])) {
                        $saved['modelo_real'] = DeltronTextHelper::extraerModeloDesdeItem($saved);
                    }
                    if (empty($saved['vinculado_bd']) || empty($saved['idProducto'])) {
                        $prodDb = $this->buscarProductoEnUnike($cleanSku, $saved['part_number'] ?? null, $saved['modelo_real'] ?? null);
                        if ($prodDb) {
                            $saved['vinculado_bd'] = true;
                            $saved['idProducto'] = $prodDb->idProducto;
                        }
                    }
                    return $saved;
                }
            }
            return null;
        }

        // 4. Limpiar campos de texto para evitar errores con UTF-8 de 4 bytes en HostGator
        $name = DeltronTextHelper::stripEmojis($raw['name'] ?? '');
        $partNumber = DeltronTextHelper::stripEmojis($raw['part_number'] ?? '');
        $miniCode = DeltronTextHelper::stripEmojis($raw['mini_code'] ?? '');
        $brandName = DeltronTextHelper::stripEmojis($raw['brand_name'] ?? '');
        $categoryName = DeltronTextHelper::stripEmojis($raw['category_name'] ?? '');
        $subcategoryName = DeltronTextHelper::stripEmojis($raw['subcategory_name'] ?? '');
        $warranty = DeltronTextHelper::normalizarGarantiaDeltron($raw['warranty'] ?? '');
        $status = DeltronTextHelper::stripEmojis($raw['status'] ?? '');
        $descLarge = DeltronTextHelper::cleanDeltronText($raw['description_large'] ?? '');
        $descSmall = DeltronTextHelper::cleanDeltronText($raw['description_small'] ?? '');
        $specsHtml = DeltronTextHelper::stripEmojis($raw['specs']['specs_all_html'] ?? '');
        $specsTexto = DeltronTextHelper::parseSpecsHtmlToText($specsHtml);

        $descCompleta = $descLarge ?: $descSmall;
        if (!empty($descCompleta) && !empty($specsTexto)) {
            $descCompleta .= "\n\n" . $specsTexto;
        } elseif (!empty($specsTexto)) {
            $descCompleta = $specsTexto;
        }

        // Precios
        $price = isset($raw['price']) ? (float) $raw['price'] : null;
        $pricePromo = isset($raw['price_promo']) ? (float) $raw['price_promo'] : null;
        $tienePromo = ($pricePromo !== null && $price !== null && $pricePromo < $price);
        $ahorro = $tienePromo ? round($price - $pricePromo, 2) : 0;

        // Stock por almacen y calculo de total
        $almacenes = $raw['stock']['almacenes'] ?? [];
        $stockTotal = 0;
        $hayMas = !empty($raw['stock']['hay_mas']) || !empty($raw['hay_mas']);
        if (is_array($almacenes)) {
            foreach ($almacenes as &$alm) {
                $alm['almacen_name'] = DeltronTextHelper::stripEmojis($alm['almacen_name'] ?? '');
                $stockTotal += (int) ($alm['cantidad'] ?? 0);
                if (!empty($alm['hay_mas'])) {
                    $hayMas = true;
                }
            }
            unset($alm);
        }

        // Mercaderia en transito
        $transitoItems = $raw['trans'] ?? [];
        $transitoTotal = 0;
        if (is_array($transitoItems)) {
            foreach ($transitoItems as &$tr) {
                $tr['almacen_name'] = DeltronTextHelper::stripEmojis($tr['almacen_name'] ?? '');
                $tr['fecha'] = DeltronTextHelper::stripEmojis($tr['fecha'] ?? '');
                $tr['tipo_transporte'] = DeltronTextHelper::stripEmojis($tr['tipo_transporte'] ?? ($tr['transporte'] ?? ''));
                $cantTr = (int) ($tr['cantidad'] ?? ($tr['cant'] ?? 0));
                $transitoTotal += $cantTr;
            }
            unset($tr);
        }

        // Promociones del producto
        $promociones = [];
        if (!empty($raw['promociones']) && is_array($raw['promociones'])) {
            foreach ($raw['promociones'] as $p) {
                $promociones[] = [
                    'promocion_id' => DeltronTextHelper::stripEmojis($p['promocion_id'] ?? ''),
                    'promocion_descripcion' => DeltronTextHelper::stripEmojis($p['promocion_descripcion'] ?? ''),
                    'precio' => isset($p['precio']) ? (float) $p['precio'] : null,
                    'stock' => $p['stock'] ?? []
                ];
            }
        }

        // Imagenes
        $images = [
            'listado' => $raw['images']['image_listado'] ?? null,
            'carrusel' => $raw['images']['images_carrusel'] ?? []
        ];

        // 5. Vincular y sincronizar con la base de datos local de Unike (Inventario_Proveedor)
        $modeloReal = DeltronTextHelper::extraerModeloDesdeItem($raw);
        $producto = $this->buscarProductoEnUnike($cleanSku, $partNumber, $modeloReal);

        $vinculadoBd = false;
        $idProducto = null;
        if ($producto) {
            $vinculadoBd = true;
            $idProducto = $producto->idProducto;

            DetalleProducto::where('idProducto', $producto->idProducto)->update(['updated_at' => now()]);

            $invProveedor = Inventario_Proveedor::where('idProducto', $producto->idProducto)->first();
            if (!$invProveedor) {
                $invProveedor = new Inventario_Proveedor();
                $invProveedor->idInventarioProveedor = $producto->idProducto;
                $invProveedor->idProducto = $producto->idProducto;
            }
            $invProveedor->idProveedor = $this->idProveedor;
            $invProveedor->stock = $stockTotal;
            $invProveedor->estado = 1;
            $invProveedor->save();

            $skuValues = [
                'sku_proveedor' => $cleanSku,
                'part_number' => $partNumber ?: $producto->partNumber,
                'hay_mas' => $hayMas,
            ];
            if ($price !== null && $price > 0) {
                $skuValues['precio_b2b'] = $price;
            }
            if ($pricePromo !== null && $pricePromo > 0) {
                $skuValues['precio_promo'] = $pricePromo;
            }
            if (!empty($miniCode)) {
                $skuValues['mini_codigo'] = $miniCode;
            }
            InventarioProveedorSku::updateOrCreate(
                [
                    'idInventarioProveedor' => $invProveedor->idInventarioProveedor,
                    'idProveedor' => $this->idProveedor,
                ],
                $skuValues
            );
        }

        $ficha = [
            'sku' => $cleanSku,
            'part_number' => $partNumber,
            'modelo_real' => $modeloReal,
            'mini_code' => $miniCode,
            'name' => $name,
            'brand_name' => $brandName,
            'category_name' => $categoryName,
            'subcategory_name' => $subcategoryName,
            'warranty' => $warranty,
            'status' => $status,
            'price' => $price,
            'price_promo' => $pricePromo,
            'price_con_igv' => $price ? round($price * 1.18, 2) : null,
            'price_promo_con_igv' => $pricePromo ? round($pricePromo * 1.18, 2) : null,
            'tiene_promo' => $tienePromo,
            'ahorro' => $ahorro,
            'stock_total' => $stockTotal,
            'hay_mas' => $hayMas,
            'almacenes' => $almacenes,
            'transito_total' => $transitoTotal,
            'transito_items' => $transitoItems,
            'promociones' => $promociones,
            'images' => $images,
            'specs_html' => $specsHtml,
            'specs_texto' => $specsTexto,
            'descripcion_larga' => $descLarge,
            'descripcion_corta' => $descSmall,
            'descripcion_completa' => $descCompleta,
            'vinculado_bd' => $vinculadoBd,
            'idProducto' => $idProducto,
            'origen' => 'api_deltron',
            'actualizado_at' => now()->format('d/m/Y h:i A')
        ];

        // 6. Guardar en almacenamiento persistente de disco y en Cache
        if (!file_exists($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        @file_put_contents($snapshotPath, json_encode($ficha, JSON_UNESCAPED_UNICODE));
        Cache::put($cacheKey, $ficha, now()->addHours(24));

        return $ficha;
    }

    /**
     * Consulta el stock por almacen de un producto en tiempo real.
     */
    public function getStock(string $sku): ?array
    {
        $res = $this->request('GET', '/api/stock', ['sku' => $sku], [], 15);
        $items = $res['result']['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $first = $items[0];
            $almacenes = $first['stock']['almacenes'] ?? [];
            $total = 0;
            $hayMas = !empty($first['stock']['hay_mas']) || !empty($first['hay_mas']);
            foreach ($almacenes as $alm) {
                $total += (int) ($alm['cantidad'] ?? 0);
                if (!empty($alm['hay_mas'])) {
                    $hayMas = true;
                }
            }
            return [
                'sku' => $first['sku'] ?? $sku,
                'name' => $first['name'] ?? '',
                'stock_total' => $total,
                'hay_mas' => $hayMas,
                'almacenes' => $almacenes
            ];
        }
        return null;
    }

    /**
     * Consulta el precio vigente y precio de promocion de un producto puntual en tiempo real (API-0000030).
     * Endpoint: GET /api/precio?sku={sku}
     * Limite oficial Deltron: 60 llamadas por hora.
     */
    public function getPrecio(string $sku): ?array
    {
        $res = $this->request('GET', '/api/precio', ['sku' => trim($sku)], [], 15);
        $items = $res['result']['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $first = $items[0];
            $price = isset($first['price']) ? (float) $first['price'] : null;
            $pricePromo = isset($first['price_promo']) ? (float) $first['price_promo'] : null;
            $tienePromo = ($pricePromo !== null && $price !== null && $pricePromo < $price);
            $ahorro = $tienePromo ? round($price - $pricePromo, 2) : 0;

            return [
                'sku' => $first['sku'] ?? $sku,
                'name' => $first['name'] ?? '',
                'price' => $price,
                'price_promo' => $pricePromo,
                'price_con_igv' => $price ? round($price * 1.18, 2) : null,
                'price_promo_con_igv' => $pricePromo ? round($pricePromo * 1.18, 2) : null,
                'tiene_promo' => $tienePromo,
                'ahorro' => $ahorro,
                'consultado_at' => now()->format('d/m/Y h:i A')
            ];
        }
        return null;
    }

    /**
     * Consulta las promociones activas disponibles para el partner.
     * Permite filtrar opcionalmente por SKU.
     */
    public function getPromociones(?string $sku = null): array
    {
        $params = [];
        if (!empty($sku)) {
            $params['sku'] = trim($sku);
        }

        $res = $this->request('GET', '/api/promociones', $params, [], 20);
        $result = $res['result'] ?? ($res['items'] ?? (is_array($res) ? $res : []));
        return is_array($result) ? $result : [];
    }

    /**
     * Consulta el detalle de una promocion especifica desde la API de Deltron (API-0000025).
     * Endpoint: GET /api/promociones/{promo_id}
     */
    public function getPromocionDetalle(string $promoId): ?array
    {
        $res = $this->request('GET', '/api/promociones/' . urlencode($promoId), [], [], 20);
        if (isset($res['status']) && $res['status'] === 'ok') {
            return $res['result'] ?? null;
        }
        return $res['result'] ?? ($res['data'] ?? (isset($res['promocion_id']) ? $res : null));
    }

    /* =========================================================================
     * DELEGACION HACIA DeltronSyncService
     * ========================================================================= */

    public function buscarProductoEnUnike(string $sku, ?string $partNumber = null, ?string $modeloReal = null): ?Producto
    {
        return $this->syncService->buscarProductoEnUnike($sku, $partNumber, $modeloReal);
    }

    public function syncPromociones(): array
    {
        return $this->syncService->syncPromociones();
    }

    public function getOrFetchPromocionDetalle(string $promoId, bool $forceRefresh = false): ?array
    {
        return $this->syncService->getOrFetchPromocionDetalle($promoId, $forceRefresh);
    }

    public function syncInventario(array $deltronItems): array
    {
        return $this->syncService->syncInventario($deltronItems);
    }

    /* =========================================================================
     * DELEGACION HACIA DeltronClassifierService
     * ========================================================================= */

    public function clasificarGrupoSugerido(array $item, $grupos): array
    {
        return $this->classifierService->clasificarGrupoSugerido($item, $grupos);
    }

    public function emparejarMarca(?string $deltronBrand, $marcas): int
    {
        return $this->classifierService->emparejarMarca($deltronBrand, $marcas);
    }

    public function generarSiguienteCodigoProducto(int $idGrupo, string $nombreGrupo): string
    {
        return $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);
    }

    public static function detectarVarianteDeltron(array $item): ?array
    {
        return DeltronClassifierService::detectarVarianteDeltron($item);
    }

    public static function extraerModeloBaseYNombrePadre(array $item, ?array $variante): array
    {
        return DeltronClassifierService::extraerModeloBaseYNombrePadre($item, $variante);
    }

    /* =========================================================================
     * DELEGACION HACIA DeltronImportService
     * ========================================================================= */

    public function getCandidatosOfertasAltoStock(): array
    {
        return $this->importService->getCandidatosOfertasAltoStock();
    }

    public function getCandidatosCatalogoPaginado(array $filtros = [], int $page = 1, int $perPage = 100): array
    {
        return $this->importService->getCandidatosCatalogoPaginado($filtros, $page, $perPage);
    }

    public function importarLoteOfertas(array $itemsAImportar): array
    {
        return $this->importService->importarLoteOfertas($itemsAImportar);
    }

    public function completarFotosProducto(Producto $producto): array
    {
        return $this->importService->completarFotosProducto($producto);
    }

    /* =========================================================================
     * PROXIES ESTATICOS HACIA DeltronTextHelper (100% COMPATIBILIDAD RETROACTIVA)
     * ========================================================================= */

    public static function stripEmojis(?string $string): ?string
    {
        return DeltronTextHelper::stripEmojis($string);
    }

    public static function cleanDeltronText(?string $string): string
    {
        return DeltronTextHelper::cleanDeltronText($string);
    }

    public static function parseSpecsHtmlToText(?string $html): string
    {
        return DeltronTextHelper::parseSpecsHtmlToText($html);
    }

    public static function construirDescripcionProducto(array $item): string
    {
        return DeltronTextHelper::construirDescripcionProducto($item);
    }

    public static function recortarTextoEnLinea(string $texto, int $max = 5000): string
    {
        return DeltronTextHelper::recortarTextoEnLinea($texto, $max);
    }

    public static function extraerModeloRealDesdeSpecs(?string $html): ?string
    {
        return DeltronTextHelper::extraerModeloRealDesdeSpecs($html);
    }

    public static function obtenerTituloComercialDeltron(array $item): string
    {
        return DeltronTextHelper::obtenerTituloComercialDeltron($item);
    }

    public static function normalizarGarantiaDeltron(?string $warranty): string
    {
        return DeltronTextHelper::normalizarGarantiaDeltron($warranty);
    }

    public static function obtenerUrlCdnDeltron(string $sku): ?string
    {
        return DeltronTextHelper::obtenerUrlCdnDeltron($sku);
    }

    public static function getDestinationPathProductos(): string
    {
        return DeltronTextHelper::getDestinationPathProductos();
    }
}
