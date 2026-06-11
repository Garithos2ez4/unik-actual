<?php

namespace App\Services;

use App\Models\FalabellaOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FalabellaOrderSyncService
{
    private const DEFAULT_LOOKBACK_DAYS = 3;

    public const RETURN_STATUSES = [
        'canceled',
        'returned',
        'return_waiting_for_approval',
        'return_shipped_by_customer',
        'return_delivered_to_seller',
    ];

    public function __construct(
        protected FalabellaApiService $falabellaApiService
    ) {}

    /**
     * Sincroniza una sola orden y sus items.
     */
    public function syncSingleOrder(array $orderPayload, ?string $syncDate = null): ?FalabellaOrder
    {
        $normalized = $orderPayload['_normalized'] ?? [];
        $orderId = (string) ($normalized['order_id'] ?? '');

        if ($orderId === '') return null;

        $order = FalabellaOrder::updateOrCreate(
            ['order_id' => $orderId],
            [
                'order_number'          => $normalized['order_number'] ?? null,
                'customer_name'         => $normalized['customer_name'] ?: null,
                'customer_email'        => $normalized['customer_email'] ?: null,
                'status'                => $normalized['status'] ?: null,
                'statuses'              => $normalized['statuses'] ?? [],
                'price'                 => $normalized['price'] ?? null,
                'payment_method'        => $normalized['payment_method'] ?: null,
                'shipping_type'         => $normalized['shipping_type'] ?: null,
                'delivery_info'         => $normalized['delivery_info'] ?: null,
                'items_count'           => (int) ($normalized['items_count'] ?? 0),
                'created_at_falabella'  => $this->parseDate($normalized['created_at'] ?? null),
                'updated_at_falabella'  => $this->parseDate($normalized['updated_at'] ?? null),
                'promised_shipping_time' => $this->parseDate($normalized['promised_shipping_time'] ?? null),
                'shipping_city'         => $normalized['city'] ?: null,
                'shipping_address'      => $normalized['address'] ?: null,
                'payload'               => $orderPayload,
                'synced_at'             => now(),
                'sync_date'             => $syncDate ?: now()->toDateString(),
            ]
        );

        // Sincronizar items de esta orden
        try {
            $itemsResponse = $this->falabellaApiService->getOrderItems((string) $order->order_id);
            $items = $this->falabellaApiService->extractOrderItems($itemsResponse);

            foreach ($items as $itemPayload) {
                $item = $itemPayload['_normalized'] ?? [];
                $sellerSku = $item['seller_sku'] ?? null;
                $falabellaSku = $item['falabella_sku'] ?? null;

                // Intento de rescate de SKU numérico si no viene
                if ($sellerSku && (!is_numeric($falabellaSku) || blank($falabellaSku) || $falabellaSku === '-')) {
                    try {
                        $productResponse = $this->falabellaApiService->getProducts([
                            'SkuSellerList' => json_encode([$sellerSku])
                        ]);
                        $apiProducts = $this->falabellaApiService->extractProducts($productResponse);

                        if (!empty($apiProducts)) {
                            $normalizedProd = $apiProducts[0]['_normalized'] ?? [];
                            $skuLimpio = trim((string) ($normalizedProd['falabella_sku'] ?? '-'));
                            if ($skuLimpio !== '-' && is_numeric($skuLimpio)) {
                                $falabellaSku = $skuLimpio;
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Error obteniendo ShopSku para {$sellerSku}: " . $e->getMessage());
                    }
                }

                $order->items()->updateOrCreate(
                    ['order_item_id' => (string) ($item['order_item_id'] ?? '')],
                    [
                        'order_id'      => (string) ($item['order_id'] ?? $order->order_id),
                        'order_number'  => $item['order_number'] ?? $order->order_number,
                        'seller_sku'    => $sellerSku,
                        'falabella_sku' => $falabellaSku,
                        'shop_sku'      => $falabellaSku,
                        'name'          => $item['name'] ?? null,
                        'status'        => $item['status'] ?? null,
                        'price'         => $item['price'] ?? null,
                        'quantity'      => (int) ($item['quantity'] ?? 1),
                        'tracking_code' => $item['tracking_code'] ?? null,
                        'package_id'    => $item['package_id'] ?? null,
                        'payload'       => $itemPayload,
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error sincronizando items de orden {$orderId}: " . $e->getMessage());
        }

        return $order;
    }

    public function syncOrdersByDate(string $date, ?string $status = null): array
    {
        $day = Carbon::parse($date);
        $createdAfter = $day->copy()->startOfDay()->toIso8601String();
        $createdBefore = $day->copy()->endOfDay()->toIso8601String();

        $response = $this->falabellaApiService->getOrders([
            'CreatedAfter' => $createdAfter,
            'CreatedBefore' => $createdBefore,
            'Limit' => 100,
            'Status' => $status ?: null,
        ]);

        $orders = $this->falabellaApiService->extractOrders($response);
        $syncedOrders = [];

        DB::transaction(function () use ($orders, $date, &$syncedOrders) {
            foreach ($orders as $orderPayload) {
                $order = $this->syncSingleOrder($orderPayload, $date);
                if ($order) {
                    $syncedOrders[] = $order->order_id;
                }
            }
        });

        return [
            'count' => count(array_unique($syncedOrders)),
            'order_ids' => array_values(array_unique($syncedOrders)),
            'raw_response' => $response,
        ];
    }

    public function syncDispatchQueue(string $date, ?string $status = null, int $lookbackDays = self::DEFAULT_LOOKBACK_DAYS): array
    {
        $lookbackDays = max(0, config('services.falabella.sync_lookback_days', $lookbackDays));
        $targetDate = Carbon::parse($date);
        $orderIds = [];
        $totalCount = 0;

        for ($offset = $lookbackDays; $offset >= 0; $offset--) {
            $syncDate = $targetDate->copy()->subDays($offset)->toDateString();
            $result = $this->syncOrdersByDate($syncDate, $status);

            $orderIds = array_merge($orderIds, $result['order_ids']);
            $totalCount += $result['count'];
        }

        // Paso 2: Re-verificar órdenes locales que siguen como pending/ready_to_ship
        // pero que podrían haber cambiado a shipped/canceled en Falabella
        $staleRefreshed = $this->refreshStaleOrders();
        $orderIds = array_merge($orderIds, $staleRefreshed);

        return [
            'count' => count(array_unique($orderIds)),
            'synced_rows' => $totalCount,
            'order_ids' => array_values(array_unique($orderIds)),
            'lookback_days' => $lookbackDays,
        ];
    }

    public function getOrdersByDate(string $date, ?string $status = null)
    {
        $limitDate = Carbon::parse($date)->endOfDay();
        $query = FalabellaOrder::with('items')
            ->whereNotNull('promised_shipping_time')
            ->where('promised_shipping_time', '<=', $limitDate);

        if (filled($status) && $status === 'pending') {
            // "Pendientes" agrupa todo lo que falta despachar (igual que Falabella)
            $query->whereIn('status', ['pending', 'ready_to_ship']);
        } elseif (filled($status) && $status !== 'all') {
            $query->where('status', $status);
        } elseif (!filled($status)) {
            $query->whereIn('status', ['pending', 'ready_to_ship']);
        }
        // Si $status === 'all', no filtramos por estado

        return $query->orderBy('promised_shipping_time')->orderBy('created_at_falabella')->get();
    }

    /**
     * Re-consulta órdenes locales que siguen como pending/ready_to_ship
     * pero que están FUERA del lookback window (ya que el lookback las habría
     * actualizado si estuvieran dentro del rango). Solo hace 1 llamada API
     * por fecha única de creación, agrupando órdenes del mismo día.
     */
    private function refreshStaleOrders(int $lookbackDays = self::DEFAULT_LOOKBACK_DAYS): array
    {
        $lookbackCutoff = now()->subDays($lookbackDays)->startOfDay();

        // Solo órdenes fuera del rango del lookback (las del rango ya fueron actualizadas)
        $staleOrders = FalabellaOrder::whereIn('status', ['pending', 'ready_to_ship'])
            ->whereNotNull('created_at_falabella')
            ->where('created_at_falabella', '<', $lookbackCutoff)
            ->get();

        if ($staleOrders->isEmpty()) {
            return [];
        }

        $refreshedIds = [];

        // Agrupar por fecha de creación (YYYY-MM-DD) para minimizar llamadas API
        $byDate = $staleOrders->groupBy(fn ($o) => Carbon::parse($o->created_at_falabella)->toDateString());

        foreach ($byDate as $dateStr => $ordersOnDate) {
            try {
                $day = Carbon::parse($dateStr);
                $response = $this->falabellaApiService->getOrders([
                    'CreatedAfter'  => $day->copy()->startOfDay()->toIso8601String(),
                    'CreatedBefore' => $day->copy()->endOfDay()->toIso8601String(),
                    'Limit'         => 50,
                ]);

                $apiOrders = $this->falabellaApiService->extractOrders($response);

                // Indexar por order_id para búsqueda O(1)
                $apiIndexed = collect($apiOrders)->keyBy(fn ($o) => (string)($o['_normalized']['order_id'] ?? ''));

                foreach ($ordersOnDate as $localOrder) {
                    $apiOrder = $apiIndexed->get((string)$localOrder->order_id);
                    if ($apiOrder) {
                        $synced = $this->syncSingleOrder($apiOrder, $localOrder->sync_date);
                        if ($synced) {
                            $refreshedIds[] = $synced->order_id;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    "Error refrescando órdenes del {$dateStr}: " . $e->getMessage()
                );
            }
        }

        return $refreshedIds;
    }


    /**
     * Sincroniza devoluciones desde la API de Falabella para un rango de fechas.
     * Hace 1 llamada por estado (rango completo) en lugar de 1 por día por estado.
     */
    public function syncReturnsByDateRange(string $dateFrom, string $dateTo): array
    {
        $createdAfter  = Carbon::parse($dateFrom)->startOfDay()->toIso8601String();
        $createdBefore = Carbon::parse($dateTo)->endOfDay()->toIso8601String();
        $synced        = [];

        foreach (self::RETURN_STATUSES as $returnStatus) {
            try {
                // Usar UpdatedAfter porque devoluciones se inician después de la creación de la orden
                $response  = $this->falabellaApiService->getOrders([
                    'UpdatedAfter'  => $createdAfter,
                    'UpdatedBefore' => $createdBefore,
                    'Status'        => $returnStatus,
                    'Limit'         => 100,
                ]);

                $apiOrders = $this->falabellaApiService->extractOrders($response);

                DB::transaction(function () use ($apiOrders, $dateFrom, &$synced) {
                    foreach ($apiOrders as $orderPayload) {
                        $order = $this->syncSingleOrder($orderPayload, $dateFrom);
                        if ($order) {
                            $synced[] = $order->order_id;
                        }
                    }
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    "Error sincronizando devoluciones [{$returnStatus}]: " . $e->getMessage()
                );
            }
        }

        return [
            'count'     => count(array_unique($synced)),
            'order_ids' => array_values(array_unique($synced)),
        ];
    }

    /**
     * Obtiene las devoluciones de la BD local filtradas por rango de fechas y estado.
     */
    public function getReturns(string $dateFrom, string $dateTo, ?string $status = null)
    {
        $query = FalabellaOrder::with('items')
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at_falabella', [
                    Carbon::parse($dateFrom)->startOfDay(),
                    Carbon::parse($dateTo)->endOfDay(),
                ])->orWhereBetween('updated_at_falabella', [
                    Carbon::parse($dateFrom)->startOfDay(),
                    Carbon::parse($dateTo)->endOfDay(),
                ]);
            });

        if (filled($status)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', self::RETURN_STATUSES);
        }

        return $query->orderByDesc('updated_at_falabella')->get();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value);
    }
}
