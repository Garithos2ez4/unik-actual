<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Models\Web\PedidoWeb;
use App\Models\Web\DireccionPedidoWeb;
use App\Models\Web\DetallePedidoWeb;
use App\Models\Usuarios\Cliente;
use App\Models\Catalogo\Producto;
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
        $savedRoutes = \App\Models\Ecommerce\MercadoLibreFlexRoute::all()->keyBy('ml_order_id');

        // 1. Órdenes Flex de Mercado Libre
        $flexOrders = MercadoLibreOrder::with(['items', 'credential'])
            ->where('logistic_type', 'self_service')
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) {
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

                if ($lat != 0 && $lon != 0) {
                    if (isset($savedRoutes[$order->ml_order_id]) && $savedRoutes[$order->ml_order_id]->distancia_km !== null) {
                        $distanciaKm = $savedRoutes[$order->ml_order_id]->distancia_km;
                    } else {
                        $distanciaKm = $this->openRouteService->calcularDistancia($lat, $lon);
                        if ($distanciaKm !== null) {
                            $route = \App\Models\Ecommerce\MercadoLibreFlexRoute::firstOrNew(['ml_order_id' => $order->ml_order_id]);
                            if (!$route->exists) {
                                $route->zona = $this->determinarZona((float)$lat, (float)$lon);
                            }
                            $route->distancia_km = $distanciaKm;
                            $route->save();
                        }
                    }
                }

                $costo = $this->openRouteService->calcularCosto($distanciaKm);
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
            } else if ($receiverAddress) {
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
                $costo = $this->openRouteService->calcularCosto(null);
            }

            $zona = 'Centro / Sin Asignar';
            if ($distanciaKm !== null && $lat != 0 && $lon != 0) {
                $zona = $this->determinarZona($lat, $lon);
            }

            $plazoMaximo = '—';
            if (isset($payload['shipment']['shipping_option']['estimated_delivery_limit']['date'])) {
                $fechaLimiteStr = $payload['shipment']['shipping_option']['estimated_delivery_limit']['date'];
                try {
                    $fechaLimite = \Carbon\Carbon::parse($fechaLimiteStr)->timezone('America/Lima');
                    if ($fechaLimite->isToday()) {
                        $plazoMaximo = 'Hoy';
                    } elseif ($fechaLimite->isTomorrow()) {
                        $plazoMaximo = 'Mañana';
                    } else {
                        $plazoMaximo = $fechaLimite->format('d/m/Y');
                    }
                } catch (\Exception $e) {
                    $plazoMaximo = '—';
                }
            }

            $entregas[] = [
                'order_id' => $order->ml_order_id,
                'buyer_name' => $order->buyer_name ?? $order->buyer_nickname,
                'status' => $order->status,
                'items' => $order->items,
                'destino' => $destinoTxt,
                'distancia_km' => $distanciaKm,
                'plazo_maximo' => $plazoMaximo,
                'fecha_venta' => $order->created_at_ml ? $order->created_at_ml->format('d/m/Y H:i') : 'N/A',
                'zona' => $zona,
                'lat' => $lat ?? null,
                'lng' => $lon ?? null,
                'tipo' => 'ml',
                'pedido_web_id' => null,
                'telefono' => $order->buyer_phone,
                'cuenta' => $order->credential->seller_nickname ?? 'N/A',
                'balance_envio' => ($order->receiver_shipping_cost ?? 0) + ($order->promoted_shipping_amount ?? 0)
            ];
        }

        // 2. Pedidos Web con delivery a domicilio (ventas por WhatsApp u otros)
        $pedidosWeb = PedidoWeb::with(['direccion', 'detalles.producto', 'cliente'])
            ->where('tipo_entrega', 'domicilio')
            ->where('estado', 'pendiente')
            ->whereHas('direccion')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($pedidosWeb as $pedido) {
            $dir = $pedido->direccion;
            $lat = $dir->latitud ?? null;
            $lon = $dir->longitud ?? null;
            $distanciaKm = null;
            $costo = null;

            if ($lat && $lon && $lat != 0 && $lon != 0) {
                $orderIdWsp = 'WSP-' . $pedido->idPedidoWeb;
                if (isset($savedRoutes[$orderIdWsp]) && $savedRoutes[$orderIdWsp]->distancia_km !== null) {
                    $distanciaKm = $savedRoutes[$orderIdWsp]->distancia_km;
                } else {
                    $distanciaKm = $this->openRouteService->calcularDistancia((float)$lat, (float)$lon);
                    if ($distanciaKm !== null) {
                        $route = \App\Models\Ecommerce\MercadoLibreFlexRoute::firstOrNew(['ml_order_id' => $orderIdWsp]);
                        if (!$route->exists) {
                            $route->zona = $this->determinarZona((float)$lat, (float)$lon);
                        }
                        $route->distancia_km = $distanciaKm;
                        $route->save();
                    }
                }
            }
            $costo = $this->openRouteService->calcularCosto($distanciaKm);

            $zona = 'Centro / Sin Asignar';
            if ($distanciaKm !== null && $lat != 0 && $lon != 0) {
                $zona = $this->determinarZona((float)$lat, (float)$lon);
            }

            $clienteNombre = $pedido->cliente
                ? trim($pedido->cliente->nombre . ' ' . ($pedido->cliente->apellidoPaterno ?? ''))
                : 'Cliente WSP';

            $clienteTelefono = $pedido->cliente ? $pedido->cliente->telefono : null;

            // Construir items como objetos simples para mantener compatibilidad con la vista
            $itemsWsp = [];
            foreach ($pedido->detalles as $det) {
                $itemsWsp[] = (object)[
                    'title' => $det->producto ? $det->producto->nombreProducto : 'Producto #' . $det->idProducto,
                    'quantity' => $det->cantidad
                ];
            }
            if (empty($itemsWsp)) {
                $itemsWsp[] = (object)['title' => 'Pedido WSP #' . $pedido->idPedidoWeb, 'quantity' => 1];
            }

            $entregas[] = [
                'order_id' => 'WSP-' . $pedido->idPedidoWeb,
                'buyer_name' => $clienteNombre,
                'status' => $pedido->estado,
                'items' => $itemsWsp,
                'destino' => $dir->direccion ?? 'Sin dirección',
                'distancia_km' => $distanciaKm,
                'plazo_maximo' => 'Hoy', // WSP usualmente es hoy
                'fecha_venta' => $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : 'N/A',
                'zona' => $zona,
                'lat' => $lat,
                'lng' => $lon,
                'tipo' => 'wsp',
                'pedido_web_id' => $pedido->idPedidoWeb,
                'telefono' => $clienteTelefono,
                'cuenta' => 'WhatsApp',
                'balance_envio' => 0
            ];
        }

        // 3. Ya obtuvimos las zonas guardadas al inicio, solo aplicamos

        foreach ($entregas as &$entregaItem) {
            if (isset($savedRoutes[$entregaItem['order_id']])) {
                $entregaItem['zona'] = $savedRoutes[$entregaItem['order_id']]->zona;
            }
        }
        unset($entregaItem); // Romper la referencia

        // Agrupar por zona
        $entregasAgrupadas = collect($entregas)->groupBy('zona')->toArray();

        // Optimizar ruta por zona (Nearest Neighbor)
        foreach ($entregasAgrupadas as $zona => &$listaEntregas) {
            $listaEntregas = $this->ordenarRutaOptima($listaEntregas, $zona);
        }

        return view('plataformas.mercadolibre.partials.delivery_flex', [
            'user' => $userModel,
            'entregasAgrupadas' => $entregasAgrupadas,
            'documentos' => \App\Models\Usuarios\TipoDocumento::all()
        ]);
    }

    /**
     * Buscar productos por nombre para el modal (AJAX)
     */
    public function searchProductos(Request $request)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $productos = Producto::where('nombreProducto', 'like', "%{$query}%")
            ->limit(10)
            ->get(['idProducto', 'nombreProducto', 'codigoProducto']);

        return response()->json($productos);
    }

    /**
     * Buscar clientes por nombre o documento para el modal (AJAX)
     */
    public function searchClientes(Request $request)
    {
        $query = $request->input('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clientes = Cliente::where('nombre', 'like', "%{$query}%")
            ->orWhere('numeroDocumento', 'like', "%{$query}%")
            ->orWhere('apellidoPaterno', 'like', "%{$query}%")
            ->limit(10)
            ->get(['idCliente', 'nombre', 'apellidoPaterno', 'numeroDocumento']);

        return response()->json($clientes);
    }

    /**
     * Crear un pedido web manual (venta por WhatsApp)
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'idCliente' => 'required|integer',
            'direccion' => 'required|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'monto_total' => 'required|numeric|min:0',
            'productos' => 'nullable|array',
            'productos.*.idProducto' => 'required|integer',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        // 1. Calcular costo de envío
        $distanciaKm = $this->openRouteService->calcularDistancia(
            (float)$request->latitud,
            (float)$request->longitud
        );
        $costoEnvio = $this->openRouteService->calcularCosto($distanciaKm);

        // 2. Crear PedidoWeb
        $pedido = PedidoWeb::create([
            'idCliente' => $request->idCliente,
            'codigoTransaccion' => 'WSP-' . time(),
            'pasarela' => 'whatsapp',
            'total' => $request->monto_total,
            'estado' => 'pendiente',
            'tipo_entrega' => 'domicilio',
            'costo_envio' => $costoEnvio
        ]);

        // 4. Crear DireccionPedidoWeb
        DireccionPedidoWeb::create([
            'pedido_web_id' => $pedido->idPedidoWeb,
            'direccion' => $request->direccion,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud
        ]);

        // 5. Crear detalles del pedido (productos)
        if ($request->has('productos') && is_array($request->productos)) {
            foreach ($request->productos as $prod) {
                DetallePedidoWeb::create([
                    'idPedidoWeb' => $pedido->idPedidoWeb,
                    'idProducto' => $prod['idProducto'],
                    'cantidad' => $prod['cantidad'],
                    'precio' => 0 // Always 0 since total is in Monto Total
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Entrega manual creada correctamente.']);
    }

    /**
     * Actualizar un pedido web manual
     */
    public function getManual($id)
    {
        try {
            $pedido = PedidoWeb::with(['detalles.producto', 'direccion', 'cliente'])->findOrFail($id);

            $productos = $pedido->detalles->map(function ($d) {
                return [
                    'idProducto' => $d->idProducto,
                    'nombre' => optional($d->producto)->nombreProducto ?? 'Producto desconocido',
                    'cantidad' => $d->cantidad,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pedido->idPedidoWeb,
                    'idCliente' => $pedido->idCliente,
                    'nombre' => optional($pedido->cliente)->nombre . ' ' . optional($pedido->cliente)->apellidoPaterno,
                    'monto_total' => $pedido->monto_total,
                    'direccion' => optional($pedido->direccion)->direccion,
                    'latitud' => optional($pedido->direccion)->latitud,
                    'longitud' => optional($pedido->direccion)->longitud,
                    'productos' => $productos,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al cargar pedido.']);
        }
    }

    public function updateManual(Request $request, $id)
    {
        $pedido = PedidoWeb::where('idPedidoWeb', $id)
            ->where('pasarela', 'whatsapp')
            ->firstOrFail();

        $request->validate([
            'idCliente' => 'required|integer',
            'direccion' => 'required|string|max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'monto_total' => 'required|numeric|min:0',
            'productos' => 'nullable|array',
        ]);

        // Recalcular envío
        $distanciaKm = $this->openRouteService->calcularDistancia(
            (float)$request->latitud,
            (float)$request->longitud
        );
        $costoEnvio = $this->openRouteService->calcularCosto($distanciaKm);

        $pedido->update([
            'idCliente' => $request->idCliente,
            'total' => $request->monto_total,
            'costo_envio' => $costoEnvio
        ]);

        // Actualizar dirección
        if ($pedido->direccion) {
            $pedido->direccion->update([
                'direccion' => $request->direccion,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud
            ]);
        }

        // Actualizar productos
        if ($request->has('productos') && is_array($request->productos)) {
            DetallePedidoWeb::where('idPedidoWeb', $pedido->idPedidoWeb)->delete();
            foreach ($request->productos as $prod) {
                DetallePedidoWeb::create([
                    'idPedidoWeb' => $pedido->idPedidoWeb,
                    'idProducto' => $prod['idProducto'],
                    'cantidad' => $prod['cantidad'],
                    'precio' => 0
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Entrega actualizada correctamente.']);
    }

    /**
     * Eliminar un pedido web manual
     */
    public function destroyManual($id)
    {
        $pedido = PedidoWeb::where('idPedidoWeb', $id)
            ->where('pasarela', 'whatsapp')
            ->firstOrFail();

        // Eliminar dependencias
        DetallePedidoWeb::where('idPedidoWeb', $pedido->idPedidoWeb)->delete();
        DireccionPedidoWeb::where('pedido_web_id', $pedido->idPedidoWeb)->delete();

        // Eliminar rutas asociadas
        \App\Models\Ecommerce\MercadoLibreFlexRoute::where('ml_order_id', 'WSP-' . $pedido->idPedidoWeb)->delete();

        $pedido->delete();

        return response()->json(['success' => true, 'message' => 'Entrega eliminada correctamente.']);
    }

    /**
     * Marca un pedido manual como completado/despachado para que ya no aparezca en Flex
     */
    public function completeManual($id)
    {
        $pedido = PedidoWeb::where('idPedidoWeb', $id)
            ->where('pasarela', 'whatsapp')
            ->firstOrFail();

        $pedido->estado = 'despachado';
        $pedido->save();

        return response()->json(['success' => true, 'message' => 'Pedido marcado como despachado.']);
    }

    /**
     * Ordena una lista de entregas sugeridas.
     * Si hay un orden manual guardado en DB, lo usa. Si no, usa Nearest Neighbor.
     */
    private function ordenarRutaOptima(array $entregas, string $zona)
    {
        if (empty($entregas)) return [];

        $orderIds = array_column($entregas, 'order_id');

        $savedRoutes = \App\Models\Ecommerce\MercadoLibreFlexRoute::whereIn('ml_order_id', $orderIds)
            ->where('zona', $zona)
            ->get()
            ->keyBy('ml_order_id');

        // Si hay al menos un orden guardado, usamos ese orden
        if ($savedRoutes->count() > 0) {
            usort($entregas, function ($a, $b) use ($savedRoutes) {
                $orderA = isset($savedRoutes[$a['order_id']]) ? $savedRoutes[$a['order_id']]->route_order : 9999;
                $orderB = isset($savedRoutes[$b['order_id']]) ? $savedRoutes[$b['order_id']]->route_order : 9999;
                return $orderA <=> $orderB;
            });
            return $entregas;
        }

        // Si no hay orden guardado, Nearest Neighbor
        $rutaOrdenada = [];
        $pendientes = $entregas;

        // Coordenadas de origen (La tienda)
        $storeCoords = get_store_coordinates();
        $currentLat = $storeCoords['lat'];
        $currentLng = $storeCoords['lng'];

        while (!empty($pendientes)) {
            $closestIndex = -1;
            $minDist = PHP_FLOAT_MAX;

            foreach ($pendientes as $index => $entrega) {
                if ($entrega['lat'] !== null && $entrega['lng'] !== null) {
                    $dist = $this->haversineGreatCircleDistance($currentLat, $currentLng, $entrega['lat'], $entrega['lng']);
                } else {
                    $dist = PHP_FLOAT_MAX; // Si no tiene coords, lo mandamos al final
                }

                if ($dist < $minDist) {
                    $minDist = $dist;
                    $closestIndex = $index;
                }
            }

            if ($closestIndex === -1) {
                $rutaOrdenada = array_merge($rutaOrdenada, $pendientes);
                break;
            }

            $closest = $pendientes[$closestIndex];
            $rutaOrdenada[] = $closest;

            if ($closest['lat'] !== null && $closest['lng'] !== null) {
                $currentLat = $closest['lat'];
                $currentLng = $closest['lng'];
            }

            unset($pendientes[$closestIndex]);
            $pendientes = array_values($pendientes);
        }

        return $rutaOrdenada;
    }

    /**
     * Endpoint para guardar el nuevo orden manual de las rutas
     */
    public function reorder(Request $request)
    {
        $orden = $request->input('orden'); // Array de order_ids en el nuevo orden
        $zona = $request->input('zona');

        if (!is_array($orden) || !$zona) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos.']);
        }

        foreach ($orden as $index => $orderId) {
            \App\Models\Ecommerce\MercadoLibreFlexRoute::updateOrCreate(
                ['ml_order_id' => $orderId],
                ['zona' => $zona, 'route_order' => $index + 1]
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Calcula la distancia en línea recta entre 2 puntos geográficos (Fórmula Haversine)
     */
    private function haversineGreatCircleDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radio de la tierra en km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Determina la zona (Norte, Sur, Este, Oeste) relativa al punto de partida usando el ángulo.
     */
    private function determinarZona($latDestino, $lngDestino)
    {
        $storeCoords = get_store_coordinates();
        $latOrigen = $storeCoords['lat']; // Av Bolivia 180 (Aproximado, mismo que webunik)
        $lngOrigen = $storeCoords['lng'];

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
    /**
     * Descarga la hoja de ruta en PDF para una zona específica
     */
    public function downloadPdf($zonaBuscada)
    {
        // 1. Obtener todas las órdenes de la misma manera que en index()
        $userModel = auth()->user();

        $flexOrders = \App\Models\Ecommerce\MercadoLibreOrder::with('items')
            ->where('logistic_type', 'self_service')
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($q) {
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

                if ($lat != 0 && $lon != 0) {
                    $distanciaKm = $this->openRouteService->calcularDistancia($lat, $lon);
                }

                $costo = $this->openRouteService->calcularCosto($distanciaKm);
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
            } else if ($receiverAddress) {
                $destinoTxt = $receiverAddress['address_line'] . ', ' . ($receiverAddress['city']['name'] ?? '');
                $costo = $this->openRouteService->calcularCosto(null);
            }

            $zona = 'Centro / Sin Asignar';
            if ($distanciaKm !== null && isset($lat) && isset($lon) && $lat != 0 && $lon != 0) {
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
                'zona' => $zona,
                'lat' => $lat ?? null,
                'lng' => $lon ?? null,
                'tipo' => 'ml',
                'pedido_web_id' => null,
                'telefono' => $order->buyer_phone,
                'cuenta' => $order->credential->seller_nickname ?? 'N/A',
                'balance_envio' => ($order->receiver_shipping_cost ?? 0) + ($order->promoted_shipping_amount ?? 0)
            ];
        }

        // 2. Obtener Pedidos Web (WSP)
        $pedidosWeb = \App\Models\web\PedidoWeb::with(['direccion', 'detalles.producto', 'cliente'])
            ->where('tipo_entrega', 'domicilio')
            ->where('estado', 'pendiente')
            ->whereHas('direccion')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($pedidosWeb as $pedido) {
            $dir = $pedido->direccion;
            $lat = $dir->latitud ?? null;
            $lon = $dir->longitud ?? null;
            $distanciaKm = null;
            $costo = null;

            if ($lat && $lon && $lat != 0 && $lon != 0) {
                $distanciaKm = $this->openRouteService->calcularDistancia((float)$lat, (float)$lon);
            }
            $costo = $this->openRouteService->calcularCosto($distanciaKm);

            $zona = 'Centro / Sin Asignar';
            if ($distanciaKm !== null && $lat != 0 && $lon != 0) {
                $zona = $this->determinarZona((float)$lat, (float)$lon);
            }

            $clienteNombre = $pedido->cliente
                ? trim($pedido->cliente->nombre . ' ' . ($pedido->cliente->apellidoPaterno ?? ''))
                : 'Cliente WSP';

            $clienteTelefono = $pedido->cliente ? $pedido->cliente->telefono : null;

            $itemsWsp = [];
            foreach ($pedido->detalles as $det) {
                $itemsWsp[] = (object)[
                    'title' => $det->producto ? $det->producto->nombreProducto : 'Producto #' . $det->idProducto,
                    'quantity' => $det->cantidad
                ];
            }
            if (empty($itemsWsp)) {
                $itemsWsp[] = (object)['title' => 'Pedido WSP #' . $pedido->idPedidoWeb, 'quantity' => 1];
            }

            $entregas[] = [
                'order_id' => 'WSP-' . $pedido->idPedidoWeb,
                'buyer_name' => $clienteNombre,
                'status' => $pedido->estado,
                'items' => $itemsWsp,
                'destino' => $dir->direccion ?? 'Sin dirección',
                'distancia_km' => $distanciaKm,
                'costo' => $costo,
                'fecha_venta' => $pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : 'N/A',
                'zona' => $zona,
                'lat' => $lat,
                'lng' => $lon,
                'tipo' => 'wsp',
                'pedido_web_id' => $pedido->idPedidoWeb,
                'telefono' => $clienteTelefono,
                'cuenta' => 'WhatsApp',
                'balance_envio' => 0
            ];
        }

        // 3. Aplicar las zonas sobreescritas por reorden manual
        $savedRoutes = \App\Models\Ecommerce\MercadoLibreFlexRoute::all()->keyBy('ml_order_id');

        foreach ($entregas as &$entregaItem) {
            if (isset($savedRoutes[$entregaItem['order_id']])) {
                $entregaItem['zona'] = $savedRoutes[$entregaItem['order_id']]->zona;
            }
        }
        unset($entregaItem);

        // Agrupar y buscar la zona solicitada
        $entregasAgrupadas = collect($entregas)->groupBy('zona')->toArray();

        // Lógica para TODAS las zonas
        if ($zonaBuscada === 'todas') {
            $zonasParaPdf = [];
            foreach ($entregasAgrupadas as $z => $lista) {
                // Solo incluimos zonas que tengan entregas
                if (count($lista) > 0) {
                    $zonasParaPdf[$z] = $this->ordenarRutaOptima($lista, $z);
                }
            }

            if (empty($zonasParaPdf)) {
                return redirect()->back()->with([
                    'title' => 'Sin pedidos',
                    'message' => 'No hay pedidos en ninguna zona',
                    'icon' => 'warning',
                    'button' => 'btn-primary'
                ]);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('plataformas.mercadolibre.pdf.hoja_ruta_pdf', [
                'is_multiple' => true,
                'zonas_entregas' => $zonasParaPdf
            ]);

            $pdf->setPaper('a4', 'landscape');

            return $pdf->stream("hoja_de_ruta_todas_las_zonas.pdf");
        }

        // Lógica para una sola zona
        if (!isset($entregasAgrupadas[$zonaBuscada])) {
            return redirect()->back()->with([
                'title' => 'Zona vacía',
                'message' => 'No hay pedidos en la zona ' . $zonaBuscada,
                'icon' => 'warning',
                'button' => 'btn-primary'
            ]);
        }

        // Ordenar la ruta óptima o según lo que guardó el usuario
        $listaEntregas = $this->ordenarRutaOptima($entregasAgrupadas[$zonaBuscada], $zonaBuscada);

        // Generar PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('plataformas.mercadolibre.pdf.hoja_ruta_pdf', [
            'is_multiple' => false,
            'zona' => $zonaBuscada,
            'entregas' => $listaEntregas
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Hoja_Ruta_Zona_' . str_replace(' ', '_', $zonaBuscada) . '.pdf');
    }

    public function updatePhone(Request $request, $orderId)
    {
        $request->validate([
            'buyer_phone' => 'nullable|string|max:20'
        ]);

        $order = \App\Models\Ecommerce\MercadoLibreOrder::where('ml_order_id', $orderId)->firstOrFail();
        $order->update([
            'buyer_phone' => $request->buyer_phone
        ]);

        return response()->json([
            'success' => true,
            'buyer_phone' => $order->buyer_phone
        ]);
    }
}
