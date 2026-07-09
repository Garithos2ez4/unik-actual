<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

class FalabellaApiService
{
    public function getProducts(array $filters = []): array
    {
        return $this->sendGetRequest('GetProducts', $filters);
    }

    public function getOrders(array $filters = []): array
    {
        return $this->sendGetRequest('GetOrders', $filters);
    }

    public function getOrderItems(int|string $orderId): array
    {
        return $this->sendGetRequest('GetOrderItems', [
            'OrderId' => $orderId,
        ]);
    }

    /**
     * Obtiene el PDF de etiqueta de envío de Falabella para un OrderItemId.
     * Retorna el contenido binario del PDF.
     */
    public function getDocumentRaw(string|int $orderItemId): string
    {
        $params = [
            'Action'       => 'GetDocument',
            'Format'       => config('services.falabella.format', 'JSON'),
            'Timestamp'    => now('UTC')->toIso8601String(),
            'UserID'       => trim((string) config('services.falabella.user_id')),
            'Version'      => config('services.falabella.version', '1.0'),
            'DocumentType' => 'shippingParcel',
            'OrderItemIds' => json_encode([(int) $orderItemId]),
        ];

        $this->validateCredentials($params);

        $params['Signature'] = $this->generateSignature(
            trim((string) config('services.falabella.api_key')),
            $params
        );

        $response = Http::baseUrl(rtrim(config('services.falabella.base_url'), '/'))
            ->timeout(60)
            ->withHeaders([
                'User-Agent' => 'LogunkApp/1.0 (+https://log.unikstoreperu.com)',
                'Accept' => 'application/json'
            ])
            ->withOptions(['verify' => $this->resolveVerifyOption()])
            ->get('/', $params);

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Falabella HTTP %s al obtener etiqueta (item %s): %s',
                    $response->status(),
                    $orderItemId,
                    $response->body()
                )
            );
        }

        $body = $response->body();

        if (empty($body)) {
            throw new RuntimeException("Falabella devolvió una respuesta vacía para el item $orderItemId.");
        }

        // Check if response is JSON (usually true for Falabella API)
        $json = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($json['ErrorResponse'])) {
                $err = $json['ErrorResponse']['Head']['ErrorMessage'] ?? 'Unknown API Error';
                throw new RuntimeException("Falabella API Error: " . $err);
            }

            $doc = $json['SuccessResponse']['Body']['Documents']['Document'] ?? null;
            if ($doc && isset($doc['File'])) {
                $decoded = base64_decode($doc['File']);
                $mimeType = $doc['MimeType'] ?? 'unknown';

                // Si es un PDF directo
                if (str_starts_with(ltrim($decoded), '%PDF')) {
                    return $decoded;
                }

                // Si Falabella devuelve HTML (común en etiquetas drop-off)
                if ($mimeType === 'text/html' || stripos($decoded, '<html') !== false) {
                    // Convertimos el HTML a un PDF de tamaño A4
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($decoded)
                        ->setPaper('a4', 'portrait');
                    return $pdf->output();
                }

                return $decoded;
            }
        }

        return $body;
    }

    public function extractProducts(array $payload): array
    {
        $products = $this->findNode($payload, 'Products');

        if (is_array($products) && array_key_exists('Product', $products)) {
            $products = $products['Product'];
        }

        if (is_array($products) && $this->isAssoc($products)) {
            return [$this->normalizeProduct($products)];
        }

        if (is_array($products)) {
            return array_values(array_map(
                fn(array $product) => $this->normalizeProduct($product),
                array_filter($products, 'is_array')
            ));
        }

        return [];
    }

    public function extractOrders(array $payload): array
    {
        $orders = $this->findNode($payload, 'Orders');

        if (is_array($orders) && array_key_exists('Order', $orders)) {
            $orders = $orders['Order'];
        }

        if (is_array($orders) && $this->isAssoc($orders)) {
            if (array_key_exists('Order', $orders) && is_array($orders['Order'])) {
                $orders = $orders['Order'];
            }

            return [$this->normalizeOrder($orders)];
        }

        if (is_array($orders)) {
            return array_values(array_map(
                function (array $order) {
                    if (array_key_exists('Order', $order) && is_array($order['Order'])) {
                        $order = $order['Order'];
                    }

                    return $this->normalizeOrder($order);
                },
                array_filter($orders, 'is_array')
            ));
        }

        return [];
    }

    public function extractOrderItems(array $payload): array
    {
        $items = $this->findNode($payload, 'OrderItems');

        if (is_array($items) && array_key_exists('OrderItem', $items)) {
            $items = $items['OrderItem'];
        }

        if (is_array($items) && $this->isAssoc($items)) {
            if (array_key_exists('OrderItem', $items) && is_array($items['OrderItem'])) {
                $items = $items['OrderItem'];
            }

            return [$this->normalizeOrderItem($items)];
        }

        if (is_array($items)) {
            return array_values(array_map(
                function (array $item) {
                    if (array_key_exists('OrderItem', $item) && is_array($item['OrderItem'])) {
                        $item = $item['OrderItem'];
                    }

                    return $this->normalizeOrderItem($item);
                },
                array_filter($items, 'is_array')
            ));
        }

        return [];
    }

    public function normalizeProduct(array $product): array
    {
        $businessUnit = $this->extractFirstBusinessUnit($product);

        $status = $this->firstFilled([
            $product['Status'] ?? null,
            $product['status'] ?? null,
            $businessUnit['Status'] ?? null,
            $businessUnit['status'] ?? null,
        ]);

        $stock = $this->firstFilled([
            $product['Quantity'] ?? null,
            $product['quantity'] ?? null,
            $product['Available'] ?? null,
            $product['available'] ?? null,
            $product['Stock'] ?? null,
            $product['stock'] ?? null,
            $businessUnit['Quantity'] ?? null,
            $businessUnit['quantity'] ?? null,
            $businessUnit['Available'] ?? null,
            $businessUnit['available'] ?? null,
            $businessUnit['Stock'] ?? null,
            $businessUnit['stock'] ?? null,
        ]);

        $price = $this->firstFilled([
            $product['Price'] ?? null,
            $product['price'] ?? null,
            $businessUnit['Price'] ?? null,
            $businessUnit['price'] ?? null,
        ]);

        $salePrice = $this->firstFilled([
            $product['SpecialPrice'] ?? null,
            $product['special_price'] ?? null,
            $businessUnit['SpecialPrice'] ?? null,
            $businessUnit['special_price'] ?? null,
        ]);

        $creationDateRaw = $this->firstFilled([
            $product['SpecialFromDate'] ?? null,
            $businessUnit['SpecialFromDate'] ?? null,
        ]);
        
        $creationDate = '-';
        if ($creationDateRaw) {
            try {
                $creationDate = \Carbon\Carbon::parse($creationDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                $creationDate = '-';
            }
        }

        $product['_normalized'] = [
            'seller_sku' => $this->firstFilled([
                $product['SellerSku'] ?? null,
                $product['SkuSeller'] ?? null,
                $product['seller_sku'] ?? null,
                $businessUnit['SellerSku'] ?? null,
                $product['Sku'] ?? null,
            ], '-'),
            'falabella_sku' => $this->firstFilled([
                $product['ShopSku'] ?? null,
                $businessUnit['ShopSku'] ?? null,
                $product['falabella_sku'] ?? null,
            ], '-'),
            'name' => $this->firstFilled([
                $product['Name'] ?? null,
                $product['name'] ?? null,
            ], '-'),
            'status' => $status ?? '-',
            'stock' => $stock ?? '-',
            'price' => $price ?? '-',
            'sale_price' => $salePrice ?? '-',
            'creation_date' => $creationDate,
        ];

        return $product;
    }

    public function normalizeOrder(array $order): array
    {
        $statuses = $order['Statuses'] ?? $order['statuses'] ?? [];
        $statusList = [];

        if (is_array($statuses)) {
            if (array_key_exists('Status', $statuses)) {
                $statuses = $statuses['Status'];
            }

            if (is_array($statuses)) {
                $statusList = $this->isAssoc($statuses) ? array_values($statuses) : $statuses;
            }
        } elseif ($statuses !== null && $statuses !== '') {
            $statusList = [$statuses];
        }

        $statusList = array_values(array_filter(array_map(function ($status) {
            if (is_array($status)) {
                return $status['Status'] ?? $status['status'] ?? null;
            }

            return $status;
        }, $statusList), fn($value) => $value !== null && $value !== ''));

        $addressShipping = $order['AddressShipping'] ?? $order['address_shipping'] ?? [];

        $order['_normalized'] = [
            'order_id' => $this->firstFilled([$order['OrderId'] ?? null, $order['order_id'] ?? null]),
            'order_number' => $this->firstFilled([$order['OrderNumber'] ?? null, $order['order_number'] ?? null]),
            'customer_name' => trim((string) $this->firstFilled([
                ($order['CustomerFirstName'] ?? '') . ' ' . ($order['CustomerLastName'] ?? ''),
                $order['CustomerName'] ?? null,
            ], '')),
            'status' => $statusList[0] ?? null,
            'statuses' => $statusList,
            'price' => $this->firstFilled([$order['Price'] ?? null, $order['price'] ?? null]),
            'payment_method' => $this->firstFilled([$order['PaymentMethod'] ?? null, $order['payment_method'] ?? null]),
            'created_at' => $this->firstFilled([$order['CreatedAt'] ?? null, $order['created_at'] ?? null]),
            'updated_at' => $this->firstFilled([$order['UpdatedAt'] ?? null, $order['updated_at'] ?? null]),
            'promised_shipping_time' => $this->firstFilled([$order['PromisedShippingTime'] ?? null, $order['promised_shipping_time'] ?? null]),
            'items_count' => $this->firstFilled([$order['ItemsCount'] ?? null, $order['items_count'] ?? null], 0),
            'shipping_type' => $this->firstFilled([$order['ShippingType'] ?? null, $order['shipping_type'] ?? null]),
            'delivery_info' => $this->firstFilled([$order['DeliveryInfo'] ?? null, $order['delivery_info'] ?? null]),
            'customer_email' => $this->firstFilled([$order['CustomerEmail'] ?? null, $order['customer_email'] ?? null]),
            'city' => $this->firstFilled([$addressShipping['City'] ?? null, $addressShipping['city'] ?? null]),
            'address' => $this->firstFilled([$addressShipping['Address1'] ?? null, $addressShipping['address1'] ?? null]),
        ];

        return $order;
    }

    public function normalizeOrderItem(array $item): array
    {
        $item['_normalized'] = [
            'order_item_id' => $this->firstFilled([$item['OrderItemId'] ?? null, $item['order_item_id'] ?? null]),
            'order_id' => $this->firstFilled([$item['OrderId'] ?? null, $item['order_id'] ?? null]),
            'order_number' => $this->firstFilled([$item['OrderNumber'] ?? null, $item['order_number'] ?? null]),
            'seller_sku' => $this->firstFilled([
                $item['SellerSku'] ?? null, 
                $item['SkuSeller'] ?? null, 
                $item['seller_sku'] ?? null,
                $item['Sku'] ?? null
            ], '-'),
            'falabella_sku' => $this->firstFilled([
                $item['ShopSku'] ?? null,
                $item['falabella_sku'] ?? null,
            ], '-'),
            'shop_sku' => $this->firstFilled([$item['ShopSku'] ?? null, $item['shop_sku'] ?? null]),
            'name' => $this->firstFilled([$item['Name'] ?? null, $item['name'] ?? null], '-'),
            'status' => $this->firstFilled([$item['Status'] ?? null, $item['status'] ?? null], '-'),
            'price' => $this->firstFilled([$item['PaidPrice'] ?? null, $item['Price'] ?? null, $item['price'] ?? null]),
            'quantity' => $this->firstFilled([$item['Quantity'] ?? null, $item['quantity'] ?? null], 1),
            'tracking_code' => $this->firstFilled([$item['TrackingCode'] ?? null, $item['tracking_code'] ?? null]),
            'package_id' => $this->firstFilled([$item['PackageId'] ?? null, $item['package_id'] ?? null]),
        ];

        return $item;
    }

    private function validateCredentials(array $params): void
    {
        if (blank($params['UserID'] ?? null) || blank(config('services.falabella.api_key'))) {
            throw new InvalidArgumentException(
                'Configura FALABELLA_USER_ID y FALABELLA_API_KEY en el archivo .env antes de consumir la API.'
            );
        }
    }

    private function generateSignature(string $apiKey, array $params): string
    {
        unset($params['Signature']);
        ksort($params);

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return hash_hmac('sha256', $query, $apiKey);
    }

    private function formatResponse(Response $response): array
    {
        if ($response->failed()) {
            throw new RuntimeException(
                sprintf('Falabella devolvio HTTP %s: %s', $response->status(), $response->body())
            );
        }

        $contentType = strtolower($response->header('Content-Type', ''));

        if (str_contains($contentType, 'json')) {
            return $response->json() ?? [];
        }

        if (str_contains($contentType, 'xml') || str_starts_with(trim($response->body()), '<')) {
            $xml = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NOCDATA);

            if ($xml === false) {
                throw new RuntimeException('No se pudo interpretar la respuesta XML de Falabella.');
            }

            return json_decode(json_encode($xml), true) ?? [];
        }

        return [
            'raw' => $response->body(),
        ];
    }

    public function sendGetRequest(string $action, array $filters = []): array
    {
        $params = array_merge([
            'Action' => $action,
            'Format' => config('services.falabella.format', 'JSON'),
            'Timestamp' => now('UTC')->toIso8601String(),
            'UserID' => trim((string) config('services.falabella.user_id')),
            'Version' => config('services.falabella.version', '1.0'),
        ], array_filter($filters, static fn($value) => $value !== null && $value !== ''));

        $this->validateCredentials($params);

        $params['Signature'] = $this->generateSignature(
            trim((string) config('services.falabella.api_key')),
            $params
        );

        $response = Http::baseUrl(rtrim(config('services.falabella.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('services.falabella.timeout', 30))
            ->withHeaders([
                'User-Agent' => 'LogunkApp/1.0 (+https://log.unikstoreperu.com)'
            ])
            ->withOptions(['verify' => $this->resolveVerifyOption()])
            ->get('/', $params);

        return $this->formatResponse($response);
    }

    private function resolveVerifyOption(): string|bool
    {
        $verifySsl = config('services.falabella.verify_ssl', true);
        $caBundle = config('services.falabella.ca_bundle');

        if (! $verifySsl) {
            return false;
        }

        if (filled($caBundle)) {
            return $caBundle;
        }

        return true;
    }

    private function findNode(array $payload, string $targetKey): mixed
    {
        foreach ($payload as $key => $value) {
            if ($key === $targetKey) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findNode($value, $targetKey);

                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function extractFirstBusinessUnit(array $product): array
    {
        $units = $product['BusinessUnits'] ?? $product['business_units'] ?? null;

        if (! is_array($units)) {
            return [];
        }

        if (array_key_exists('BusinessUnit', $units)) {
            $units = $units['BusinessUnit'];
        }

        if (! is_array($units)) {
            return [];
        }

        if ($this->isAssoc($units)) {
            return $units;
        }

        foreach ($units as $unit) {
            if (is_array($unit)) {
                return $unit;
            }
        }

        return [];
    }

    private function firstFilled(array $values, mixed $default = null): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}
