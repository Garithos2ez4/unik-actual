<?php

namespace App\Services\Deltron;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\Inventario_Proveedor;
use Exception;

class DeltronApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected ?string $manualToken;
    protected int $idProveedor = 1; // ID de Deltron en la tabla Preveedor

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.deltron.base_url', 'https://api.deltron.com.pe/vadhsgytGn/partner-service'), '/');
        $this->apiKey = (string) config('services.deltron.api_key', '');
        $this->apiSecret = (string) config('services.deltron.api_secret', '');
        $this->manualToken = config('services.deltron.token') ? (string) config('services.deltron.token') : null;
    }

    /**
     * Obtiene o renueva el token Bearer utilizando caché para respetar los límites de la API.
     */
    public function getToken(bool $forceRefresh = false): string
    {
        // 1. Si se configuró un token fijo en .env, usarlo directamente
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
                throw new Exception("Deltron API (429): Límite de solicitudes de token alcanzado desde tu IP. Espera antes de volver a intentar.");
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
                $errMsg = $data['result']['error_msg'] ?? 'Respuesta inválida al solicitar token.';
                throw new Exception("Deltron API Auth Error: {$errMsg}");
            }

            $token = $data['result']['token'];
            $clientIp = $data['result']['client_ip'] ?? 'desconocida';
            Cache::put('deltron_api_client_ip', $clientIp, now()->addDays(7));

            // Duración del token (Deltron suele devolver 24h, cacheamos por 23 horas para renovar antes de que expire)
            $expiresInSeconds = 23 * 3600;
            Cache::put($cacheKey, $token, $expiresInSeconds);

            Log::info("Deltron API: Token renovado con éxito para la IP cliente {$clientIp}.");

            return $token;

        } catch (Exception $e) {
            Log::error("Deltron API Auth Exception: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realiza una petición autenticada a la API de Deltron.
     */
    protected function request(string $method, string $path, array $queryParams = [], array $body = [], int $timeout = 30): array
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

        // Si expiró el token (401 con código 205 o mensaje de token), renovar token y reintentar una vez
        if ($response->status() === 401) {
            Log::warning("Deltron API: Token rechazado (401). Forzando renovación...");
            $token = $this->getToken(true);
            $response = $method === 'GET' 
                ? Http::withToken($token)->timeout($timeout)->get($url, $queryParams)
                : Http::withToken($token)->timeout($timeout)->post($url, $body);
        }

        if ($response->status() === 429) {
            Log::error("Deltron API (429): Límite de tasa (Rate Limit) alcanzado para el endpoint: {$path}");
            throw new Exception("Deltron API (429): Has superado el límite de llamadas para este recurso. Por favor espera unos minutos antes de reintentar.");
        }

        if ($response->status() === 403) {
            $clientIp = Cache::get('deltron_api_client_ip', 'tu IP actual');
            throw new Exception("Deltron API (403): Acceso denegado. Asegúrate de registrar la IP ({$clientIp}) en la Whitelist del panel de partners de Deltron.");
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
     * Consulta el stock del corte de comienzo del día de todos los productos con sus precios.
     * Endpoint recomendado para sincronización diaria. Trae precio, precio_promocion y stock.
     * Configurado con timeout amplio (240s) y manejo de fallos para evitar reintentos inmediatos.
     * Deltron permite un máximo de 3 consultas cada 24 horas a este endpoint.
     */
    public function getInicioDia(int $timeout = 240, bool $forceRefresh = false): array
    {
        $cacheKey = 'deltron_inicio_dia_cache';
        $snapshotPath = storage_path('app/deltron_inicio_dia_snapshot.json');

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Timeout amplio (240s) para procesar la respuesta pesada de inicio de día
            $res = $this->request('GET', '/api/inicio-dia', [], [], $timeout);
            $result = $res['result'] ?? ($res['data'] ?? []);
            $items = $result['items'] ?? ($result['productos'] ?? (is_array($result) ? $result : []));

            if (!empty($items) && is_array($items)) {
                Cache::put($cacheKey, $items, now()->addHours(24));
                @file_put_contents($snapshotPath, json_encode($items));
                return $items;
            }
        } catch (Exception $e) {
            Log::error("Deltron API getInicioDia: Error al consultar corte de inicio de día: " . $e->getMessage() . ". Se evita reintentos inmediatos.");
            
            // Si la llamada falló (p. ej. 429 por límite diario alcanzado), usar el snapshot local guardado en disco
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
     * Descarga el catálogo completo de productos Deltron.
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
     * Límite oficial Deltron: 300 llamadas por día · 60 llamadas por hora.
     * Uso recomendado: Bajo demanda por producto nuevo o puntual. Almacena en disco y base de datos
     * local para que las consultas frecuentes de la tienda web no consuman llamadas a la API.
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
                return $cached;
            }
        }

        // 2. Si no está en cache y no es forzado, buscar snapshot guardado en disco
        if (!$forceRefresh && file_exists($snapshotPath)) {
            $saved = @json_decode(@file_get_contents($snapshotPath), true);
            if (is_array($saved) && !empty($saved)) {
                $saved['origen'] = 'base_de_datos';
                Cache::put($cacheKey, $saved, now()->addHours(24));
                return $saved;
            }
        }

        // 3. Consultar a la API oficial de Deltron
        $raw = $this->getProductoPorSku($cleanSku);
        if (!$raw || !is_array($raw)) {
            // Si la llamada falla o no encuentra el SKU, usar snapshot si existe
            if (file_exists($snapshotPath)) {
                $saved = @json_decode(@file_get_contents($snapshotPath), true);
                if (is_array($saved) && !empty($saved)) {
                    $saved['origen'] = 'base_de_datos';
                    $saved['aviso_api'] = 'Mostrando ficha previa almacenada localmente.';
                    return $saved;
                }
            }
            return null;
        }

        // 4. Limpiar campos de texto para evitar errores con UTF-8 de 4 bytes en HostGator
        $name = self::stripEmojis($raw['name'] ?? '');
        $partNumber = self::stripEmojis($raw['part_number'] ?? '');
        $miniCode = self::stripEmojis($raw['mini_code'] ?? '');
        $brandName = self::stripEmojis($raw['brand_name'] ?? '');
        $categoryName = self::stripEmojis($raw['category_name'] ?? '');
        $subcategoryName = self::stripEmojis($raw['subcategory_name'] ?? '');
        $warranty = self::stripEmojis($raw['warranty'] ?? '');
        $status = self::stripEmojis($raw['status'] ?? '');
        $descLarge = self::stripEmojis($raw['description_large'] ?? '');
        $descSmall = self::stripEmojis($raw['description_small'] ?? '');
        $specsHtml = self::stripEmojis($raw['specs']['specs_all_html'] ?? '');

        // Precios
        $price = isset($raw['price']) ? (float) $raw['price'] : null;
        $pricePromo = isset($raw['price_promo']) ? (float) $raw['price_promo'] : null;
        $tienePromo = ($pricePromo !== null && $price !== null && $pricePromo < $price);
        $ahorro = $tienePromo ? round($price - $pricePromo, 2) : 0;

        // Stock por almacén y cálculo de total
        $almacenes = $raw['stock']['almacenes'] ?? [];
        $stockTotal = 0;
        if (is_array($almacenes)) {
            foreach ($almacenes as &$alm) {
                $alm['almacen_name'] = self::stripEmojis($alm['almacen_name'] ?? '');
                $stockTotal += (int) ($alm['cantidad'] ?? 0);
            }
            unset($alm);
        }

        // Mercadería en tránsito
        $transitoItems = $raw['trans'] ?? [];
        $transitoTotal = 0;
        if (is_array($transitoItems)) {
            foreach ($transitoItems as &$tr) {
                $tr['tipo_transporte'] = self::stripEmojis($tr['tipo_transporte'] ?? ($tr['transporte'] ?? ''));
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
                    'promocion_id' => self::stripEmojis($p['promocion_id'] ?? ''),
                    'promocion_descripcion' => self::stripEmojis($p['promocion_descripcion'] ?? ''),
                    'precio' => isset($p['precio']) ? (float) $p['precio'] : null,
                    'stock' => $p['stock'] ?? []
                ];
            }
        }

        // Imágenes
        $images = [
            'listado' => $raw['images']['image_listado'] ?? null,
            'carrusel' => $raw['images']['images_carrusel'] ?? []
        ];

        // 5. Vincular y sincronizar con la base de datos local de Unike (Inventario_Proveedor)
        $producto = null;
        if (!empty($partNumber) && $partNumber !== '-') {
            $claveExacta = strtoupper(trim($partNumber));
            $claveLimpia = preg_replace('/[^A-Z0-9]/', '', $claveExacta);

            $producto = Producto::where('partNumber', $claveExacta)->first();
            if (!$producto && !empty($claveLimpia)) {
                $producto = Producto::whereRaw("UPPER(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), ' ', ''), '/', '')) = ?", [$claveLimpia])->first();
            }
        }

        if (!$producto && !empty($cleanSku)) {
            $producto = Producto::where('modelo', $cleanSku)
                ->orWhere('codigoProducto', $cleanSku)
                ->orWhere('partNumber', $cleanSku)
                ->first();
        }

        $vinculadoBd = false;
        $idProducto = null;
        if ($producto) {
            $vinculadoBd = true;
            $idProducto = $producto->idProducto;

            // Actualizar timestamp en DetalleProducto
            \App\Models\Catalogo\DetalleProducto::where('idProducto', $producto->idProducto)->update(['updated_at' => now()]);

            // Actualizar o crear registro en Inventario_Proveedor
            $invProveedor = Inventario_Proveedor::updateOrCreate(
                [
                    'idProducto' => $producto->idProducto,
                    'idProveedor' => $this->idProveedor,
                ],
                [
                    'stock' => $stockTotal,
                    'estado' => 1
                ]
            );

            // Almacenar SKU y Mini Código del proveedor en la tabla relacional
            $skuValues = [
                'sku_proveedor' => $cleanSku,
                'part_number' => $partNumber ?: $producto->partNumber,
            ];
            if (!empty($miniCode)) {
                $skuValues['mini_codigo'] = $miniCode;
            }
            \App\Models\Inventario\InventarioProveedorSku::updateOrCreate(
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
            'almacenes' => $almacenes,
            'transito_total' => $transitoTotal,
            'transito_items' => $transitoItems,
            'promociones' => $promociones,
            'images' => $images,
            'specs_html' => $specsHtml,
            'descripcion_larga' => $descLarge,
            'descripcion_corta' => $descSmall,
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
     * Consulta el stock por almacén de un producto en tiempo real.
     */
    public function getStock(string $sku): ?array
    {
        $res = $this->request('GET', '/api/stock', ['sku' => $sku], [], 15);
        $items = $res['result']['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $first = $items[0];
            $almacenes = $first['stock']['almacenes'] ?? [];
            $total = 0;
            foreach ($almacenes as $alm) {
                $total += (int) ($alm['cantidad'] ?? 0);
            }
            return [
                'sku' => $first['sku'] ?? $sku,
                'name' => $first['name'] ?? '',
                'stock_total' => $total,
                'almacenes' => $almacenes
            ];
        }
        return null;
    }

    /**
    /**
     * Consulta el precio vigente y precio de promoción de un producto puntual en tiempo real (API-0000030).
     * Endpoint: GET /api/precio?sku={sku}
     * Límite oficial Deltron: 60 llamadas por hora.
     * Uso recomendado: bajo demanda antes de cotizar o confirmar una venta puntual.
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
     * Sincroniza la lista de promociones oficiales de Deltron en la base de datos local
     * (tabla deltron_promociones) y en snapshot de disco.
     * Cumple con la recomendación de Deltron de sincronizar vía cron y servir desde la BD local.
     */
    /**
     * Limpia emojis y caracteres UTF-8 de 4 bytes para total compatibilidad con bases de datos en HostGator (utf8_general_ci / utf8mb3).
     */
    public static function stripEmojis(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }
        return preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $string);
    }

    /**
     * Sincroniza la lista de promociones oficiales de Deltron en la base de datos local
     * (tabla deltron_promociones) y en snapshot de disco.
     * Preserva los detalles ya consultados y añade las promociones nuevas sin saturar la API.
     */
    public function syncPromociones(): array
    {
        try {
            $promos = $this->getPromociones();
            if (!empty($promos) && is_array($promos)) {
                $now = now();
                $incomingIds = [];

                // Obtener promociones existentes en base de datos para no borrar detalles ya guardados
                $existentes = \App\Models\Inventario\DeltronPromocion::all()->keyBy('promocion_id');

                foreach ($promos as $p) {
                    $promoId = trim($p['promocion_id'] ?? ($p['id'] ?? 'PROMO'));
                    if (empty($promoId)) continue;
                    $incomingIds[] = $promoId;

                    $desc = self::stripEmojis($p['promocion_descripcion'] ?? ($p['descripcion'] ?? ''));
                    $sku = $p['sku'] ?? null;
                    $skuDesc = self::stripEmojis($p['sku_descripcion'] ?? ($p['producto'] ?? null));

                    if (isset($existentes[$promoId])) {
                        // Si ya existe, actualizar descripción o SKU si cambiaron
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
                        // Nueva promoción detectada: insertar para posterior consulta de detalle
                        \App\Models\Inventario\DeltronPromocion::create([
                            'promocion_id' => $promoId,
                            'promocion_descripcion' => $desc,
                            'sku' => $sku,
                            'sku_descripcion' => $skuDesc,
                            'detalle_cargado' => false,
                        ]);
                    }
                }

                // Opcional: Eliminar promociones locales que ya no existen en el listado activo de Deltron
                if (!empty($incomingIds)) {
                    \App\Models\Inventario\DeltronPromocion::whereNotIn('promocion_id', $incomingIds)->delete();
                }

                @file_put_contents(storage_path('app/deltron_promociones_snapshot.json'), json_encode($promos));
                Cache::put('deltron_promociones_list', $promos, now()->addHours(6));
                return $promos;
            }
        } catch (Exception $e) {
            Log::warning("Deltron API syncPromociones: " . $e->getMessage() . ". Leyendo de base de datos local.");
        }

        // Recuperar de la base de datos local
        $dbPromos = \App\Models\Inventario\DeltronPromocion::all()->map(function($p) {
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
     * Consulta el detalle de una promoción específica desde la API de Deltron (API-0000025).
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

    /**
     * Obtiene el detalle de una promoción respetando el límite estricto de 30 llamadas/hora.
     * Consulta primero la base de datos local (deltron_promociones); si ya fue consultada
     * y no ha vencido, retorna los datos locales sin consumir cupo de la API.
     * Si no existe o se fuerza actualización, consulta la API oficial y persiste los productos del combo,
     * precios unitarios, precio promocional, fecha fin y stock por almacén.
     */
    public function getOrFetchPromocionDetalle(string $promoId, bool $forceRefresh = false): ?array
    {
        $promoId = trim($promoId);
        $record = \App\Models\Inventario\DeltronPromocion::where('promocion_id', $promoId)->first();

        // Si ya está en la base de datos, tiene el detalle cargado, no ha vencido y no se fuerza refresco
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
        $apiData = $this->getPromocionDetalle($promoId);
        if (!$apiData) {
            return null;
        }

        // Extraer y procesar stock por almacén (cuántos combos completos se pueden atender)
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
        $fechaFin = !empty($apiData['fecha_fin']) ? \Carbon\Carbon::parse($apiData['fecha_fin']) : null;

        // Persistir en base de datos local para reutilizarlo hasta que cambie o venza
        if (!$record) {
            $record = new \App\Models\Inventario\DeltronPromocion();
            $record->promocion_id = $promoId;
        }

        $record->promocion_descripcion = self::stripEmojis($apiData['promocion_descripcion'] ?? ($record->promocion_descripcion ?: 'Promoción Deltron'));
        $record->sku = $principal['sku'] ?? ($record->sku ?: null);
        $record->sku_descripcion = self::stripEmojis($principal['sku_descripcion'] ?? ($record->sku_descripcion ?: null));
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
     * Obtiene la última IP detectada de conexión con Deltron (para verificación de Whitelist).
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
            // Si el error contiene la IP detectada por Deltron
            if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $e->getMessage(), $matches)) {
                $detectedIp = $matches[1];
                Cache::put('deltron_api_client_ip', $detectedIp, now()->addMinutes(10));
                throw new Exception("IP no registrada en Deltron ({$detectedIp}).");
            }
            throw $e;
        }
    }

    /**
     * Sincroniza un listado de productos de Deltron con Inventario_Proveedor y el catálogo de Unik
     * utilizando primordialmente el parámetro part_number.
     *
     * @param array $deltronItems Listado devuelto por /api/inicio-dia o /api/catalogo
     * @return array Resumen de sincronización
     */
    public function syncInventario(array $deltronItems): array
    {
        $updatedCount = 0;
        $detallesActualizados = [];
        $recomendados = [];

        // Precargar mapas de productos en memoria para búsqueda ultra rápida O(1)
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

        $productosPorModelo = Producto::whereNotNull('modelo')
            ->where('modelo', '!=', '')
            ->get()
            ->keyBy(function ($p) {
                return strtoupper(trim($p->modelo));
            });

        // Precargar mapa de SKUs ya vinculados en inventario_proveedor_skus
        $productosPorSkuProveedor = [];
        $skusMapeados = \App\Models\Inventario\InventarioProveedorSku::where('idProveedor', $this->idProveedor)
            ->with('inventarioProveedor.producto')
            ->get();
        foreach ($skusMapeados as $sm) {
            if (!empty($sm->sku_proveedor) && $sm->inventarioProveedor && $sm->inventarioProveedor->producto) {
                $productosPorSkuProveedor[strtoupper(trim($sm->sku_proveedor))] = $sm->inventarioProveedor->producto;
            }
        }

        foreach ($deltronItems as $item) {
            // Normalizar campos según la estructura de Deltron Partner API
            $sku = $item['sku'] ?? ($item['codigo'] ?? null);
            $partNumber = $item['part_number'] ?? ($item['partNumber'] ?? ($item['numero_parte'] ?? null));
            $titulo = self::stripEmojis($item['name'] ?? ($item['descripcion'] ?? ($item['titulo'] ?? ($item['nombre'] ?? 'Producto Deltron'))));

            // Precio regular (USD sin IGV)
            $precio = isset($item['price']) ? (float) $item['price'] : (isset($item['precio']) ? (float) $item['precio'] : (isset($item['precio_lista']) ? (float) $item['precio_lista'] : null));

            // Precio de promoción (USD sin IGV)
            $precioPromo = isset($item['price_promo']) ? (float) $item['price_promo'] : (isset($item['precio_promocion']) ? (float) $item['precio_promocion'] : (isset($item['precio_promo']) ? (float) $item['precio_promo'] : (isset($item['promo_price']) ? (float) $item['promo_price'] : null)));

            // Calcular stock total sumando los almacenes de Deltron
            $stock = 0;
            if (isset($item['stock']['almacenes']) && is_array($item['stock']['almacenes'])) {
                foreach ($item['stock']['almacenes'] as $alm) {
                    $stock += (int) ($alm['cantidad'] ?? 0);
                }
            } elseif (is_numeric($item['stock'] ?? null)) {
                $stock = (int) $item['stock'];
            } elseif (isset($item['stock_total']) && is_numeric($item['stock_total'])) {
                $stock = (int) $item['stock_total'];
            }

            // Mercadería en tránsito (específica del endpoint de catálogo Deltron)
            $transito = 0;
            if (isset($item['transito']) && is_numeric($item['transito'])) {
                $transito = (int) $item['transito'];
            } elseif (isset($item['en_transito']) && is_numeric($item['en_transito'])) {
                $transito = (int) $item['en_transito'];
            } elseif (isset($item['stock']['transito']) && is_numeric($item['stock']['transito'])) {
                $transito = (int) $item['stock']['transito'];
            }

            // Imágenes y especificaciones técnicas (presentes en /api/catalogo)
            $imagenes = $item['images'] ?? ($item['imagenes'] ?? ($item['imagen'] ?? []));
            $especificaciones = $item['specs'] ?? ($item['especificaciones'] ?? ($item['caracteristicas'] ?? []));

            if (empty($partNumber) && empty($sku)) {
                continue;
            }

            // 1. Buscar producto en Unike principalmente por part_number (exacto o limpio alfanumérico)
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

            // 2. Si no coincidió por part_number, intentar por SKU Deltron (modelo)
            if (!$producto && !empty($sku)) {
                $claveSku = strtoupper(trim($sku));
                $producto = $productosPorModelo->get($claveSku);

                // 3. Si no coincidió, buscar en nuestra tabla relacional de SKUs Deltron (inventario_proveedor_skus)
                if (!$producto && isset($productosPorSkuProveedor[$claveSku])) {
                    $producto = $productosPorSkuProveedor[$claveSku];
                }
            }

            // Si el corte matutino (inicio-dia) no envió part_number, pero el producto ya existe en la BD local:
            if ($producto && empty($partNumber) && !empty($producto->partNumber)) {
                $partNumber = $producto->partNumber;
            }

            if ($producto) {
                // Actualizar timestamp en DetalleProducto
                \App\Models\Catalogo\DetalleProducto::where('idProducto', $producto->idProducto)->update(['updated_at' => now()]);

                // Actualizar o crear registro en Inventario_Proveedor
                $invProveedor = Inventario_Proveedor::updateOrCreate(
                    [
                        'idProducto' => $producto->idProducto,
                        'idProveedor' => $this->idProveedor,
                    ],
                    [
                        'stock' => $stock,
                        'estado' => 1
                    ]
                );

                // Almacenar SKU y Mini Código del proveedor en la tabla relacional
                if (!empty($sku)) {
                    $skuValues = [
                        'sku_proveedor' => strtoupper(trim($sku)),
                        'part_number' => $partNumber ?: $producto->partNumber,
                    ];
                    $miniCodeItem = !empty($item['mini_code']) ? trim($item['mini_code']) : (!empty($item['mini_codigo']) ? trim($item['mini_codigo']) : null);
                    if (!empty($miniCodeItem)) {
                        $skuValues['mini_codigo'] = $miniCodeItem;
                    }
                    \App\Models\Inventario\InventarioProveedorSku::updateOrCreate(
                        [
                            'idInventarioProveedor' => $invProveedor->idInventarioProveedor,
                            'idProveedor' => $this->idProveedor,
                        ],
                        $skuValues
                    );
                }

                // NO modificamos precioDolar de Producto para preservar intactos los precios publicados en la tienda web

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
                // Si el producto no está en el catálogo pero tiene stock destacado o mercadería en tránsito
                if ($stock >= 50 || $transito >= 20) {
                    $pnRec = $partNumber;
                    if (empty($pnRec) && !empty($sku)) {
                        $skuPrevio = \App\Models\Inventario\InventarioProveedorSku::where('sku_proveedor', $sku)->first();
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
                        'imagenes' => $imagenes,
                        'especificaciones' => $especificaciones
                    ];
                }
            }
        }

        return [
            'count' => $updatedCount,
            'detalles' => $detallesActualizados,
            'recomendados' => $recomendados
        ];
    }
}
