<?php

namespace App\Http\Controllers;

use App\Models\Ecommerce\MercadoLibreCredential;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Jobs\SyncMercadoLibreOrdersJob;
use App\Services\MercadoLibreApiService;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MercadoLibreController extends Controller
{
    public function __construct(
        protected HeaderServiceInterface $headerService,
        protected MercadoLibreApiService $mlApi
    ) {}

    /**
     * Redirige al usuario a la pantalla de autorización de Mercado Libre.
     */
    public function authorize()
    {
        $userModel = $this->headerService->getModelUser();
        if (!$userModel->Accesos->contains('idVista', 7)) {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado');
        }

        return redirect($this->mlApi->getAuthUrl());
    }

    /**
     * Recibe el callback de ML con el code, intercambia por tokens y los guarda.
     */
    public function callback(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $code  = $request->query('code');
        $error = $request->query('error');

        if ($error || !$code) {
            $this->headerService->sendFlashAlerts(
                'Error ML',
                'Autorización cancelada o fallida: ' . ($error ?? 'sin code'),
                'error',
                'btn-danger'
            );
            return redirect()->route('configweb');
        }

        try {
            $tokenData = $this->mlApi->exchangeCodeForToken($code);

            $accessToken  = $tokenData['access_token'] ?? null;
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn    = $tokenData['expires_in'] ?? 21600;

            if (!$accessToken) {
                throw new \RuntimeException('No se recibió el access_token de Mercado Libre.');
            }

            // Obtener info del seller
            $meData   = $this->mlApi->getMe($accessToken);
            $sellerId = (string) ($meData['id'] ?? '');
            $nickname = $meData['nickname'] ?? '';

            MercadoLibreCredential::updateOrCreate(
                ['seller_id' => $sellerId],
                [
                    'seller_nickname' => $nickname,
                    'access_token'    => $accessToken,
                    'refresh_token'   => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                ]
            );

            $this->headerService->sendFlashAlerts(
                'Correcto',
                "Cuenta de ML '{$nickname}' conectada correctamente.",
                'success',
                'btn-success'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en callback de Mercado Libre: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $this->headerService->sendFlashAlerts(
                'Error ML',
                'No se pudo conectar: ' . $e->getMessage(),
                'error',
                'btn-danger'
            );
        }

        return redirect()->route('configweb');
    }

    /**
     * Dispara sincronización manual en background (Job).
     */
    public function syncOrders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        if (!$userModel->Accesos->contains('idVista', 7)) {
            return response()->json(['error' => 'Acceso denegado'], 403);
        }

        $sellerId = $request->input('seller_id');
        $fromDate = $request->input('from_date');

        Cache::put('ml_sync_orders_status', 'processing', 300);
        SyncMercadoLibreOrdersJob::dispatch($sellerId ?: null, $fromDate ?: null);

        return response()->json(['status' => 'dispatched']);
    }

    /**
     * Retorna el estado del último Job de sincronización ML.
     */
    public function syncStatus()
    {
        return response()->json([
            'status' => Cache::get('ml_sync_orders_status', 'idle'),
        ]);
    }

    /**
     * Lista órdenes sincronizadas con filtros.
     */
    public function orders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) { // 4 es Plataformas
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta seccion', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $query = MercadoLibreOrder::with('items')->orderByDesc('created_at_ml');

        if ($request->filled('logistic_type')) {
            $logType = $request->input('logistic_type');
            if ($logType === 'drop_off_all') {
                $query->whereIn('logistic_type', ['drop_off', 'xd_drop_off']);
            } else {
                $query->where('logistic_type', $logType);
            }
        }

        $shippingStatus = $request->input('shipping_status', 'pending_only');
        if ($shippingStatus === 'pending_only') {
            // Ocultar los finalizados
            $query->whereNotIn('shipping_status', ['delivered', 'not_delivered', 'cancelled']);
        } elseif ($shippingStatus !== '') {
            $query->where('shipping_status', $shippingStatus);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at_ml', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at_ml', '<=', $request->input('to'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('ml_order_id', 'LIKE', "%{$search}%")
                  ->orWhere('buyer_name', 'LIKE', "%{$search}%")
                  ->orWhere('buyer_nickname', 'LIKE', "%{$search}%");
            });
        }

        $orders      = $query->paginate(50)->appends($request->all());
        $credentials = MercadoLibreCredential::all();

        return view('plataformas.mercadolibre.orders', [
            'user'        => $userModel,
            'orders'      => $orders,
            'credentials' => $credentials,
            'filtros'     => $request->only('logistic_type', 'status', 'from', 'to'),
        ]);
    }

    /**
     * Lista órdenes de envío Flex.
     */
    public function flexOrders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) { // 4 es Plataformas
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta seccion', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $query = MercadoLibreOrder::with('items')
            ->where('logistic_type', 'self_service')
            ->orderByDesc('created_at_ml');

        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->input('shipping_status'));
        }

        $orders = $query->paginate(50);

        return view('plataformas.mercadolibre.flex', [
            'user'   => $userModel,
            'orders' => $orders,
        ]);
    }

    /**
     * Retorna JSON con preguntas sin responder + reclamos abiertos de ML.
     */
    public function getNotifications()
    {
        $credentials = MercadoLibreCredential::all();

        if ($credentials->isEmpty()) {
            return response()->json([
                'questions_count' => 0,
                'claims_count'    => 0,
                'total'           => 0,
                'questions'       => [],
                'claims'          => [],
            ]);
        }

        $allQuestions = [];
        $allClaims    = [];

        foreach ($credentials as $credential) {
            // Preguntas sin responder
            $qData = $this->mlApi->getUnansweredQuestions($credential->seller_id, 10);
            $questions = $qData['questions'] ?? [];

            // Filtrar preguntas con más de 2 semanas de antigüedad
            $twoWeeksAgo = now()->subWeeks(2);
            $questions = array_filter($questions, function ($q) use ($twoWeeksAgo) {
                if (isset($q['date_created'])) {
                    try {
                        return \Carbon\Carbon::parse($q['date_created'])->greaterThanOrEqualTo($twoWeeksAgo);
                    } catch (\Throwable $e) {
                        return true;
                    }
                }
                return true;
            });
            $questions = array_values($questions);


            // Enriquecer con título del producto
            $itemCache = [];
            foreach ($questions as &$q) {
                $itemId = $q['item_id'] ?? '';
                if ($itemId && !isset($itemCache[$itemId])) {
                    $itemCache[$itemId] = $this->mlApi->getItem($itemId);
                }
                $q['item_title'] = $itemCache[$itemId]['title'] ?? 'Producto';
                $q['item_thumbnail'] = $itemCache[$itemId]['thumbnail'] ?? '';
            }
            unset($q);

            $allQuestions = array_merge($allQuestions, $questions);

            // Reclamos abiertos
            $claimsData = $this->mlApi->getOpenClaims($credential->seller_id);
            $claims = $claimsData['data'] ?? [];
            $allClaims = array_merge($allClaims, $claims);
        }

        return response()->json([
            'questions_count' => count($allQuestions),
            'claims_count'    => count($allClaims),
            'total'           => count($allQuestions) + count($allClaims),
            'questions'       => $allQuestions,
            'claims'          => $allClaims,
        ]);
    }

    /**
     * Responde una pregunta de ML.
     */
    public function answerQuestion(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer',
            'text'        => 'required|string|max:2000',
        ]);

        $credential = MercadoLibreCredential::first();

        if (!$credential) {
            return response()->json(['error' => 'No hay cuenta de ML conectada'], 400);
        }

        try {
            $result = $this->mlApi->answerQuestion(
                $credential->seller_id,
                $request->input('question_id'),
                $request->input('text')
            );

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            Log::error('Error respondiendo pregunta ML: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function downloadLabel(Request $request, $order_id)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para descargar etiquetas', 'warning', 'btn-danger');
            return redirect()->back();
        }

        $order = MercadoLibreOrder::where('ml_order_id', $order_id)->firstOrFail();

        // Validaciones según documentación
        if ($order->logistic_type === 'fulfillment') {
            $this->headerService->sendFlashAlerts('Error', 'No se puede imprimir etiquetas de envíos Fulfillment.', 'error', 'btn-danger');
            return redirect()->back();
        }

        if ($order->shipping_mode !== 'me2') {
            $this->headerService->sendFlashAlerts('Error', 'La orden no utiliza Mercado Envíos (me2).', 'error', 'btn-danger');
            return redirect()->back();
        }

        if ($order->shipping_status !== 'ready_to_ship' && $order->shipping_status !== 'shipped') {
            $this->headerService->sendFlashAlerts('Error', 'El estado del envío (' . $order->shipping_status . ') no permite imprimir etiquetas.', 'error', 'btn-danger');
            return redirect()->back();
        }

        if (empty($order->shipping_id)) {
            $this->headerService->sendFlashAlerts('Error', 'No se encontró un ID de envío (shipping_id) válido para esta orden.', 'error', 'btn-danger');
            return redirect()->back();
        }

        try {
            $pdfContent = $this->mlApi->downloadShipmentLabel($order->seller_id, $order->shipping_id);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="etiqueta_' . $order->shipping_id . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al descargar etiqueta ML: ' . $e->getMessage());
            $this->headerService->sendFlashAlerts('Error ML', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back();
        }
    }

    public function etiquetas(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta seccion', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        // Obtener órdenes listas para enviar, excluyendo fulfillment y asegurando me2
        $orders = MercadoLibreOrder::where('shipping_mode', 'me2')
            ->where('logistic_type', '!=', 'fulfillment')
            ->whereIn('shipping_status', ['ready_to_ship'])
            ->orderBy('created_at_ml', 'desc')
            ->paginate(50);

        return view('plataformas.mercadolibre.etiquetas', [
            'orders' => $orders,
            'user' => $userModel
        ]);
    }

    public function downloadLabelsBulk(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['error' => 'Acceso denegado'], 403);
        }

        $shippingIds = $request->input('shipping_ids', []);

        if (empty($shippingIds) || !is_array($shippingIds)) {
            $this->headerService->sendFlashAlerts('Error', 'No se seleccionaron etiquetas para imprimir.', 'error', 'btn-danger');
            return redirect()->back();
        }

        if (count($shippingIds) > 50) {
            $this->headerService->sendFlashAlerts('Error', 'Mercado Libre permite un máximo de 50 etiquetas por consulta.', 'error', 'btn-danger');
            return redirect()->back();
        }

        // Obtener el seller_id del primer envío (asumiendo que todos son de la misma cuenta conectada)
        $order = MercadoLibreOrder::whereIn('shipping_id', $shippingIds)->first();

        if (!$order) {
            $this->headerService->sendFlashAlerts('Error', 'Órdenes no encontradas.', 'error', 'btn-danger');
            return redirect()->back();
        }

        try {
            $pdfContent = $this->mlApi->downloadShipmentLabel($order->seller_id, $shippingIds);

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="etiquetas_ml_bulk_' . date('YmdHis') . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al descargar etiquetas bulk ML: ' . $e->getMessage());
            $this->headerService->sendFlashAlerts('Error ML', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back();
        }
    }

    public function updateDeliveryRanges(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['error' => 'Acceso denegado'], 403);
        }

        $request->validate([
            'seller_id'       => 'required|string',
            'delivery_window' => 'required|in:same_day,next_day',
            'delivery_ranges' => 'required|array'
        ]);

        try {
            $sellerId  = $request->input('seller_id');
            $siteId    = 'MPE'; // Configurado para Perú

            // Extraer el service_id dinámicamente mediante la API
            $serviceId = $this->mlApi->getFlexServiceId($sellerId, $siteId);

            if (!$serviceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una suscripción activa de tipo FLEX para esta cuenta.'
                ], 404);
            }

            $payload = [
                'delivery_window' => $request->input('delivery_window'),
                'delivery_ranges' => $request->input('delivery_ranges')
            ];

            $this->mlApi->updateFlexDeliveryRanges($sellerId, $siteId, $serviceId, $payload);

            return response()->json([
                'success' => true,
                'message' => 'Rangos de entrega Flex actualizados correctamente en Mercado Libre.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando rangos Flex ML: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar los horarios de entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkFlexCategory(Request $request, $categoryId)
    {
        // Si no se envía un seller_id en el request, tomamos la primera cuenta conectada
        $sellerId = $request->input('seller_id');

        if (!$sellerId) {
            $credential = \App\Models\Ecommerce\MercadoLibreCredential::first();
            $sellerId   = $credential ? $credential->seller_id : null;
        }

        if (!$sellerId) {
            return response()->json(['error' => 'No hay cuenta de Mercado Libre conectada para validar la categoría'], 400);
        }

        try {
            $allowsFlex = $this->mlApi->categoryAllowsFlex($sellerId, $categoryId);

            return response()->json([
                'category_id' => $categoryId,
                'allows_flex' => $allowsFlex
            ]);
        } catch (\Exception $e) {
            Log::error('Error validando categoría Flex ML: ' . $e->getMessage());
            return response()->json(['error' => 'Error al consultar la API de Mercado Libre'], 500);
        }
    }

    public function toggleFlexForItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'enable'  => 'required|boolean',
            'seller_id' => 'nullable|string'
        ]);

        $sellerId = $request->input('seller_id');

        if (!$sellerId) {
            $credential = \App\Models\Ecommerce\MercadoLibreCredential::first();
            $sellerId   = $credential ? $credential->seller_id : null;
        }

        if (!$sellerId) {
            return response()->json(['success' => false, 'message' => 'No hay cuenta de Mercado Libre conectada'], 400);
        }

        $itemId = $request->input('item_id');
        $siteId = 'MPE'; // Configurado para Perú

        try {
            if ($request->input('enable')) {
                $result = $this->mlApi->enableFlexForItem($sellerId, $siteId, $itemId);
            } else {
                $result = $this->mlApi->disableFlexForItem($sellerId, $siteId, $itemId);
            }

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Estado de Flex actualizado exitosamente.']);
            } else {
                return response()->json(['success' => false, 'message' => 'No se pudo actualizar el estado de Flex para este ítem.'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error actualizando Flex en ítem ML: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al comunicarse con Mercado Libre'], 500);
        }
    }

    public function publicaciones(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // Verificar acceso a la vista
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) { // Ajustar idVista según corresponda
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Error de Acceso', 'No tiene permisos para ver esta sección.', 'error', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $credential = \App\Models\Ecommerce\MercadoLibreCredential::first();
        $sellerId   = $credential ? $credential->seller_id : null;

        if (!$sellerId) {
            $this->headerService->sendFlashAlerts('Sin cuenta', 'No hay cuenta de Mercado Libre conectada.', 'warning', 'btn-warning');
            return redirect()->back();
        }

        $limit = 20; // ML items multiget soporta max 20
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $limit;

        // 1. Capturar el texto de búsqueda desde el input del frontend
        $searchQuery = $request->input('q');

        // 2. Enviar la búsqueda al servicio (asegúrate de que getSellerItemsSearch acepte este 4to parámetro)
        $searchResult = $this->mlApi->getSellerItemsSearch($sellerId, $offset, $limit, $searchQuery);

        $itemsIds = $searchResult['results'] ?? [];
        $totalItems = $searchResult['paging']['total'] ?? 0;

        $itemsDetails = [];
        if (!empty($itemsIds)) {
            $chunks = array_chunk($itemsIds, 20);
            foreach ($chunks as $chunk) {
                // OJO: Mantengo tu orden de parámetros ($sellerId, $chunk)
                $details = $this->mlApi->getItemsDetails($sellerId, $chunk);
                // getItemsDetails ya extrae el 'body', así que solo combinamos los arreglos
                $itemsDetails = array_merge($itemsDetails, $details);
            }
        }

        // Crear Paginador conservando el parámetro ?q= en la URL
        $publicaciones = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsDetails,
            $totalItems,
            $limit,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('plataformas.mercadolibre.publicaciones', [
            'publicaciones' => $publicaciones,
            'user' => $userModel
        ]);
    }
}
