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
     * Busca reclamos/devoluciones vinculados a una orden.
     */
    public function getClaims(string $sellerId, string $orderId): array
    {
        try {
            return $this->get($sellerId, '/post-purchase/v1/claims/search', [
                'resource_id'   => $orderId,
                'resource_type' => 'order',
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
    public function getItem(string $itemId): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . "/items/{$itemId}");
            return $response->json() ?? [];
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
                'status' => 'opened',
                'role'   => 'defendant',
            ]);
        } catch (\Throwable $e) {
            Log::warning("ML Claims search error: " . $e->getMessage());
            return ['data' => [], 'paging' => ['total' => 0]];
        }
    }
}
