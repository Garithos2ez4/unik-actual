<?php

namespace App\Services;

use App\Models\Ecommerce\RipleyOrder;
use App\Models\Ecommerce\RipleyOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RipleyOrderSyncService
{
    public function __construct(
        protected RipleyApiService $ripleyApiService
    ) {}

    public function syncPendingOrders(): int
    {
        $ordersPayload = $this->ripleyApiService->getOrders([
            'order_state_codes' => 'WAITING_ACCEPTANCE,SHIPPING,SHIPPED', // Adjust states as needed
            'paginate' => 'false'
        ]);

        $syncedCount = 0;
        foreach ($ordersPayload as $orderPayload) {
            $this->syncSingleOrder($orderPayload);
            $syncedCount++;
        }

        return $syncedCount;
    }

    public function syncSingleOrder(array $orderPayload): ?RipleyOrder
    {
        $orderId = $orderPayload['order_id'] ?? '';
        if (empty($orderId)) return null;

        $customer = $orderPayload['customer'] ?? [];
        $customerName = trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? ''));

        $attributes = ['order_id' => $orderId];
        $values = [
            'order_number' => $orderId,
            'customer_name' => $customerName ?: null,
            'customer_email' => null, // Mirakl usually hides customer email unless explicit
            'status' => $orderPayload['order_state'] ?? null,
            'statuses' => [$orderPayload['order_state'] ?? ''],
            'price' => $orderPayload['total_price'] ?? 0,
            'payment_method' => $orderPayload['paymentType'] ?? null,
            'shipping_type' => $orderPayload['shipping_type_label'] ?? null,
            'delivery_info' => $orderPayload['shipping_tracking'] ?? null,
            'items_count' => count($orderPayload['order_lines'] ?? []),
            'created_at_ripley' => $this->parseDate($orderPayload['created_date'] ?? null),
            'updated_at_ripley' => $this->parseDate($orderPayload['last_updated_date'] ?? null),
            'promised_shipping_time' => $this->parseDate($orderPayload['shipping_deadline'] ?? null),
            'shipping_city' => $customer['shipping_address']['city'] ?? null,
            'shipping_address' => ($customer['shipping_address']['street_1'] ?? '') . ' ' . ($customer['shipping_address']['street_2'] ?? ''),
            'payload' => $orderPayload,
            'synced_at' => now(),
            'sync_date' => now()->toDateString(),
        ];

        $order = RipleyOrder::updateOrCreate($attributes, $values);

        foreach ($orderPayload['order_lines'] ?? [] as $linePayload) {
            $lineId = $linePayload['order_line_id'] ?? '';
            if (empty($lineId)) continue;

            $lineAttributes = ['order_item_id' => $lineId];
            $productTitle = $linePayload['product_title'] ?? null;
            if (empty($productTitle) || strtolower(trim($productTitle)) === 'n/a') {
                $productTitle = $linePayload['offer_sku'] ?? $linePayload['product_shop_sku'] ?? 'Producto Ripley';
            }

            $lineValues = [
                'order_id' => $orderId,
                'order_number' => $orderId,
                'seller_sku' => $linePayload['offer_sku'] ?? null,
                'ripley_sku' => $linePayload['product_sku'] ?? null,
                'shop_sku' => $linePayload['product_shop_sku'] ?? null,
                'name' => $productTitle,
                'status' => $linePayload['order_line_state'] ?? null,
                'price' => $linePayload['price'] ?? 0,
                'quantity' => $linePayload['quantity'] ?? 1,
                'tracking_code' => $orderPayload['shipping_tracking'] ?? null,
                'payload' => $linePayload,
            ];

            RipleyOrderItem::updateOrCreate($lineAttributes, $lineValues);
        }

        return $order;
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (!$date) return null;
        try {
            return Carbon::parse($date)->setTimezone(config('app.timezone'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
