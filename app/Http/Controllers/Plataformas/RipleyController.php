<?php

namespace App\Http\Controllers\Plataformas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\PlataformaServiceInterface;
use App\Services\FalabellaApiService;
use App\Services\FalabellaOrderSyncService;
use App\Services\RipleyApiService;
use Illuminate\Support\Facades\Log;
use Throwable;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class RipleyController extends Controller
{
    protected $headerService;
    protected $plataformaService;
    protected $falabellaApiService;
    protected $falabellaOrderSyncService;

    public function __construct(
        HeaderServiceInterface $headerService,
        PlataformaServiceInterface $plataformaService,
        FalabellaApiService $falabellaApiService,
        FalabellaOrderSyncService $falabellaOrderSyncService
    ) {
        $this->headerService              = $headerService;
        $this->plataformaService          = $plataformaService;
        $this->falabellaApiService        = $falabellaApiService;
        $this->falabellaOrderSyncService  = $falabellaOrderSyncService;
    }



    public function orders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', 'pending'));

                $query = \App\Models\Ecommerce\RipleyOrder::with('items');
                if ($selectedDate) {
                    $query->where(function ($q) use ($selectedDate) {
                        $q->whereDate('created_at_ripley', $selectedDate)
                            ->orWhereDate('sync_date', $selectedDate);
                    });
                }

                if (filled($selectedStatus) && $selectedStatus !== 'all') {
                    if ($selectedStatus === 'pending') {
                        $query->whereIn('status', ['SHIPPING', 'STAGING', 'WAITING_ACCEPTANCE']);
                    } elseif ($selectedStatus === 'shipped') {
                        $query->whereIn('status', ['SHIPPED', 'RECEIVED']);
                    } else {
                        $query->where('status', $selectedStatus);
                    }
                }

                $orders = $query->orderByDesc('created_at_ripley')->get();

                return view('plataformas.ripley.ripley-orders', [
                    'user'           => $userModel,
                    'selectedDate'   => $selectedDate,
                    'selectedStatus' => $selectedStatus,
                    'orders'         => $orders,
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function publicaciones(Request $request, \App\Services\RipleyApiService $ripleyApiService)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $search = $request->input('search');
                $limit  = 50;
                $offset = max(0, (int) $request->input('offset', 0));

                                try {
                    if ($search) {
                        // Fetch ALL offers to filter locally (Mirakl limits max to 100 per request)
                        $allOffers = [];
                        $apiOffset = 0;
                        $apiLimit = 100;
                        $apiTotalCount = 0;

                        do {
                            $params = ['max' => $apiLimit, 'offset' => $apiOffset];
                            $response = $ripleyApiService->getOffers($params);
                            $offersPage = $response['offers'];
                            $apiTotalCount = $response['total_count'];
                            
                            $allOffers = array_merge($allOffers, $offersPage);
                            $apiOffset += $apiLimit;
                        } while (count($allOffers) < $apiTotalCount && count($offersPage) > 0);

                        // Normalize for model-number fuzzy matching
                        $searchNorm = strtolower(str_replace(['-', ' ', '_'], '', $search));
                        $filtered   = [];
                        foreach ($allOffers as $offer) {
                            foreach (self::flattenOfferStrings($offer) as $value) {
                                $valueNorm = strtolower(str_replace(['-', ' ', '_'], '', $value));
                                if (str_contains($valueNorm, $searchNorm)) {
                                    $filtered[] = $offer;
                                    break;
                                }
                            }
                        }
                        $totalCount = count($filtered);
                        $offers     = array_slice($filtered, $offset, $limit);
                    } else {
                        // Use native API pagination when not searching
                        $params = [
                            'max'    => $limit,
                            'offset' => $offset,
                        ];
                        $response = $ripleyApiService->getOffers($params);
                        $offers = $response['offers'];
                        $totalCount = $response['total_count'];
                    }

                    // DEBUG: visit ?debug=1 to see exact raw offer structure from Ripley API
                    if ($request->query('debug') == '1') {
                        dd([
                            'total_count'    => $totalCount,
                            'first_3_offers' => array_slice($offers, 0, 3),
                        ]);
                    }

                    $error = null;
                } catch (\Exception $e) {
                    $offers = [];
                    $totalCount = 0;
                    $error = $e->getMessage();
                }

                return view('plataformas.ripley.ripley-publicaciones', [
                    'user'       => $userModel,
                    'offers'     => $offers,
                    'search'     => $search ?? '',
                    'limit'      => $limit,
                    'offset'     => $offset,
                    'totalCount' => $totalCount,
                    'error'      => $error ?? null,
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function etiquetas(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', 'pending'));

                $query = \App\Models\Ecommerce\RipleyOrder::with('items');

                if ($selectedDate) {
                    $query->whereDate('promised_shipping_time', $selectedDate);
                }

                if (filled($selectedStatus) && $selectedStatus === 'pending') {
                    // En Mirakl, WAITING_ACCEPTANCE y SHIPPING significan que el vendedor debe enviarlo
                    $query->whereIn('status', ['WAITING_ACCEPTANCE', 'SHIPPING', 'pending', 'ready_to_ship', 'waiting_debit_payment']);
                } elseif (filled($selectedStatus) && $selectedStatus !== 'all') {
                    $query->where('status', $selectedStatus);
                }

                $orders = $query->orderByDesc('promised_shipping_time')->get();

                return view('plataformas.ripley.ripley-etiquetas', [
                    'user'           => $userModel,
                    'selectedDate'   => $selectedDate,
                    'selectedStatus' => $selectedStatus,
                    'orders'         => $orders,
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function etiquetasDescargar(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                @ini_set('max_execution_time', '300');
                @set_time_limit(300);

                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', 'pending'));

                $query = \App\Models\Ecommerce\RipleyOrder::with('items');

                if ($selectedDate) {
                    $query->whereDate('promised_shipping_time', $selectedDate);
                }

                if (filled($selectedStatus) && $selectedStatus === 'pending') {
                    $query->whereIn('status', ['WAITING_ACCEPTANCE', 'SHIPPING', 'pending', 'ready_to_ship', 'waiting_debit_payment']);
                } elseif (filled($selectedStatus) && $selectedStatus !== 'all') {
                    $query->where('status', $selectedStatus);
                }

                $orders = $query->orderByDesc('promised_shipping_time')->get();

                if ($orders->isEmpty()) {
                    $this->headerService->sendFlashAlerts('Sin etiquetas', 'No hay órdenes para la fecha/estado seleccionado.', 'info', 'btn-warning');
                    return redirect()->back();
                }

                // Obtenemos los IDs de las ordenes
                $orderIds = $orders->pluck('order_id')->toArray();

                try {
                    $ripleyApi = app(\App\Services\RipleyApiService::class);
                    $result = $ripleyApi->getDocumentsRaw($orderIds);

                    $contentType = $result['content_type'];
                    $labels = [];

                    if (str_contains(strtolower($contentType), 'zip')) {
                        // Extract ZIP
                        $tempZip = storage_path('app/temp_ripley_' . time() . '.zip');
                        file_put_contents($tempZip, $result['body']);

                        $zip = new \ZipArchive;
                        if ($zip->open($tempZip) === TRUE) {
                            for ($i = 0; $i < $zip->numFiles; $i++) {
                                $filename = $zip->getNameIndex($i);
                                if (str_ends_with(strtolower($filename), '.pdf')) {
                                    $pdfContent = $zip->getFromIndex($i);
                                    $labels[] = base64_encode($pdfContent);
                                }
                            }
                            $zip->close();
                        }
                        @unlink($tempZip);
                    } else {
                        // Es un solo PDF
                        $labels[] = base64_encode($result['body']);
                    }

                    if (empty($labels)) {
                        return response()->json(['success' => false, 'error' => 'No se encontraron etiquetas PDF en la respuesta.']);
                    }

                    return response()->json([
                        'success' => true,
                        'labels'  => $labels,
                    ]);
                } catch (\Throwable $e) {
                    return response()->json(['success' => false, 'error' => $e->getMessage()]);
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }


    private static function flattenOfferStrings(array $array): array
    {
        $strings = [];
        array_walk_recursive($array, function ($item) use (&$strings) {
            if (is_string($item)) {
                $strings[] = $item;
            } elseif (is_numeric($item)) {
                $strings[] = (string) $item;
            }
        });
        return $strings;
    }

}
