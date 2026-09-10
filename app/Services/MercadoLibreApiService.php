<?php

namespace App\Services;

use App\Models\Ecommerce\MercadoLibreCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MercadoLibreApiService
{
    protected string $baseUrl   = 'https://api.mercadolibre.com';
    protected string $authUrl   = 'https://auth.mercadolibre.com.pe';
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId     = config('services.mercadolibre.client_id', '');
        $this->clientSecret = config('services.mercadolibre.client_secret', '');
        $this->redirectUri  = config('services.mercadolibre.redirect_uri', '');
    }

    // ─── OAuth ─────────────────────────────────────────────────────────

    public function getAuthUrl(): string
    {
        return $this->authUrl . '/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => 'offline_access',
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::post($this->baseUrl . '/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('ML OAuth error: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(MercadoLibreCredential $credential): MercadoLibreCredential
    {
        $response = Http::post($this->baseUrl . '/oauth/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $credential->refresh_token,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('ML Refresh Token error: ' . $response->body());
        }

        $data = $response->json();

        $credential->update([
            'access_token'    => $data['access_token'],
            'refresh_token'   => $data['refresh_token'] ?? $credential->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 21600),
        ]);

        return $credential->fresh();
    }

    // ─── Token Helper ──────────────────────────────────────────────────

    protected function getToken(string $sellerId): string
    {
        $credential = MercadoLibreCredential::where('seller_id', $sellerId)->first();

        if (!$credential) {
            throw new RuntimeException("No hay credenciales configuradas para el seller_id: {$sellerId}");
        }

        if ($credential->isExpired()) {
            $credential = $this->refreshAccessToken($credential);
        }

        return $credential->access_token;
    }

    protected function get(string $sellerId, string $path, array $params = []): array
    {
        $token = $this->getToken($sellerId);

        $response = Http::withToken($token)
            ->timeout(30)
            ->get($this->baseUrl . $path, $params);

        if ($response->failed()) {
            Log::warning("ML API Error [{$path}]: " . $response->status() . ' - ' . $response->body());
            throw new RuntimeException("ML API Error {$response->status()}: " . $response->body());
        }

        return $response->json() ?? [];
    }

    // ─── Endpoints ─────────────────────────────────────────────────────

    /**
     * Busca órdenes del seller.
     * @param string $sellerId
     * @param array $params  (offset, limit, order.status, etc.)
     */
    public function getOrders(string $sellerId, array $params = []): array
    {
        $defaults = [
            'seller'        => $sellerId,
            'order.status'  => 'paid',
            'limit'         => 50,
            'offset'        => 0,
        ];

        return $this->get($sellerId, '/orders/search', array_merge($defaults, $params));
    }

    /**
     * Busca productos en todo el catálogo de ML.
     */
    public function searchListings(string $sellerId, array $params = []): array
    {
        return $this->get($sellerId, '/sites/MPE/search', $params);
    }

    /**
     * Busca productos específicos dentro del catálogo del seller.
     */
    public function searchSellerItems(string $sellerId, string $query): array
    {
        return $this->get($sellerId, "/users/{$sellerId}/items/search", ['query' => $query]);
    }

    /**
     * Obtiene los IDs de ítems con referencias de precios para un seller.
     */
    public function getSuggestionItems(string $sellerId): array
    {
        return $this->get($sellerId, "/suggestions/user/{$sellerId}/items");
    }

    /**
     * Obtiene el detalle de referencia de precios para un ítem específico.
     * Retorna null si el ítem no tiene referencia (404).
     */
    public function getSuggestionDetail(string $sellerId, string $itemId): ?array
    {
        $token = $this->getToken($sellerId);

        $response = Http::withToken($token)
            ->timeout(10)
            ->get($this->baseUrl . "/suggestions/items/{$itemId}/details");

        if ($response->status() === 404) {
            return null; // Normal: ítem sin vecinos o eliminado
        }

        if ($response->failed()) {
            Log::warning("ML Suggestion Error [{$itemId}]: {$response->status()}");
            return null;
        }

        return $response->json() ?? null;
    }

    /**
     * Detalle de una orden específica.
     */
    public function getOrder(string $sellerId, string $orderId): array
    {
        return $this->get($sellerId, "/orders/{$orderId}");
    }

    /**
     * Detalle del envío (aquí está logistic_type, carrier, tracking, fechas).
     */
    public function getShipment(string $sellerId, string $shippingId): array
    {
        return $this->get($sellerId, "/shipments/{$shippingId}");
    }

    /**
     * Costos de un envío (seller, receiver, promoted).
     */
    public function getShipmentCosts(string $sellerId, string $shippingId): array
    {
        return $this->get($sellerId, "/shipments/{$shippingId}/costs");
    }

    public function downloadShipmentLabel(string $sellerId, $shipmentIds): string
    {
        $token = $this->getToken($sellerId);

        // ML permite hasta 50 ids separados por coma
        $idsParam = is_array($shipmentIds) ? implode(',', $shipmentIds) : $shipmentIds;

        $response = Http::withToken($token)
            ->timeout(30)
            ->get($this->baseUrl . "/shipment_labels?shipment_ids={$idsParam}&response_type=pdf");

        if ($response->failed()) {
            $errorBody = $response->body();
            $errorJson = json_decode($errorBody, true);
            $message = $errorJson['message'] ?? $errorBody;
            throw new RuntimeException("Error al descargar etiqueta(s): " . $message);
        }

        return $response->body();
    }

    /**
     * Busca reclamos/devoluciones vinculados a una orden.
     */
    public function getClaims(string $sellerId, string $orderId): array
    {
        try {
            return $this->get($sellerId, '/post-purchase/v1/claims/search', [
                'resource'    => 'order',
                'resource_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("ML Claims error para orden {$orderId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Info básica del seller autenticado (para guardar seller_id y nickname).
     */
    public function getMe(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->get($this->baseUrl . '/users/me');

        if ($response->failed()) {
            throw new RuntimeException('ML /users/me error: ' . $response->body());
        }

        return $response->json() ?? [];
    }

    // ─── Mapeo logistic_type ───────────────────────────────────────────

    public static function mapLogisticLabel(string $logisticType): string
    {
        return match (strtolower($logisticType)) {
            'fulfillment'   => 'ME Full',
            'self_service'  => 'Flex',
            'xd_drop_off'   => 'Urbano (Drop-off)',
            'drop_off'      => 'Punto de entrega',
            'custom'        => 'Acuerdo de entrega',
            default         => 'Sin especificar',
        };
    }

    // ─── Preguntas y Respuestas ───────────────────────────────────────

    /**
     * Obtiene preguntas sin responder del seller.
     */
    public function getUnansweredQuestions(string $sellerId, int $limit = 20): array
    {
        try {
            return $this->get($sellerId, '/my/received_questions/search', [
                'status' => 'UNANSWERED',
                'api_version' => '4',
                'limit' => $limit,
                'sort_fields' => 'date_created',
                'sort_types'  => 'DESC',
            ]);
        } catch (\Throwable $e) {
            Log::warning("ML Questions error: " . $e->getMessage());
            return ['total' => 0, 'questions' => []];
        }
    }

    /**
     * Responde una pregunta.
     */
    public function answerQuestion(string $sellerId, int $questionId, string $text): array
    {
        $token = $this->getToken($sellerId);

        $response = Http::withToken($token)
            ->timeout(15)
            ->post($this->baseUrl . '/answers', [
                'question_id' => $questionId,
                'text'        => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("ML Answer error: " . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Obtiene info de un item de ML (para obtener el título del producto).
     */
    public function getItem(string $sellerId, string $itemId): array
    {
        try {
            return $this->get($sellerId, "/items/{$itemId}");
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Busca reclamos/mediaciones abiertas del seller.
     */
    public function getOpenClaims(string $sellerId): array
    {
        try {
            return $this->get($sellerId, '/post-purchase/v1/claims/search', [
                'status'         => 'opened',
                'player_role'    => 'defendant',
                'player_user_id' => $sellerId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("ML Claims search error: " . $e->getMessage());
            return ['data' => [], 'paging' => ['total' => 0]];
        }
    }
    /**
     * Actualiza el estado de un envío personalizado (ME1) en Mercado Libre (API V2).
     */
    public function updateCustomShipmentStatus(string $sellerId, string $shipmentId, string $status, ?string $substatus = null, array $payloadData = [], ?string $trackingNumber = null, ?string $trackingUrl = null): array
    {
        $body = [
            'status' => $status,
            'substatus' => $substatus,
            'payload' => array_merge([
                'service_id' => 361180, // Por defecto MPE (Perú)
                'date' => now()->toISOString(),
            ], $payloadData)
        ];

        if ($trackingNumber && $trackingUrl) {
            $body['tracking_number'] = $trackingNumber;
            $body['tracking_url'] = $trackingUrl;
        }

        $token = $this->getToken($sellerId);

        $response = Http::withToken($token)
            ->timeout(15)
            ->post($this->baseUrl . "/v2/shipments/{$shipmentId}/seller_notifications", $body);

        if ($response->failed()) {
            throw new \Exception("ML Update Shipment Status error: " . $response->body());
        }

        return $response->json();
    }
    /**
     * Obtiene las suscripciones de Flex/Turbo de un usuario.
     */
    public function getFlexSubscriptions(string $sellerId, string $siteId = 'MPE'): array
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/subscriptions/v1");

        if ($response->failed()) {
            Log::error("Error obteniendo suscripciones Flex: " . $response->body());
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Extrae dinámicamente el service_id para la modalidad FLEX.
     */
    public function getFlexServiceId(string $sellerId, string $siteId = 'MPE'): ?string
    {
        $subscriptions = $this->getFlexSubscriptions($sellerId, $siteId);
        foreach ($subscriptions as $sub) {
            if (isset($sub['mode']) && $sub['mode'] === 'FLEX' && isset($sub['service_id'])) {
                return (string) $sub['service_id'];
            }
        }
        return null;
    }

    /**
     * Actualiza los rangos de entrega de Flex.
     */
    public function updateFlexDeliveryRanges(string $sellerId, string $siteId, string $serviceId, array $payload): array
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(15)
            ->put($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/services/{$serviceId}/configurations/delivery-ranges/v1", $payload);

        if ($response->failed()) {
            throw new \Exception("ML Update Flex Delivery Ranges error: " . $response->body());
        }

        return $response->status() === 204 ? [] : ($response->json() ?? []);
    }

    /**
     * Consulta los rangos de entrega Flex.
     */
    public function getFlexDeliveryRanges(string $sellerId, string $siteId, string $serviceId, bool $showAvailables = false): array
    {
        $token = $this->getToken($sellerId);
        $query = $showAvailables ? '?show_availables=true' : '';
        $response = Http::withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/services/{$serviceId}/configurations/delivery-ranges/v1" . $query);

        if ($response->failed()) {
            Log::error("Error obteniendo rangos de entrega Flex ML: " . $response->body());
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Actualiza las zonas de cobertura Flex.
     */
    public function updateFlexCoverageZones(string $sellerId, string $siteId, string $serviceId, array $payload): array
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(15)
            ->put($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/services/{$serviceId}/configurations/coverage/zones/v1", $payload);

        if ($response->failed()) {
            throw new \Exception("ML Update Flex Coverage Zones error: " . $response->body());
        }

        return $response->status() === 204 ? [] : ($response->json() ?? []);
    }

    /**
     * Consulta los días festivos configurados para Flex.
     */
    public function getFlexHolidays(string $sellerId, string $siteId, string $serviceId): array
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(15)
            ->get($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/services/{$serviceId}/configurations/holidays/v1");

        if ($response->failed()) {
            Log::error("Error obteniendo feriados Flex ML: " . $response->body());
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Actualiza los días festivos de Flex.
     */
    public function updateFlexHolidays(string $sellerId, string $siteId, string $serviceId, array $payload): array
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(15)
            ->put($this->baseUrl . "/flex/sites/{$siteId}/users/{$sellerId}/services/{$serviceId}/configurations/holidays/v1", $payload);

        if ($response->failed()) {
            throw new \Exception("ML Update Flex Holidays error: " . $response->body());
        }

        return $response->status() === 204 ? [] : ($response->json() ?? []);
    }

    /**
     * Verifica si una categoría soporta envíos Flex.
     */
    public function categoryAllowsFlex(string $sellerId, string $categoryId): bool
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(10)
            ->get($this->baseUrl . "/categories/{$categoryId}/shipping_preferences");

        if ($response->failed()) {
            Log::warning("No se pudo obtener las preferencias de envío de la categoría {$categoryId}: " . $response->body());
            return false;
        }

        $data = $response->json();
        $logistics = $data['logistics'] ?? [];

        foreach ($logistics as $logistic) {
            if (isset($logistic['types']) && in_array('self_service', $logistic['types'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Consulta si el ítem actualmente se está ofreciendo con Envíos Flex o no.
     */
    public function checkItemHasFlex(string $sellerId, string $siteId, string $itemId): bool
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->timeout(10)
            ->get($this->baseUrl . "/flex/sites/{$siteId}/items/{$itemId}/v2");

        if ($response->failed()) {
            Log::warning("Error verificando Flex en ítem {$itemId}: " . $response->body());
            return false;
        }

        $data = $response->json();
        return $data['has_flex'] ?? false;
    }

    /**
     * Permite activar la opción de Envíos Flex al ítem.
     */
    public function enableFlexForItem(string $sellerId, string $siteId, string $itemId): bool
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->timeout(10)
            ->post($this->baseUrl . "/flex/sites/{$siteId}/items/{$itemId}/v2");

        if ($response->failed()) {
            Log::error("Error activando Flex en ítem {$itemId}: " . $response->body());
            return false;
        }

        return $response->status() === 204;
    }

    /**
     * Permite desactivar la opción de Envíos Flex al ítem.
     */
    public function disableFlexForItem(string $sellerId, string $siteId, string $itemId): bool
    {
        $token = $this->getToken($sellerId);
        $response = Http::withToken($token)
            ->withoutVerifying()
            ->timeout(10)
            ->delete($this->baseUrl . "/flex/sites/{$siteId}/items/{$itemId}/v2");

        if ($response->failed()) {
            Log::error("Error desactivando Flex en ítem {$itemId}: " . $response->body());
            return false;
        }

        return $response->status() === 204;
    }

    /**
     * Permite que las mensajerías envíen los shipments que gestionan.
     */
    public function registerCourierShipment(string $courierToken, string $siteId, string $courierUserId, string $shipmentId): bool
    {
        // NOTA: Para este endpoint, el access_token provisto es el de la cuenta de la mensajería,
        // obtenido vía OAuth. Por ende, usamos directamente el token que nos envíen.
        $response = Http::withToken($courierToken)
            ->timeout(10)
            ->post($this->baseUrl . "/flex/sites/{$siteId}/users/{$courierUserId}/courier-shipment/v1", [
                'shipment_id' => (int) $shipmentId
            ]);

        if ($response->failed()) {
            Log::error("Error registrando envío courier para {$shipmentId}: " . $response->body());
            return false;
        }

        return $response->status() === 204;
    }

    /**
     * Consulta el listado de publicaciones (ítems) del vendedor
     */
    /**
     * Obtiene los IDs de las publicaciones del vendedor (Soporta paginación y búsqueda).
     */
    public function getSellerItemsSearch(string $sellerId, int $offset = 0, int $limit = 20, ?string $searchQuery = null): array
    {
        try {
            $token = $this->getToken($sellerId);

            $params = [
                'offset' => $offset,
                'limit'  => $limit
            ];

            // Si el usuario escribió algo en el buscador, lo enviamos a ML
            if (!empty($searchQuery)) {
                $params['query'] = $searchQuery;
            }

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->timeout(30)
                ->get("{$this->baseUrl}/users/{$sellerId}/items/search", $params);

            if ($response->failed()) {
                Log::warning("Error obteniendo ítems del seller {$sellerId}: " . $response->body());
                return ['results' => [], 'paging' => ['total' => 0]];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Excepción en getSellerItemsSearch: " . $e->getMessage());
            return ['results' => [], 'paging' => ['total' => 0]];
        }
    }
    /**
     * Consulta los detalles (multiget) de un listado de IDs de ítems.
     * Mercado Libre soporta hasta 20 IDs separados por coma.
     */
    public function getItemsDetails(string $sellerId, array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        try {
            $token = $this->getToken($sellerId);
            $idsString = implode(',', $itemIds);

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->timeout(30)
                ->get($this->baseUrl . "/items", [
                    'ids' => $idsString
                ]);

            if ($response->failed()) {
                Log::error("Error obteniendo detalles de items: " . $response->body());
                return [];
            }

            $results = $response->json();
            $items = [];

            // El multiget devuelve un array de objetos con formato: { code: 200, body: { ... } }
            foreach ($results as $result) {
                if (isset($result['code']) && $result['code'] === 200 && isset($result['body'])) {
                    $items[] = $result['body'];
                }
            }

            return $items;
        } catch (\Exception $e) {
            Log::error("Excepción en getItemsDetails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el ID del transportista (Driver) para envíos Flex
     */
    public function getShipmentAssignment(string $sellerId, string $shipmentId, string $siteId = 'MPE'): array
    {
        try {
            $token = $this->getToken($sellerId);
            $maxRetries = 3;
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $response = Http::withToken($token)
                    ->withoutVerifying()
                    ->timeout(15)
                    ->get("{$this->baseUrl}/flex/sites/{$siteId}/shipments/{$shipmentId}/assignment/v2");

                if ($response->status() === 429 && $attempt < $maxRetries) {
                    sleep(2); // Wait 2 seconds before retrying
                    continue;
                }

                if ($response->failed()) {
                    if ($response->status() !== 404) {
                        Log::warning("Error obteniendo asignación de envío {$shipmentId}: " . $response->body());
                    }
                    return [];
                }
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Excepción en getShipmentAssignment: " . $e->getMessage());
            return [];
        }
        return [];
    }
}
