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

class PlataformaBaseController extends Controller
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


    public function index()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $plataformas = $this->plataformaService->getAllPlataformas()->filter(function ($p) {
                    return $p->nombrePlataforma !== 'Tienda';
                });

                return view('plataformas', [
                    'user' => $userModel,
                    'plataformas' => $plataformas
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function faltantes(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                @ini_set('max_execution_time', '300'); // Evitar timeout

                $plataformas = \App\Models\Empresa\Plataforma::whereIn('nombrePlataforma', ['Falabella', 'Mercado Libre', 'Ripley'])->get();

                $publicadosPorPlataforma = [];
                $publicadosMap = []; // Para busqueda O(1)

                foreach ($plataformas as $plat) {
                    $cuentas = $plat->CuentasPlataforma->pluck('idCuentaPlataforma');
                    $pubs = \App\Models\Catalogo\Publicacion::whereIn('idCuentaPlataforma', $cuentas)->get(['idProducto', 'titulo', 'sku']);
                    
                    $publicadosMap[$plat->idPlataforma] = ['by_id' => [], 'pubs' => []];
                    
                    foreach ($pubs as $pub) {
                        if ($pub->idProducto) {
                            $publicadosMap[$plat->idPlataforma]['by_id'][$pub->idProducto] = true;
                        }
                        // Pre-calcular el uppercase para no hacerlo millones de veces en el loop interno
                        $publicadosMap[$plat->idPlataforma]['pubs'][] = [
                            'titulo' => strtoupper((string)$pub->titulo),
                            'sku' => strtoupper((string)$pub->sku),
                        ];
                    }
                }

                $productosConStock = \App\Models\Catalogo\Producto::with('Inventario')->whereHas('Inventario', function ($q) {
                    $q->where('stock', '>', 0);
                })->where('estadoProductoWeb', '!=', 'DESCONTINUADO')->get();

                $faltantesGenerales = [];

                foreach ($productosConStock as $prod) {
                    $modelo = strtoupper(trim((string)$prod->modelo));
                    $missingIn = [];

                    foreach ($plataformas as $plat) {
                        $isPublished = false;
                        
                        // 1. Busqueda super rapida O(1) por idProducto
                        if (isset($publicadosMap[$plat->idPlataforma]['by_id'][$prod->idProducto])) {
                            $isPublished = true;
                        } 
                        // 2. Busqueda inteligente por modelo en string
                        elseif (!empty($modelo) && $modelo !== '-') {
                            // Generamos variantes del modelo para un "Fuzzy Match" (Como un LIKE flexible)
                            $variantes = [$modelo];
                            
                            // Variante sin guiones (ej. TE-2764G -> TE2764G)
                            if (str_contains($modelo, '-')) {
                                $variantes[] = str_replace('-', '', $modelo);
                                $variantes[] = str_replace('-', ' ', $modelo);
                                
                                // Variante base (ej. T544120-BK -> T544120)
                                $partes = explode('-', $modelo);
                                if (strlen($partes[0]) >= 4) { // Solo si la base es representativa
                                    $variantes[] = trim($partes[0]);
                                } elseif (isset($partes[1]) && strlen($partes[1]) >= 4) {
                                    $variantes[] = trim($partes[1]); // ej. TE-2765G -> 2765G
                                }
                            }

                            foreach ($publicadosMap[$plat->idPlataforma]['pubs'] as $p) {
                                foreach ($variantes as $var) {
                                    if (str_contains($p['titulo'], $var) || str_contains($p['sku'], $var)) {
                                        $isPublished = true;
                                        break 2; // Romper ambos foreach
                                    }
                                }
                            }
                        }

                        if (!$isPublished) {
                            $missingIn[] = $plat;
                        }
                    }

                    if (count($missingIn) > 0) {
                        $prod->missing_platforms = $missingIn;
                        $faltantesGenerales[] = $prod;
                    }
                }

                return view('plataformas.faltantes', [
                    'user' => $userModel,
                    'plataformas' => $plataformas,
                    'faltantes' => $faltantesGenerales
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function updateCuentas(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $acounts = $request->input('cuentas', []);

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                $this->plataformaService->updateCuentas($acounts);
                $this->headerService->sendFlashAlerts('Cuentas actualizadas', 'Operacion realizada correctamente', 'success', 'btn-success');
                return redirect()->back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }



    public function createCuenta(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $nombrecuenta = $request->input('cuenta');
        $idplataforma = $request->input('plataforma');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 4) {
                if ($nombrecuenta != '') {
                    $this->plataformaService->createCuenta($idplataforma, $nombrecuenta);
                    $this->headerService->sendFlashAlerts('Cuenta Registrada', 'Operacion realizada correctamente.', 'success', 'btn-success');
                    return redirect()->back();
                } else {
                    $this->headerService->sendFlashAlerts('Faltan datos', 'Ingresa datos en el formulario.', 'info', 'btn-warning');
                    return redirect()->back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

}
