<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RipleyApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('RIPLEY_API_URL', 'https://ripleyperu.mirakl.net/api'), '/');
        $this->apiKey = env('RIPLEY_API_KEY', '');
    }

    /**
     * @param array $params (e.g. ['paginate' => false, 'order_state' => 'WAITING_ACCEPTANCE'])
     */
    public function getOrders(array $params = []): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException("Ripley API Key is not configured.");
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withoutVerifying()
            ->withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->get('/orders', $params);

        if ($response->failed()) {
            throw new RuntimeException("Ripley HTTP Error: " . $response->status() . " - " . $response->body());
        }

        $data = $response->json();
        
        return $data['orders'] ?? [];
    }

    /**
     * Descarga los documentos (etiquetas/manifiestos) para uno o varios pedidos.
     * Retorna el contenido binario crudo del PDF o ZIP (dependiendo de Mirakl).
     *
     * @param array $orderIds Array de strings con los order_ids.
     * @return array ['body' => string, 'content_type' => string]
     */
    public function getDocumentsRaw(array $orderIds): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException("Ripley API Key is not configured.");
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withoutVerifying()
            ->withHeaders([
                'Authorization' => $this->apiKey,
                // Accept header para archivos
                'Accept' => 'application/octet-stream, application/zip, application/pdf',
            ])
            ->timeout(120)
            ->get('/orders/documents/download', [
                'order_ids' => implode(',', $orderIds)
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Error Ripley API Descarga Documentos: " . $response->status() . " - " . $response->body());
        }

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type') ?? 'application/zip'
        ];
    }
}
