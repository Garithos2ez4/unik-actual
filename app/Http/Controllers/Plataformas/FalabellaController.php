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

class FalabellaController extends Controller
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



    public function productos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $limit = max(1, min((int) $request->query('limit', 20), 100));
                $offset = max(0, (int) $request->query('offset', 0));
                $search = trim((string) $request->query('search', ''));
                $filter = trim((string) $request->query('filter', 'all'));
                $products = [];
                $response = [];
                $error = null;

                try {
                    $response = $this->falabellaApiService->getProducts([
                        'Limit' => $limit,
                        'Offset' => $offset,
                        'Filter' => $filter !== '' ? $filter : 'all',
                        'Search' => $search !== '' ? $search : null,
                    ]);

                    $products = $this->falabellaApiService->extractProducts($response);
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }

                return view('plataformas.falabella.falabella-productos', [
                    'user' => $userModel,
                    'products' => $products,
                    'response' => $response,
                    'error' => $error,
                    'limit' => $limit,
                    'offset' => $offset,
                    'search' => $search,
                    'filter' => $filter !== '' ? $filter : 'all',
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function publicaciones(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                try {
                    $search = $request->input('search');
                    $filter = $request->input('filter', 'all');
                    $limit  = 50;
                    $offset = max(0, (int) $request->input('offset', 0));

                    $response = $this->falabellaApiService->getProducts([
                        'Search' => $search,
                        'Filter' => $filter,
                        'Limit'  => $limit,
                        'Offset' => $offset,
                    ]);

                    $products = $this->falabellaApiService->extractProducts($response);

                    $falabellaPlataforma = \App\Models\Empresa\Plataforma::where('nombrePlataforma', 'Falabella')->first();
                    $falabellaPlataformaId = $falabellaPlataforma ? $falabellaPlataforma->idPlataforma : 0;
                    $encryptedId = encrypt($falabellaPlataformaId);
                } catch (\Exception $e) {
                    $products = [];
                    $error = $e->getMessage();
                    $encryptedId = encrypt(0);
                }

                return view('plataformas.falabella.falabella-publicaciones', [
                    'user'     => $userModel,
                    'products' => $products,
                    'search'   => $search ?? '',
                    'filter'   => $filter ?? 'all',
                    'limit'    => $limit ?? 50,
                    'offset'   => $offset ?? 0,
                    'error'    => $error ?? null,
                    'encryptedId' => $encryptedId,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function sellerFalabella()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $modules = [
                    [
                        'title' => 'Sincronizacion de ordenes del dia',
                        'description' => 'Fase 1. Traer pedidos desde Seller Center para iniciar el flujo operativo.',
                        'icon' => 'arrow-repeat',
                        'status' => 'Disponible',
                        'priority' => 'Critica',
                        'url' => route('plataformas.falabella.orders'),
                        'disabled' => false,
                    ],
                    [
                        'title' => 'Lista de picking',
                        'description' => 'Preparar automaticamente la lista de productos para almacen.',
                        'icon' => 'list-check',
                        'status' => 'Disponible',
                        'priority' => 'Critica',
                        'url' => route('plataformas.falabella.picking'),
                        'disabled' => false,
                    ],
                    [
                        'title' => 'Etiquetas de envio',
                        'description' => 'Generar etiquetas de despacho en formato 4 por pagina A4.',
                        'icon' => 'tags-fill',
                        'status' => 'Disponible',
                        'priority' => 'Critica',
                        'url' => route('plataformas.falabella.etiquetas'),
                        'disabled' => false,
                    ],
                    [
                        'title'       => 'Productos publicados',
                        'description' => 'Consultar el catalogo conectado actualmente a Falabella Seller Center.',
                        'icon'        => 'bag-check-fill',
                        'status'      => 'Disponible',
                        'priority'    => 'Media',
                        'url'         => route('plataformas.falabella.productos'),
                        'disabled'    => false,
                    ],
                    [
                        'title'       => 'Devoluciones',
                        'description' => 'Gestionar y seguir las devoluciones recibidas desde Falabella Seller Center.',
                        'icon'        => 'arrow-return-left',
                        'status'      => 'Disponible',
                        'priority'    => 'Alta',
                        'url'         => route('plataformas.falabella.devoluciones'),
                        'disabled'    => false,
                    ],
                ];

                return view('plataformas.falabella.seller-falabella', [
                    'user' => $userModel,
                    'modules' => $modules,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function orders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));
                $search = $request->query('search');

                if ($search) {
                    // MODO CONSULTA PURA (SIN GUARDAR EN DB)
                    try {
                        // 1. Intentar buscar con varios formatos de parámetro (Falabella es especial)
                        $response = $this->falabellaApiService->getOrders([
                            'order_ids' => $search,
                            'order_numbers' => $search,
                            'Status' => 'all'
                        ]);
                        $apiOrders = $this->falabellaApiService->extractOrders($response);

                        // 2. Si la API ignoró los filtros y mandó las últimas 100, filtramos nosotros a mano
                        // para que el usuario NO vea basura, solo lo que buscó.
                        $apiOrders = array_filter($apiOrders, function ($o) use ($search) {
                            $norm = $o['_normalized'] ?? [];
                            $id = (string)($norm['order_id'] ?? '');
                            $num = (string)($norm['order_number'] ?? '');
                            return $id == $search || $num == $search;
                        });

                        // 3. Convertir datos de API a objetos compatibles con la vista
                        $orders = collect($apiOrders)->map(function ($o) {
                            $norm = $o['_normalized'] ?? [];
                            $orderId = $norm['order_id'] ?? '';

                            // Consultar items en tiempo real para esta orden específica
                            $items = collect();
                            try {
                                $itemsResp = $this->falabellaApiService->getOrderItems($orderId);
                                $apiItems = $this->falabellaApiService->extractOrderItems($itemsResp);
                                $items = collect($apiItems)->map(function ($itemPayload) {
                                    $inorm = $itemPayload['_normalized'] ?? [];
                                    return (object)[
                                        'seller_sku'    => $inorm['seller_sku'] ?? '',
                                        'falabella_sku' => $inorm['falabella_sku'] ?? '',
                                        'shop_sku'      => $inorm['shop_sku'] ?? '',
                                        'name'          => $inorm['name'] ?? ''
                                    ];
                                });
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning("No se pudieron obtener items para consulta real-time de orden {$orderId}");
                            }

                            return (object)[
                                'order_id'               => $orderId,
                                'order_number'           => $norm['order_number'] ?? '',
                                'customer_name'          => $norm['customer_name'] ?? 'N/A',
                                'shipping_city'          => $norm['city'] ?? 'N/A',
                                'status'                 => $norm['status'] ?? 'unknown',
                                'items_count'            => $norm['items_count'] ?? 0,
                                'price'                  => $norm['price'] ?? 0,
                                'promised_shipping_time' => isset($norm['promised_shipping_time']) ? \Carbon\Carbon::parse($norm['promised_shipping_time']) : null,
                                'items'                  => $items
                            ];
                        });
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Error en consulta API: " . $e->getMessage());
                        $orders = collect();
                    }
                } else {
                    $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                        $selectedDate,
                        $selectedStatus !== '' ? $selectedStatus : null
                    );

                    // Filtrar solo las órdenes seleccionadas por checkbox (si se enviaron)
                    $orderIdsParam = $request->query('order_ids');
                    if (!empty($orderIdsParam)) {
                        $selectedIds = is_array($orderIdsParam) ? $orderIdsParam : explode(',', $orderIdsParam);
                        $orders = $orders->filter(function ($order) use ($selectedIds) {
                            return in_array($order->order_id, $selectedIds);
                        });
                    }
                }

                return view('plataformas.falabella.falabella-orders', [
                    'user'           => $userModel,
                    'selectedDate'   => $selectedDate,
                    'selectedStatus' => $selectedStatus,
                    'orders'         => $orders,
                    'search'         => $search
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function syncOrders(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                @ini_set('max_execution_time', '600');
                @set_time_limit(600);

                $date = $request->input('date', now()->toDateString());
                $status = trim((string) $request->input('status', ''));

                try {
                    // Sincronizar el día exacto trayendo todos los estados, y refrescar obsoletos para actualizar envíos
                    $result = $this->falabellaOrderSyncService->syncDispatchQueue($date, null, 0, true);

                    $this->headerService->sendFlashAlerts(
                        'Sincronizacion completada',
                        'Se sincronizaron y actualizaron ' . $result['count'] . ' órdenes en total',
                        'success',
                        'btn-success'
                    );
                } catch (Throwable $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error de sincronizacion',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                }

                return redirect()->route('plataformas.falabella.orders', [
                    'date' => $date,
                    'status' => $status,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaÃ±a', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function picking(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));

                $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                    $selectedDate,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                // Agrupar ítems por seller_sku
                $pickingItems = [];
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        $groupKey = $item->falabella_sku ?: $item->seller_sku ?: 'UNKNOWN';
                        if (!isset($pickingItems[$groupKey])) {
                            $image = null;
                            $skuToSearch = $item->seller_sku ?: $item->falabella_sku;
                            if ($skuToSearch && $skuToSearch !== '-' && $skuToSearch !== 'UNKNOWN') {
                                $producto = \App\Models\Catalogo\Producto::where('codigoProducto', $skuToSearch)->first();
                                if ($producto && $producto->imagenProducto1) {
                                    $image = asset('storage/' . $producto->imagenProducto1);
                                }
                            }

                            $pickingItems[$groupKey] = [
                                'seller_sku'    => $item->seller_sku ?? '-',
                                'falabella_sku' => $item->falabella_sku ?? '-',
                                'name'          => $item->name ?? '-',
                                'total_qty'     => 0,
                                'orders'        => [],
                                'image'         => $image,
                            ];
                        }
                        $pickingItems[$groupKey]['total_qty'] += $item->quantity;
                        $pickingItems[$groupKey]['orders'][] = [
                            'order_number' => $order->order_number ?? $order->order_id,
                            'quantity'     => $item->quantity,
                        ];
                    }
                }

                usort($pickingItems, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);

                return view('plataformas.falabella.falabella-picking', [
                    'user'           => $userModel,
                    'selectedDate'   => $selectedDate,
                    'selectedStatus' => $selectedStatus,
                    'orders'         => $orders,
                    'pickingItems'   => $pickingItems,
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
                $selectedStatus = trim((string) $request->query('status', ''));

                $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                    $selectedDate,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                return view('plataformas.falabella.falabella-etiquetas', [
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



    public function pickingPdf(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));

                $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                    $selectedDate,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                $pickingItems = [];
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        $groupKey = $item->falabella_sku ?: $item->seller_sku ?: 'UNKNOWN';
                        if (!isset($pickingItems[$groupKey])) {
                            $image = null;
                            $skuToSearch = $item->seller_sku ?: $item->falabella_sku;
                            if ($skuToSearch && $skuToSearch !== '-' && $skuToSearch !== 'UNKNOWN') {
                                $producto = \App\Models\Catalogo\Producto::where('codigoProducto', $skuToSearch)->first();
                                if ($producto && $producto->imagenProducto1) {
                                    $image = asset('storage/' . $producto->imagenProducto1);
                                }
                            }

                            $pickingItems[$groupKey] = [
                                'seller_sku'    => $item->seller_sku ?? '-',
                                'falabella_sku' => $item->falabella_sku ?? '-',
                                'name'          => $item->name ?? '-',
                                'total_qty'     => 0,
                                'orders'        => [],
                                'image'         => $image,
                            ];
                        }
                        $pickingItems[$groupKey]['total_qty'] += $item->quantity;
                        $pickingItems[$groupKey]['orders'][] = [
                            'order_number' => $order->order_number ?? $order->order_id,
                            'quantity'     => $item->quantity,
                        ];
                    }
                }
                usort($pickingItems, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);

                $totalUnits = collect($pickingItems)->sum('total_qty');

                $pdf = Pdf::loadView('pdf.falabella_picking_pdf', [
                    'selectedDate'   => $selectedDate,
                    'selectedStatus' => $selectedStatus !== '' ? $selectedStatus : 'Pendientes para alistar',
                    'orders'         => $orders,
                    'pickingItems'   => $pickingItems,
                    'totalUnits'     => $totalUnits,
                ])->setPaper('a4', 'portrait');

                return $pdf->stream('picking_' . $selectedDate . '.pdf');
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function etiquetasPdf(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));

                $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                    $selectedDate,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                $pdf = Pdf::loadView('pdf.falabella_etiquetas_pdf', [
                    'selectedDate' => $selectedDate,
                    'orders'       => $orders,
                ])->setPaper('a4', 'portrait');

                return $pdf->stream('etiquetas_' . $selectedDate . '.pdf');
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function returns(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $dateFrom       = $request->query('date_from', now()->subDays(30)->toDateString());
                $dateTo         = $request->query('date_to',   now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));

                $returns = $this->falabellaOrderSyncService->getReturns(
                    $dateFrom,
                    $dateTo,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                return view('plataformas.falabella.falabella-devoluciones', [
                    'user'           => $userModel,
                    'dateFrom'       => $dateFrom,
                    'dateTo'         => $dateTo,
                    'selectedStatus' => $selectedStatus,
                    'returns'        => $returns,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function syncReturns(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                @ini_set('max_execution_time', '300');
                @set_time_limit(300);

                $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
                $dateTo   = $request->input('date_to',   now()->toDateString());

                try {
                    $result = $this->falabellaOrderSyncService->syncReturnsByDateRange($dateFrom, $dateTo);

                    $this->headerService->sendFlashAlerts(
                        'Sincronización completada',
                        'Se encontraron ' . $result['count'] . ' devoluciones en el rango seleccionado.',
                        'success',
                        'btn-success'
                    );
                } catch (Throwable $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error de sincronización',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                }

                return redirect()->route('plataformas.falabella.devoluciones', [
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function orderDetails(Request $request, $order_id)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $order = \App\Models\Ecommerce\FalabellaOrder::with('items')->where('order_id', $order_id)->firstOrFail();

                return view('plataformas.falabella.falabella-order-details', [
                    'order' => $order,
                    'user'  => $userModel,
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }



    public function etiquetasOficialesPdf(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                @ini_set('max_execution_time', '300');
                @set_time_limit(300);

                $selectedDate   = $request->query('date', now()->toDateString());
                $selectedStatus = trim((string) $request->query('status', ''));

                $orders = $this->falabellaOrderSyncService->getOrdersByDate(
                    $selectedDate,
                    $selectedStatus !== '' ? $selectedStatus : null
                );

                // Filtrar solo las órdenes seleccionadas por checkbox (si se enviaron)
                $orderIdsParam = $request->query('order_ids');
                if (!empty($orderIdsParam)) {
                    $selectedIds = is_array($orderIdsParam) ? $orderIdsParam : explode(',', $orderIdsParam);
                    $orders = $orders->filter(function ($order) use ($selectedIds) {
                        return in_array($order->order_id, $selectedIds);
                    });
                }

                $labels = [];
                $errors = [];
                $processedPackages = [];

                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        // Evitar duplicados por PackageId
                        $packageId = $item->package_id;
                        if ($packageId && in_array($packageId, $processedPackages)) {
                            continue;
                        }

                        try {
                            $pdfBinary = $this->falabellaApiService->getDocumentRaw($item->order_item_id);
                            $labels[] = base64_encode($pdfBinary);
                            if ($packageId) {
                                $processedPackages[] = $packageId;
                            }
                        } catch (Throwable $e) {
                            $errors[] = "Item {$item->order_item_id}: " . $e->getMessage();
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'labels'  => $labels,
                    'errors'  => $errors,
                ]);
            }
        }

        return response()->json(['success' => false, 'error' => 'No tienes permiso'], 403);
    }

}
