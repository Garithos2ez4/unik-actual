<?php

namespace App\Http\Controllers;

use App\Services\EgresoProductoServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\ProductoServiceInterface;
use App\Services\VentaServiceInterface;
use App\Models\RegistroProducto;
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
                if ($month === 'null' || empty($month)) {
                    $month = Carbon::now()->format('Y-m');
                }
                $carbonMonth = Carbon::createFromFormat('Y-m', $month);
                $diaSeleccionado = $request->query('dia');
                $egresos = $this->egresoService->getEgresosByMonth($month, 150, $diaSeleccionado);
                $almacenes = $this->egresoService->getAllAlmacenes();
                $usuarios = \App\Models\Usuario::all();

                return view('egresos.egresos', [
                    'user' => $userModel,
                    'egresos' => $egresos,
                    'almacenes' => $almacenes,
                    'fecha' => $carbonMonth,
                    'diaSeleccionado' => $diaSeleccionado,
                    'usuarios' => $usuarios
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
                $cuentasBancarias = \App\Models\CuentasTransferencia::with('Banco')->orderBy('idBanco')->get();
                $empresas = \App\Models\Empresa::all();
                $tipoDocumentos = \App\Models\TipoDocumento::all();
                return view('egresos.createegreso', [
                    'user' => $userModel,
                    'metodosPago' => $metodosPago,
                    'cuentasBancarias' => $cuentasBancarias,
                    'empresas' => $empresas,
                    'tipoDocumentos' => $tipoDocumentos
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
                    if ($fechapedido > $fechadespacho) {
                        $this->headerService->sendFlashAlerts('Error de validación', 'La fecha de pedido no puede ser posterior a la fecha de despacho', 'error', 'btn-danger');
                        return back();
                    }

                    \Illuminate\Support\Facades\DB::beginTransaction();
                    try {
                        // Agrupar los items por número de orden
                        $itemsByOrden = [];
                        foreach ($items as $item) {
                            $ordenStr = isset($item['numeroorden']) && $item['numeroorden'] !== '' ? $item['numeroorden'] : 'No aplica';
                            $itemsByOrden[$ordenStr][] = $item;
                        }

                        foreach ($itemsByOrden as $numeroordenGrupo => $grupoItems) {
                            $arrayEgreso = [
                                'numeroOrden' => $numeroordenGrupo === 'No aplica' ? null : $numeroordenGrupo,
                                'fechaCompra' => $fechapedido,
                                'fechaDespacho' => $fechadespacho
                            ];

                            // 1. Crear el egreso (stock)
                            $createResult = $this->egresoService->createEgreso($arrayEgreso, $grupoItems);
                            $productos = $createResult['productos'];
                            $egresosGenerados = $createResult['egresos'];

                            // Determinar el canal dinámicamente basado en la primera publicación válida encontrada
                            $plataformaTienda = \App\Models\Plataforma::find(7);
                            $canal = $plataformaTienda ? strtoupper(substr($plataformaTienda->nombrePlataforma, 0, 20)) : 'TIENDA';
                            foreach ($grupoItems as $item) {
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
                                'numeroOrden' => $numeroordenGrupo === 'No aplica' ? null : $numeroordenGrupo,
                                'fechaVenta' => $fechadespacho,
                                'canal' => $canal
                            ];

                            $detallesVenta = [];
                            foreach ($grupoItems as $item) {
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

                            // 3. Pagos no se usan en este modo agrupado masivo
                            $pagos = [];

                            // 4. Crear la Venta
                            $this->ventaService->createVenta($ventaData, $detallesVenta, $pagos);
                        }

                        \Illuminate\Support\Facades\DB::commit();
                        $this->headerService->sendFlashAlerts('Egreso y Venta registrados', 'Operacion exitosa', 'success', 'btn-success');
                        return back();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
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
                $precio_venta = $request->input('precio_venta');

                if (isset($transaccion) && isset($idegreso)) {
                    if ($transaccion === 'update' && !empty($fecha_compra) && !empty($fecha_despacho) && $fecha_compra > $fecha_despacho) {
                        $this->headerService->sendFlashAlerts('Error de validación', 'La fecha de compra no puede ser posterior a la fecha de despacho', 'error', 'btn-danger');
                        return back();
                    }
                    $dataEgreso = [
                        'sku' => $sku,
                        'numeroOrden' => $nro_orden,
                        'fechaCompra' => $fecha_compra,
                        'fechaDespacho' => $fecha_despacho,
                        'precioVenta' => $precio_venta
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

    public function appendEgreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $idegreso = $request->input('idegreso');
                $idRegistro = $request->input('append_idregistro');
                $sku = $request->input('append_sku');
                $precio = $request->input('append_precio');

                if (empty($idegreso) || empty($idRegistro)) {
                    $this->headerService->sendFlashAlerts('Datos incompletos', 'Verifica que hayas seleccionado una serie válida.', 'info', 'btn-warning');
                    return back();
                }

                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    // Obtener Egreso Original
                    $egresoOriginal = \App\Models\EgresoProducto::findOrFail($idegreso);

                    // Buscar Publicación
                    $idPublicacion = 'NULO';
                    if (!empty($sku)) {
                        $publicacion = \App\Models\Publicacion::where('sku', $sku)->first();
                        if ($publicacion) {
                            $idPublicacion = $publicacion->idPublicacion;
                        }
                    }

                    // Armar array para createEgreso
                    $arrayEgreso = [
                        'numeroOrden' => $egresoOriginal->numeroOrden,
                        'fechaCompra' => $egresoOriginal->fechaCompra,
                        'fechaDespacho' => $egresoOriginal->fechaDespacho
                    ];
                    
                    $items = [
                        [
                            'idregistro' => $idRegistro,
                            'idpublicacion' => $idPublicacion,
                            'precioVenta' => $precio
                        ]
                    ];

                    $createResult = $this->egresoService->createEgreso($arrayEgreso, $items);
                    $egresosGenerados = $createResult['egresos'];
                    $nuevoIdEgreso = $egresosGenerados[$idRegistro];

                    // Verificar si el original tiene una Venta asignada
                    $detalleOriginal = \App\Models\DetalleVenta::where('idEgreso', $idegreso)->first();
                    if ($detalleOriginal && $detalleOriginal->idVenta) {
                        $venta = \App\Models\Venta::find($detalleOriginal->idVenta);
                        if ($venta) {
                            // Validar precio
                            $precioFinal = ($precio !== null && $precio !== '') ? floatval($precio) : 0;
                            if ($precioFinal == 0 && isset($precio) && $precio !== '') $precioFinal = 0.1;

                            // Heredar lógica de precio si no hay
                            if ($precioFinal == 0) {
                                if ($idPublicacion !== 'NULO' && isset($publicacion)) {
                                    $precioFinal = $publicacion->precioPublicacion;
                                } else {
                                    $producto = \App\Models\RegistroProducto::find($idRegistro)->DetalleComprobante->Producto;
                                    if ($producto) {
                                        $tasaCambio = \App\Models\Calculadora::first()->tasaCambio ?? 1;
                                        $precioFinal = $producto->precioDolar * $tasaCambio;
                                    }
                                }
                            }

                            \App\Models\DetalleVenta::create([
                                'idVenta' => $venta->idVenta,
                                'idEgreso' => $nuevoIdEgreso,
                                'idProducto' => \App\Models\RegistroProducto::find($idRegistro)->DetalleComprobante->idProducto,
                                'idPublicacion' => $idPublicacion !== 'NULO' ? $idPublicacion : null,
                                'precioVenta' => $precioFinal,
                                'cantidad' => 1,
                                'origenPrecio' => $idPublicacion !== 'NULO' ? 'PUBLICACION' : 'TIENDA'
                            ]);

                            // Recalcular el total de la venta
                            $nuevoTotal = \App\Models\DetalleVenta::where('idVenta', $venta->idVenta)
                                ->selectRaw('SUM(precioVenta * cantidad) as total')
                                ->first()->total ?? 0;
                            
                            $venta->totalVenta = floatval($nuevoTotal);
                            $venta->save();
                        }
                    } else {
                        // Si no hay venta (egreso histórico sin migrar), creamos una venta nueva
                        $idUser = $this->headerService->getModelUser()->idUser;
                        $ventaData = [
                            'idCliente' => null,
                            'numeroOrden' => $egresoOriginal->numeroOrden,
                            'fechaVenta' => $egresoOriginal->fechaDespacho,
                            'canal' => $idPublicacion !== 'NULO' ? 'PLATAFORMA' : 'TIENDA'
                        ];
                        
                        $detallesVenta = [[
                            'idRegistro' => $idRegistro,
                            'idEgreso' => $nuevoIdEgreso,
                            'idPublicacion' => $idPublicacion !== 'NULO' ? $idPublicacion : null,
                            'idProducto' => \App\Models\RegistroProducto::find($idRegistro)->DetalleComprobante->idProducto,
                            'precioVenta' => $precio,
                            'cantidad' => 1
                        ]];

                        $this->ventaService->createVenta($ventaData, $detallesVenta, []);
                    }

                    \Illuminate\Support\Facades\DB::commit();
                    $this->headerService->sendFlashAlerts('Producto añadido', 'El producto se sumó a la orden correctamente.', 'success', 'btn-success');
                    return back();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->headerService->sendFlashAlerts('Error al añadir producto', $e->getMessage(), 'error', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function pendientesEnvios(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $fechaDia = $request->input('dia');
                $fechaMes = $request->input('month');
                $fechaCarbon = null;

                $query = \App\Models\EnvioProvinciaProducto::with(['EnvioProvincia.Cliente', 'EnvioProvincia.Destino'])
                    ->whereNotNull('nota_producto')
                    ->where('nota_producto', 'LIKE', '%S/N:%');

                if ($fechaDia) {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaDia);
                    $query->whereHas('EnvioProvincia', function ($q) use ($fechaDia) {
                        $q->whereDate('fecha_envio', $fechaDia);
                    });
                } elseif ($fechaMes) {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaMes . '-01');
                    $query->whereHas('EnvioProvincia', function ($q) use ($fechaMes) {
                        $q->whereMonth('fecha_envio', date('m', strtotime($fechaMes)))
                          ->whereYear('fecha_envio', date('Y', strtotime($fechaMes)));
                    });
                } else {
                    $fechaCarbon = \Carbon\Carbon::now();
                    $query->whereHas('EnvioProvincia', function ($q) {
                        $q->whereMonth('fecha_envio', date('m'))
                          ->whereYear('fecha_envio', date('Y'));
                    });
                }

                $pes = $query->orderBy('idEnvioProvinciaProducto', 'desc')->get();

                $series = [];
                foreach ($pes as $pe) {
                    preg_match_all('/S\/N:\s*([^\s,]+)/', $pe->nota_producto, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $serial) {
                            $serialClasificado = trim($serial);
                            $registro = \App\Models\RegistroProducto::with('DetalleComprobante.Producto')
                                ->where('numeroSerie', $serialClasificado)
                                ->where('estado', '!=', 'ENTREGADO')
                                ->first();

                            if ($registro) {
                                $series[] = [
                                    'idEnvioProducto' => $pe->idEnvioProvinciaProducto,
                                    'serial' => $serialClasificado,
                                    'producto' => $registro->DetalleComprobante->Producto->nombreProducto ?? 'Producto desconocido',
                                    'envio' => $pe->EnvioProvincia->toArray(),
                                    'cantidad' => $pe->cantidad
                                ];
                            }
                        }
                    }
                }

                return view('egresos.pendientes_envios', [
                    'user' => $userModel,
                    'series' => $series,
                    'fecha' => $fechaCarbon
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function markPendienteEgresado(Request $request)
    {
        $idEnvioProducto = $request->input('id_envio_producto');
        $serial = $request->input('serial');

        $envioProducto = \App\Models\EnvioProvinciaProducto::find($idEnvioProducto);
        if ($envioProducto && $envioProducto->nota_producto) {
            $nota = $envioProducto->nota_producto;
            $nota = preg_replace('/S\/N:\s*' . preg_quote($serial, '/') . '/', 'EGRESADO: ' . $serial, $nota);
            $envioProducto->nota_producto = $nota;
            $envioProducto->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'No encontrado']);
    }

    public function searchRegistro(Request $request)
    {
        $query = $request->input('query');
        $exclude = $request->input('exclude', '');
        $excludeArray = array_filter(explode(',', $exclude));
        $results = $this->egresoService->searchAjaxRegistro($query, $excludeArray);

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
            set_time_limit(0); // Permitir tiempo ilimitado para archivos masivos
            ini_set('memory_limit', '-1'); // Aumentar límite de memoria para archivos grandes

            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EgresosImport($this->egresoService, $this->ventaService), $request->file('archivo_excel'));
            $this->headerService->sendFlashAlerts('Egresos masivos registrados', 'El archivo Excel se ha procesado exitosamente.', 'success', 'btn-success');
        } catch (\Exception $e) {
            $this->headerService->sendFlashAlerts('Error al importar', 'Ocurrió un error: ' . $e->getMessage(), 'error', 'btn-danger');
        }

        return back();
    }

    public function egresosMasivos()
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $metodosPago = \App\Models\MetodoPago::where('estado', 1)->get();
                $cuentasBancarias = \App\Models\CuentasTransferencia::with('Banco')->orderBy('idBanco')->get();
                $empresas = \App\Models\Empresa::all();
                $tipoDocumentos = \App\Models\TipoDocumento::all();
                $tasaCambio = app(\App\Services\CalculadoraServiceInterface::class)->obtenerCambioDolar() ?? 3.42;
                return view('egresos.egresos_masivos', [
                    'user' => $userModel,
                    'metodosPago' => $metodosPago,
                    'cuentasBancarias' => $cuentasBancarias,
                    'empresas' => $empresas,
                    'tasaCambio' => $tasaCambio,
                    'tipoDocumentos' => $tipoDocumentos
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function searchProductoAjax(Request $request)
    {
        $query = $request->input('query');
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $words = array_filter(explode(' ', trim($query)));
        $tipo = $request->input('tipo');

        $queryBuilder = \App\Models\Producto::query()
            ->with('PrecioTienda')
            ->leftJoin('MarcaProducto', 'Producto.idMarca', '=', 'MarcaProducto.idMarca')
            ->select(
                'Producto.idProducto', 
                'Producto.nombreProducto', 
                'Producto.modelo', 
                'Producto.codigoProducto', 
                'Producto.imagenProducto1', 
                'MarcaProducto.nombreMarca', 
                'Producto.precioDolar',
                'Producto.idGrupo',
                'Producto.gananciaExtra',
                'Producto.estadoProductoWeb',
                'Producto.usar_tc_fijo'
            );

        if ($tipo === 'componente') {
            $queryBuilder->whereIn('Producto.idGrupo', [51, 52, 94]);
        }

        $productos = $queryBuilder->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where(function ($sq) use ($word) {
                        $sq->where('Producto.nombreProducto', 'LIKE', '%' . $word . '%')
                            ->orWhere('Producto.modelo', 'LIKE', '%' . $word . '%')
                            ->orWhere('Producto.codigoProducto', 'LIKE', '%' . $word . '%')
                            ->orWhere('Producto.partNumber', 'LIKE', '%' . $word . '%')
                            ->orWhere('MarcaProducto.nombreMarca', 'LIKE', '%' . $word . '%');
                    });
                }
            })
            ->take(10)
            ->get();
            
        // Calcular precio de venta sugerido igual que la Web
        $calculadora1 = \App\Models\Calculadora::find(1);
        $calculadora2 = \App\Models\Calculadora::find(2);
        
        $tcSunat = $calculadora1 ? $calculadora1->tasaCambio : 3.42;
        $tcFijo = $calculadora2 ? $calculadora2->tasaCambio : 3.80;
        $igv = $calculadora1 ? $calculadora1->igv : 18;
        $facturacion = $calculadora1 ? $calculadora1->facturacion : 1;
        $empresaUnik = \App\Models\Empresa::find(2);
        $comisionEmpresa = $empresaUnik ? $empresaUnik->comision : 5;

        // Caché de comisiones
        $comisionesPorGrupo = [];

        foreach ($productos as $p) {
            $tc = $p->usar_tc_fijo ? $tcFijo : $tcSunat;
            $precioSolesBase = $p->precioDolar * $tc;
            $gananciaSoles = $p->gananciaExtra * $tc;
            
            // Rango de comisión
            $comisionRango = 0;
            if (!isset($comisionesPorGrupo[$p->idGrupo])) {
                $comisionesPorGrupo[$p->idGrupo] = \App\Models\Comision::where('idGrupoProducto', $p->idGrupo)->with('RangoPrecio')->get();
            }
            foreach ($comisionesPorGrupo[$p->idGrupo] as $com) {
                if ($com->RangoPrecio && $precioSolesBase > $com->RangoPrecio->rangoMin && $precioSolesBase < $com->RangoPrecio->rangoMax) {
                    $comisionRango = $com->comision;
                    break;
                }
            }
            
            $precioIgv = $precioSolesBase * (1 + ($igv / 100));
            $precioSinFacturar = $precioIgv * (1 + ($comisionRango / 100));
            $precioFacturado = $precioSinFacturar * (1 + ($facturacion / 100));
            
            if ($p->estadoProductoWeb == 'EXCLUSIVO' || $p->estadoProductoWeb == 'OFERTA') {
                $precioCalculado = $precioIgv;
            } else {
                $precioCalculado = ($precioSinFacturar + $precioFacturado) / 2;
            }
            
            $totalSoles = $precioCalculado * (1 + ($comisionEmpresa / 100)) + $gananciaSoles;
            
            $p->precioWebSoles = round($totalSoles, 1);
            $p->precioTiendaSoles = $p->PrecioTienda ? $p->PrecioTienda->precioTienda : null;
        }

        return response()->json($productos);
    }

    public function getSeriesDisponibles(Request $request)
    {
        $idProducto = $request->input('idProducto');
        if (empty($idProducto)) {
            return response()->json([]);
        }

        $series = RegistroProducto::join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('Almacen', 'RegistroProducto.idAlmacen', '=', 'Almacen.idAlmacen')
            ->where('DetalleComprobante.idProducto', $idProducto)
            ->where('RegistroProducto.estado', 'NUEVO')
            ->select('RegistroProducto.idRegistro', 'RegistroProducto.numeroSerie', 'Almacen.descripcion as almacen')
            ->orderBy('RegistroProducto.idRegistro', 'desc')
            ->get();

        return response()->json($series);
    }

    public function getCostoRegistro(Request $request)
    {
        $idRegistro = $request->input('idRegistro');
        if (empty($idRegistro)) {
            return response()->json(['costo' => 0]);
        }

        $registro = RegistroProducto::with('DetalleComprobante.Comprobante')->find($idRegistro);
        if (!$registro || !$registro->DetalleComprobante) {
            return response()->json(['costo' => 0]);
        }

        $precioUnitario = $registro->DetalleComprobante->precioUnitario ?? 0;
        $moneda = $registro->DetalleComprobante->Comprobante->moneda ?? 'SOLES';

        if (strtoupper($moneda) === 'DOLAR' || strtoupper($moneda) === 'USD') {
            $tasaCambio = \App\Models\Calculadora::first()->tasaCambio ?? 3.70;
            $precioUnitario = $precioUnitario * $tasaCambio;
        }

        return response()->json(['costo' => round($precioUnitario, 2)]);
    }

    public function calcularCostoEnsamble(Request $request)
    {
        $idRegistroPrincipal = $request->input('idRegistroPrincipal');
        $componentes = $request->input('componentes', []);

        $costoBase = 0;
        $modeloPrincipal = '';

        if (!empty($idRegistroPrincipal)) {
            $registro = RegistroProducto::with(['DetalleComprobante.Comprobante', 'DetalleComprobante.Producto'])->find($idRegistroPrincipal);
            if ($registro && $registro->DetalleComprobante) {
                $precioUnitario = $registro->DetalleComprobante->precioUnitario ?? 0;
                $moneda = $registro->DetalleComprobante->Comprobante->moneda ?? 'SOLES';
                $modeloPrincipal = $registro->DetalleComprobante->Producto->modelo ?? '';

                if (strtoupper($moneda) === 'DOLAR' || strtoupper($moneda) === 'USD') {
                    $tasaCambio = \App\Models\Calculadora::first()->tasaCambio ?? 3.70;
                    $precioUnitario = $precioUnitario * $tasaCambio;
                }
                $costoBase = round($precioUnitario, 2);
            }
        }

        $costoComponentesTotal = 0;
        $detallesComponentes = [];

        if (is_array($componentes)) {
            foreach ($componentes as $comp) {
                if (is_array($comp) && isset($comp['idRegistro'])) {
                    $regComp = RegistroProducto::with('DetalleComprobante.Producto')->find($comp['idRegistro']);
                    $modComp = '';
                    if ($regComp && $regComp->DetalleComprobante && $regComp->DetalleComprobante->Producto) {
                        $modComp = $regComp->DetalleComprobante->Producto->modelo ?? '';
                    }
                    $c = floatval($comp['costo'] ?? 0);
                    $costoComponentesTotal += $c;

                    $detallesComponentes[] = [
                        'idRegistro' => $comp['idRegistro'],
                        'modelo' => $modComp,
                        'costo' => $c
                    ];
                } else {
                    $costoComponentesTotal += floatval($comp);
                }
            }
        } elseif (is_numeric($componentes)) {
            $costoComponentesTotal = floatval($componentes);
        }

        $costoTotal = $costoBase + $costoComponentesTotal;

        return response()->json([
            'modelo_principal' => $modeloPrincipal,
            'costo_base' => $costoBase,
            'componentes' => $detallesComponentes,
            'costo_componentes' => $costoComponentesTotal,
            'costo_total' => $costoTotal
        ]);
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
