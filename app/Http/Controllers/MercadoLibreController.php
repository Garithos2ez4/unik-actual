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
                'Error ML', 'Autorización cancelada o fallida: ' . ($error ?? 'sin code'),
                'error', 'btn-danger'
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
                'Correcto', "Cuenta de ML '{$nickname}' conectada correctamente.",
                'success', 'btn-success'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en callback de Mercado Libre: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $this->headerService->sendFlashAlerts(
                'Error ML', 'No se pudo conectar: ' . $e->getMessage(),
                'error', 'btn-danger'
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
        if (!$userModel->Accesos->contains('idVista', 13)) {
            return redirect()->route('dashboard');
        }

        $query = MercadoLibreOrder::with('items')->orderByDesc('created_at_ml');

        if ($request->filled('logistic_type')) {
            $query->where('logistic_type', $request->input('logistic_type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at_ml', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at_ml', '<=', $request->input('to'));
        }

        $orders      = $query->paginate(50);
        $credentials = MercadoLibreCredential::all();

        return view('plataformas.mercadolibre.orders', [
            'user'        => $userModel,
            'orders'      => $orders,
            'credentials' => $credentials,
            'filtros'     => $request->only('logistic_type', 'status', 'from', 'to'),
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
}
