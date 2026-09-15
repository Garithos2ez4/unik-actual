<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Services\OpenRouteService;
use App\Services\HeaderServiceInterface;

class DeliveryFlexController extends Controller
{
    protected $headerService;
    protected $openRouteService;

    public function __construct(HeaderServiceInterface $headerService, OpenRouteService $openRouteService)
    {
        $this->headerService = $headerService;
        $this->openRouteService = $openRouteService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // Obtener órdenes de Mercado Libre que sean Flex (logistic_type = 'self_service')
        // Generalmente son órdenes con status 'paid' que aún no se entregan.
        // O dependiendo de tu flujo, tal vez busques 'ready_to_ship'.
        $flexOrders = MercadoLibreOrder::with('items')
            ->where('logistic_type', 'self_service')
            ->whereNotIn('status', ['cancelled'])
            ->where(function($q) {
                $q->where('shipping_status', '!=', 'delivered')
                  ->orWhereNull('shipping_status');
            })
            ->orderBy('created_at_ml', 'desc')
            ->get();

        $entregas = [];

        foreach ($flexOrders as $order) {
            $payload = $order->payload;
            $receiverAddress = $payload['shipment']['receiver_address'] ?? null;
            
            $distanciaKm = null;
            $costo = null;
            $destinoTxt = 'Sin dirección';

            if ($receiverAddress && isset($receiverAddress['latitude']) && isset($receiverAddress['longitude'])) {
                $lat = $receiverAddress['latitude'];
                $lon = $receiverAddress['longitude'];
                
                // Si la lat y lon no son 0 (hay veces que ML no devuelve geolocalización exacta)
                if ($lat != 0 && $lon != 0) {
                    $distanciaKm = $this->openRouteService->calcularDistancia($lat, $lon);
                }
                
                $costo = $this->openRouteService->calcularCosto($distanciaKm);
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
            } else if ($receiverAddress) {
                // Caso en que tenemos dirección pero sin lat/long
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
                $costo = $this->openRouteService->calcularCosto(null);
            }

            $zona = 'Centro / Sin Asignar';
            if ($distanciaKm !== null && $lat != 0 && $lon != 0) {
                $zona = $this->determinarZona($lat, $lon);
            }

            $entregas[] = [
                'order_id' => $order->ml_order_id,
                'buyer_name' => $order->buyer_name ?? $order->buyer_nickname,
                'status' => $order->status,
                'items' => $order->items,
                'destino' => $destinoTxt,
                'distancia_km' => $distanciaKm,
                'costo' => $costo,
                'fecha_venta' => $order->created_at_ml ? $order->created_at_ml->format('d/m/Y H:i') : 'N/A',
                'zona' => $zona
            ];
        }

        // Agrupar por zona
        $entregasAgrupadas = collect($entregas)->groupBy('zona')->toArray();

        return view('plataformas.mercadolibre.partials.delivery_flex', [
            'user' => $userModel,
            'entregasAgrupadas' => $entregasAgrupadas
        ]);
    }

    /**
     * Determina la zona (Norte, Sur, Este, Oeste) relativa al punto de partida usando el ángulo.
     */
    private function determinarZona($latDestino, $lngDestino)
    {
        $latOrigen = -12.0545; // Av Bolivia 180 (Aproximado, mismo que webunik)
        $lngOrigen = -77.0388;

        $deltaLat = $latDestino - $latOrigen;
        $deltaLng = $lngDestino - $lngOrigen;

        // atan2 recibe primero Y (Lat) y luego X (Lng)
        $angulo = atan2($deltaLat, $deltaLng) * (180 / pi());

        if ($angulo >= 45 && $angulo < 135) {
            return 'Norte';
        } elseif ($angulo >= -135 && $angulo < -45) {
            return 'Sur';
        } elseif ($angulo >= -45 && $angulo < 45) {
            return 'Este';
        } else {
            return 'Oeste';
        }
    }
}
