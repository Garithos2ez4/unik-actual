<?php

namespace App\Services;

use App\Models\Ecommerce\MercadoLibreCredential;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Models\Ecommerce\MercadoLibreOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MercadoLibreOrderSyncService
{
    public function __construct(
        protected MercadoLibreApiService $mlApi
    ) {}

    /**
     * Sincroniza todas las órdenes de todos los sellers configurados.
     */
    public function syncAll(?string $fromDate = null): int
    {
        $credentials = MercadoLibreCredential::all();

        if ($credentials->isEmpty()) {
            Log::warning('ML Sync: No hay cuentas de Mercado Libre configuradas.');
            return 0;
        }

        $total = 0;
        foreach ($credentials as $credential) {
            $total += $this->syncBySeller($credential->seller_id, $fromDate);
        }

        return $total;
    }

    /**
     * Sincroniza órdenes de un seller en particular.
     */
    public function syncBySeller(string $sellerId, ?string $fromDate = null): int
    {
        $fromDate = $fromDate ?? now()->subDays(3)->format('Y-m-d\TH:i:s.000-05:00');
        $offset   = 0;
        $limit    = 50;
        $synced   = 0;

        do {
            $response = $this->mlApi->getOrders($sellerId, [
                'order.date_created.from' => $fromDate,
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            $results = $response['results'] ?? [];

            foreach ($results as $orderPayload) {
                try {
                    $this->syncSingleOrder($sellerId, $orderPayload);
                    $synced++;
                } catch (\Throwable $e) {
                    $orderId = $orderPayload['id'] ?? 'unknown';
                    Log::error("ML Sync: Error sincronizando orden {$orderId}: " . $e->getMessage());
                }
            }

            $paging = $response['paging'] ?? [];
            $total  = $paging['total'] ?? 0;
            $offset += $limit;

        } while ($offset < $total);

        return $synced;
    }

    /**
     * Sincroniza una orden individual incluyendo shipment y reclamos.
     */
    public function syncSingleOrder(string $sellerId, array $orderPayload): ?MercadoLibreOrder
    {
        $orderId = (string) ($orderPayload['id'] ?? '');
        if (empty($orderId)) return null;

        // Datos del comprador
        $buyer     = $orderPayload['buyer'] ?? [];
        $buyerName = trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? ''));

        // Datos de envío
        $shippingId    = (string) ($orderPayload['shipping']['id'] ?? '');
        $logisticType  = '';
        $logisticLabel = '';
        $shippingStatus = '';
        $shippingSubstatus = null;
        $driverId = null;
        $shippingMode  = '';
        $carrierName   = '';
        $trackingNumber = '';
        $dateHandled   = null;
        $dateShipped   = null;
        $dateDelivered = null;

        if (!empty($shippingId)) {
            try {
                $shipment      = $this->mlApi->getShipment($sellerId, $shippingId);
                $logisticType  = $shipment['logistic_type'] ?? '';
                $logisticLabel = MercadoLibreApiService::mapLogisticLabel($logisticType);
                $shippingStatus = $shipment['status'] ?? '';
                $shippingSubstatus = $shipment['substatus'] ?? '';
                $shippingMode  = $shipment['mode'] ?? $shipment['shipping_mode'] ?? '';
                $carrierName   = $shipment['service_id'] ?? $shipment['carrier'] ?? '';
                $trackingNumber = $shipment['tracking_number'] ?? '';

                // Si es un envío Flex (self_service), consultamos el Driver ID
                if ($logisticType === 'self_service' && !empty($shippingId)) {
                    $assignment = $this->mlApi->getShipmentAssignment($sellerId, $shippingId);
                    $driverId = $assignment['driver_id'] ?? null;
                }

                // Añadir el shipment al payload para guardar receiver_address y otros datos
                $orderPayload['shipment'] = $shipment;

                $history       = $shipment['status_history'] ?? [];
                $dateHandled   = $this->parseDate($history['date_handling'] ?? null);
                $dateShipped   = $this->parseDate($history['date_shipped'] ?? null);
                $dateDelivered = $this->parseDate($history['date_delivered'] ?? null);
            } catch (\Throwable $e) {
                Log::warning("ML: No se pudo obtener shipment {$shippingId}: " . $e->getMessage());
            }
        }

        // Datos de devolución / reclamos
        $returnStatus = '';
        $returnReason = '';
        try {
            $claimsData = $this->mlApi->getClaims($sellerId, $orderId);
            $claims     = $claimsData['data'] ?? [];
            if (!empty($claims)) {
                $claim        = $claims[0];
                $returnStatus = $claim['stage'] ?? $claim['status'] ?? '';
                $returnReason = $claim['reason_id'] ?? '';
            }
        } catch (\Throwable $e) {
            Log::warning("ML: No se pudieron obtener reclamos para orden {$orderId}: " . $e->getMessage());
        }

        $attributes = ['ml_order_id' => $orderId];
        $values = [
            'seller_id'      => $sellerId,
            'buyer_name'     => $buyerName ?: null,
            'buyer_nickname' => $buyer['nickname'] ?? null,
            'status'         => $orderPayload['status'] ?? null,
            'total_amount'   => $orderPayload['total_amount'] ?? null,
            'currency'       => $orderPayload['currency_id'] ?? 'PEN',
            'shipping_id'    => $shippingId ?: null,
            'logistic_type'  => $logisticType ?: null,
            'logistic_label' => $logisticLabel ?: null,
            'shipping_status' => $shippingStatus ?: null,
            'shipping_substatus' => $shippingSubstatus ?: null,
            'driver_id'      => $driverId ?: null,
            'shipping_mode'  => $shippingMode ?: null,
            'carrier_name'   => $carrierName ?: null,
            'tracking_number' => $trackingNumber ?: null,
            'date_handled'   => $dateHandled,
            'date_shipped'   => $dateShipped,
            'date_delivered' => $dateDelivered,
            'return_status'  => $returnStatus ?: null,
            'return_reason'  => $returnReason ?: null,
            'items_count'    => count($orderPayload['order_items'] ?? []),
            'payload'        => $orderPayload,
            'created_at_ml'  => $this->parseDate($orderPayload['date_created'] ?? null),
            'synced_at'      => now(),
            'sync_date'      => now()->toDateString(),
        ];

        $order = MercadoLibreOrder::updateOrCreate($attributes, $values);

        // Sincronizar ítems
        foreach ($orderPayload['order_items'] ?? [] as $idx => $item) {
            $mlItemId = $orderId . '_' . ($item['item']['id'] ?? $idx);

            MercadoLibreOrderItem::updateOrCreate(
                ['ml_item_id' => $mlItemId],
                [
                    'ml_order_id' => $orderId,
                    'seller_sku'  => $item['item']['seller_sku'] ?? null,
                    'ml_sku'      => $item['item']['id'] ?? null,
                    'title'       => $item['item']['title'] ?? null,
                    'status'      => $item['item']['condition'] ?? null,
                    'unit_price'  => $item['unit_price'] ?? null,
                    'quantity'    => $item['quantity'] ?? 1,
                    'payload'     => $item,
                ]
            );
        }

        return $order;
    }

    protected function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) return null;
        try {
            return Carbon::parse($date);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
