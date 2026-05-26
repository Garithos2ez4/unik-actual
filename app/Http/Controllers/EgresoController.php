<?php

namespace App\Http\Controllers;

use App\Services\EgresoProductoServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\ProductoServiceInterface;
use App\Services\VentaServiceInterface;
use Exception;

class EgresoController extends Controller
{
    protected $headerService;
    protected $egresoService;
    protected $productoService;
    protected $ventaService;

    public function __construct(
        HeaderServiceInterface $headerService,
        EgresoProductoServiceInterface $egresoService,
        ProductoServiceInterface $productoService,
        VentaServiceInterface $ventaService
    ) {
        $this->headerService = $headerService;
        $this->egresoService = $egresoService;
        $this->productoService = $productoService;
        $this->ventaService = $ventaService;
    }

    public function index($month, Request $request)
    {
        //variables de la cabecera
        $userModel = $this->headerService->getModelUser();

        //variables propias del controlador
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                Carbon::setLocale('es');
                $carbonMonth = Carbon::createFromFormat('Y-m', $month);
                $diaSeleccionado = $request->query('dia');
                $egresos = $this->egresoService->getEgresosByMonth($month, 150, $diaSeleccionado);
                $almacenes = $this->egresoService->getAllAlmacenes();

                return view('egresos', [
                    'user' => $userModel,
                    'egresos' => $egresos,
                    'almacenes' => $almacenes,
                    'fecha' => $carbonMonth,
                    'diaSeleccionado' => $diaSeleccionado
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function create()
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $metodosPago = \App\Models\MetodoPago::where('estado', 1)->get();
                return view('createegreso', [
                    'user' => $userModel,
                    'metodosPago' => $metodosPago
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function insertEgreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $numeroorden = $request->input('numeroorden');
                $fechapedido = $request->input('fechapedido');
                $fechadespacho = $request->input('fechadespacho');
                $items = $request->input('items');

                if (empty($items)) {
                    $this->headerService->sendFlashAlerts('Error en el formulario', 'Verifica que las series esten correctas existan', 'info', 'btn-warning');
                    return back();
                }

                if (!is_null($fechapedido) && !is_null($fechadespacho)) {
                    $arrayEgreso = [
                        'numeroOrden' => $numeroorden == 'No aplica' ? null : $numeroorden,
                        'fechaCompra' => $fechapedido,
                        'fechaDespacho' => $fechadespacho
                    ];

                    try {
                        // 1. Crear el egreso (stock)
                        $createResult = $this->egresoService->createEgreso($arrayEgreso, $items);
                        $productos = $createResult['productos'];
                        $egresosGenerados = $createResult['egresos'];

                        // Determinar el canal dinámicamente basado en la primera publicación válida encontrada
                        $plataformaTienda = \App\Models\Plataforma::find(7);
                        $canal = $plataformaTienda ? strtoupper(substr($plataformaTienda->nombrePlataforma, 0, 20)) : 'TIENDA';
                        foreach ($items as $item) {
                            if (isset($item['idpublicacion']) && $item['idpublicacion'] !== 'NULO' && $item['idpublicacion'] !== '') {
                                $publicacion = \App\Models\Publicacion::with('CuentasPlataforma.Plataforma')->find($item['idpublicacion']);
                                if ($publicacion && $publicacion->CuentasPlataforma && $publicacion->CuentasPlataforma->Plataforma) {
                                    // Limitar a 20 caracteres por la bbdd
                                    $canalStr = $publicacion->CuentasPlataforma->Plataforma->nombrePlataforma;
                                    $canal = substr(strtoupper($canalStr), 0, 20);
                                    break;
                                }
                            }
                        }

                        // 2. Preparar los datos para la Venta
                        $ventaData = [
                            'idCliente' => $request->input('idCliente'), // puede ser null
                            'numeroOrden' => $numeroorden == 'No aplica' ? null : $numeroorden,
                            'fechaVenta' => $fechapedido,
                            'canal' => $canal
                        ];

                        $detallesVenta = [];
                        foreach ($items as $item) {
                            $detallesVenta[] = [
                                'idRegistro' => $item['idregistro'],
                                'idEgreso' => $egresosGenerados[$item['idregistro']] ?? null,
                                'idPublicacion' => (isset($item['idpublicacion']) && $item['idpublicacion'] !== 'NULO' && $item['idpublicacion'] !== '') ? $item['idpublicacion'] : null,
                                // Obtener idProducto usando el idRegistro a través del DetalleComprobante
                                'idProducto' => \App\Models\RegistroProducto::with('DetalleComprobante')->find($item['idregistro'])->DetalleComprobante->idProducto ?? null,
                                'precioVenta' => $item['precioVenta'] ?? '', // '' hace que herede auto en el service, '0' se respeta como regalo
                                'cantidad' => 1
                            ];
                        }

                        // 3. Obtener pagos (si existen)
                        $pagos = $request->input('pagos', []);

                        // 4. Crear la Venta
                        $this->ventaService->createVenta($ventaData, $detallesVenta, $pagos);

                        $this->headerService->sendFlashAlerts('Egreso y Venta registrados', 'Operacion exitosa', 'success', 'btn-success');
                        return back();
                    } catch (\Exception $e) {
                        $this->headerService->sendFlashAlerts('Error al registrar egreso/venta', $e->getMessage(), 'error', 'btn-danger');
                        return back();
                    }
                } else {
                    $this->headerService->sendFlashAlerts('Datos incompletos', 'Verifica que los datos ingresados existan', 'info', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function devolucionEgreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $transaccion = $request->input('transaccion');
                $idegreso = $request->input('idegreso');
                $observacion = $request->input('observacion');
                $fechaDevolucion = $request->input('fecha_devolucion');
                $sku = $request->input('sku');
                $nro_orden = $request->input('nro_orden');
                $fecha_compra = $request->input('fecha_compra');
                $fecha_despacho = $request->input('fecha_despacho');

                if (isset($transaccion) && isset($idegreso)) {
                    $dataEgreso = [
                        'sku' => $sku,
                        'numeroOrden' => $nro_orden,
                        'fechaCompra' => $fecha_compra,
                        'fechaDespacho' => $fecha_despacho
                    ];
                    $this->egresoService->updateEgreso($transaccion, $idegreso, $observacion, null, $fechaDevolucion, $dataEgreso);

                    $this->headerService->sendFlashAlerts('Operacion exitosa', 'No hubo ningún error en la operacion', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Datos incompletos', 'Verifica que los datos ingresados existan', 'info', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function searchRegistro(Request $request)
    {
        $query = $request->input('query');
        $results = $this->egresoService->searchAjaxRegistro($query);

        return response()->json($results);
    }

    public function getOneRegistro(Request $request)
    {
        $query = $request->input('query');
        $results = $this->egresoService->getOneAjaxRegistro($query);

        return response()->json($results);
    }

    public function searchEgreso(Request $request)
    {
        $query = $request->input('query');
        $results = $this->egresoService->searchAjaxEgreso($query, 5);

        return response()->json($results);
    }

    public function importarExcel(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        // Verificar acceso
        $tieneAcceso = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $tieneAcceso = true;
                break;
            }
        }

        if (!$tieneAcceso) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EgresosImport($this->egresoService, $this->ventaService), $request->file('archivo_excel'));
            $this->headerService->sendFlashAlerts('Egresos masivos registrados', 'El archivo Excel se ha procesado exitosamente.', 'success', 'btn-success');
        } catch (\Exception $e) {
            $this->headerService->sendFlashAlerts('Error al importar', 'Ocurrió un error: ' . $e->getMessage(), 'error', 'btn-danger');
        }

        return back();
    }

    public function descargarFormato()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([
                    [
                        '11/05/2026',
                        'Leonardo',
                        'DISCO SOLIDO OEM M2 256GB',
                        '1',
                        'Egreso',
                        'De Tienda',
                        'ML - Unik',
                        'UNK-SSDOEM256GB-100036',
                        '2000016365872746',
                        'SKU-EJEMPLO',
                        'FLEX',
                        ''
                    ]
                ]);
            }

            public function headings(): array
            {
                return [
                    'Fecha',
                    'Responsable',
                    'Producto',
                    'Un.',
                    'Movimiento',
                    'Almacén',
                    'Plataforma',
                    'SERIES',
                    'Orden',
                    'SKU',
                    'Envío por',
                    'Observaciones'
                ];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'formato_egresos.xlsx');
    }
}
