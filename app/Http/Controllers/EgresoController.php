<?php

namespace App\Http\Controllers;

use App\Services\EgresoProductoServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\ProductoServiceInterface;
use App\Services\VentaServiceInterface;
use App\Models\Inventario\RegistroProducto;
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
                $usuarios = \App\Models\Usuarios\Usuario::all();

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
                $metodosPago = \App\Models\Ventas\MetodoPago::where('estado', 1)->get();
                $cuentasBancarias = \App\Models\Empresa\CuentasTransferencia::with('Banco')->orderBy('idBanco')->get();
                $empresas = \App\Models\Empresa\Empresa::all();
                $tipoDocumentos = \App\Models\Usuarios\TipoDocumento::all();
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
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'El carrito está vacío o las series no son válidas.']);
                    }
                    $this->headerService->sendFlashAlerts('Error en el formulario', 'Verifica que las series esten correctas existan', 'info', 'btn-warning');
                    return back();
                }

                if (!is_null($fechapedido) && !is_null($fechadespacho)) {
                    if ($fechapedido > $fechadespacho) {
                        if ($request->wantsJson() || $request->ajax()) {
                            return response()->json(['success' => false, 'message' => 'La fecha de pedido no puede ser posterior a la fecha de despacho']);
                        }
                        $this->headerService->sendFlashAlerts('Error de validación', 'La fecha de pedido no puede ser posterior a la fecha de despacho', 'error', 'btn-danger');
                        return back();
                    }

                    // ── Validación Backend: Series de Servicio ──
                    foreach ($items as $item) {
                        if (isset($item['idregistro']) && isset($item['precioVenta']) && $item['precioVenta'] > 30) {
                            $registro = \App\Models\Inventario\RegistroProducto::find($item['idregistro']);
                            if ($registro && stripos($registro->numeroSerie, 'RESETT') !== false && strtoupper($registro->estado) === 'EN_USO') {
                                if ($request->wantsJson() || $request->ajax()) {
                                    return response()->json(['success' => false, 'message' => "La serie {$registro->numeroSerie} es de servicio y su precio no puede superar S/ 30.00"]);
                                }
                                $this->headerService->sendFlashAlerts('Error de validación', "La serie {$registro->numeroSerie} es de servicio y su precio no puede superar S/ 30.00", 'error', 'btn-danger');
                                return back();
                            }
                        }
                    }

                    \Illuminate\Support\Facades\DB::beginTransaction();
                    try {

                        $numeroordenGlobal = (!empty($numeroorden) && $numeroorden !== 'No aplica') ? $numeroorden : null;

                        $itemsByOrden = [];
                        foreach ($items as $item) {
                            if ($numeroordenGlobal !== null) {
                                // Modo createegreso: un único orden para todos
                                $ordenStr = $numeroordenGlobal;
                            } else {
                                // Modo masivos: cada item trae su propio número de orden
                                $ordenStr = isset($item['numeroorden']) && $item['numeroorden'] !== '' && $item['numeroorden'] !== 'No aplica'
                                    ? $item['numeroorden']
                                    : 'No aplica';
                            }
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
                            $plataformaTienda = \App\Models\Empresa\Plataforma::find(7);
                            $canal = $plataformaTienda ? strtoupper(substr($plataformaTienda->nombrePlataforma, 0, 20)) : 'TIENDA';
                            foreach ($grupoItems as $item) {
                                if (isset($item['idpublicacion']) && $item['idpublicacion'] !== 'NULO' && $item['idpublicacion'] !== '') {
                                    $publicacion = \App\Models\Catalogo\Publicacion::with('CuentasPlataforma.Plataforma')->find($item['idpublicacion']);
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
                                    'idProducto' => \App\Models\Inventario\RegistroProducto::with('DetalleComprobante')->find($item['idregistro'])->DetalleComprobante->idProducto ?? null,
                                    'precioVenta' => $item['precioVenta'] ?? '', // '' hace que herede auto en el service, '0' se respeta como regalo
                                    'cantidad' => 1
                                ];
                            }

                            // 3. Pagos (si es venta tienda masiva o createegreso)
                            $pagos = $request->input('pagos') ?? [];

                            // 4. Crear la Venta
                            $this->ventaService->createVenta($ventaData, $detallesVenta, $pagos);
                        }

                        \Illuminate\Support\Facades\DB::commit();
                        
                        if ($request->wantsJson() || $request->ajax()) {
                            return response()->json(['success' => true, 'message' => 'Egreso y Venta registrados exitosamente.']);
                        }
                        
                        $this->headerService->sendFlashAlerts('Egreso y Venta registrados', 'Operacion exitosa', 'success', 'btn-success');
                        return back();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        
                        if ($request->wantsJson() || $request->ajax()) {
                            return response()->json(['success' => false, 'message' => $e->getMessage()]);
                        }
                        
                        $this->headerService->sendFlashAlerts('Error al registrar egreso/venta', $e->getMessage(), 'error', 'btn-danger');
                        return back();
                    }
                } else {
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'Verifica que los datos ingresados existan (fechas incompletas).']);
                    }
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

    public function anularEgreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 10) {
                $idegreso = $request->input('idegreso');

                if (isset($idegreso)) {
                    \Illuminate\Support\Facades\DB::transaction(function() use ($idegreso) {
                        $egreso = \App\Models\Inventario\EgresoProducto::find($idegreso);
                        if ($egreso) {
                            $registro = \App\Models\Inventario\RegistroProducto::find($egreso->idRegistro);
                            if ($registro) {
                                $registro->estado = 'ELIMINADO';
                                $registro->save();
                            }
                            
                            $detalleVenta = \App\Models\Ventas\DetalleVenta::withoutGlobalScope('completado')
                                                ->where('idEgreso', $idegreso)->first();
                            if ($detalleVenta) {
                                $detalleVenta->estado = 'ELIMINADO';
                                $detalleVenta->save();
                            }
                        }
                    });

                    $this->headerService->sendFlashAlerts('Operacion exitosa', 'El egreso fue anulado lógicamente', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Datos incompletos', 'No se especificó el egreso', 'info', 'btn-warning');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para anular egresos', 'warning', 'btn-danger');
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
                    $egresoOriginal = \App\Models\Inventario\EgresoProducto::findOrFail($idegreso);

                    // Buscar Publicación
                    $idPublicacion = 'NULO';
                    if (!empty($sku)) {
                        $publicacion = \App\Models\Catalogo\Publicacion::where('sku', $sku)->first();
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
                    $detalleOriginal = \App\Models\Ventas\DetalleVenta::where('idEgreso', $idegreso)->first();
                    if ($detalleOriginal && $detalleOriginal->idVenta) {
                        $venta = \App\Models\Ventas\Venta::find($detalleOriginal->idVenta);
                        if ($venta) {
                            // Validar precio
                            $precioFinal = ($precio !== null && $precio !== '') ? floatval($precio) : 0;
                            if ($precioFinal == 0 && isset($precio) && $precio !== '') $precioFinal = 0.1;

                            // Heredar lógica de precio si no hay
                            if ($precioFinal == 0) {
                                if ($idPublicacion !== 'NULO' && isset($publicacion)) {
                                    $precioFinal = $publicacion->precioPublicacion;
                                } else {
                                    $producto = \App\Models\Inventario\RegistroProducto::find($idRegistro)->DetalleComprobante->Producto;
                                    if ($producto) {
                                        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 1;
                                        $precioFinal = $producto->precioDolar * $tasaCambio;
                                    }
                                }
                            }

                            \App\Models\Ventas\DetalleVenta::create([
                                'idVenta' => $venta->idVenta,
                                'idEgreso' => $nuevoIdEgreso,
                                'idProducto' => \App\Models\Inventario\RegistroProducto::find($idRegistro)->DetalleComprobante->idProducto,
                                'idPublicacion' => $idPublicacion !== 'NULO' ? $idPublicacion : null,
                                'precioVenta' => $precioFinal,
                                'cantidad' => 1,
                                'origenPrecio' => $idPublicacion !== 'NULO' ? 'PUBLICACION' : 'TIENDA'
                            ]);

                            // Recalcular el total de la venta
                            $nuevoTotal = \App\Models\Ventas\DetalleVenta::where('idVenta', $venta->idVenta)
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

                        $detallesVenta = [
                            [
                                'idRegistro' => $idRegistro,
                                'idEgreso' => $createResult['egresos'][$idRegistro] ?? null,
                                'idPublicacion' => $idPublicacion !== 'NULO' ? $idPublicacion : null,
                                'idProducto' => \App\Models\Inventario\RegistroProducto::with('DetalleComprobante')->find($idRegistro)->DetalleComprobante->idProducto ?? null,
                                'precioVenta' => $precio,
                                'cantidad' => 1
                            ]
                        ];
                        $this->ventaService->createVenta($ventaData, $detallesVenta);
                    }

                    \Illuminate\Support\Facades\DB::commit();
                    $this->headerService->sendFlashAlerts('Éxito', 'El producto ha sido añadido a la orden.', 'success', 'btn-success');
                    return back();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un problema al añadir el producto: ' . $e->getMessage(), 'error', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta acción', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function upgradeEgreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $idegreso = $request->input('idegreso');
                $idRegistroUpgrade = $request->input('upgrade_idregistro');
                $upgradeCostoInput = $request->input('upgrade_costo');

                if (empty($idegreso) || empty($idRegistroUpgrade)) {
                    $this->headerService->sendFlashAlerts('Datos incompletos', 'Verifica que hayas escaneado un componente válido.', 'info', 'btn-warning');
                    return back();
                }

                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    // Obtener Egreso Original (Laptop)
                    $egresoLaptop = \App\Models\Inventario\EgresoProducto::findOrFail($idegreso);
                    $registroLaptop = $egresoLaptop->RegistroProducto;
                    $detalleLaptop = $registroLaptop->DetalleComprobante;

                    // Obtener Registro del Componente (RAM/SSD)
                    $registroComponente = \App\Models\Inventario\RegistroProducto::findOrFail($idRegistroUpgrade);
                    $detalleComponente = $registroComponente->DetalleComprobante;

                    if (!$detalleLaptop || !$detalleComponente) {
                        throw new \Exception("Datos de compra inconsistentes.");
                    }

                    // Determinar costo a sumar (del input o del costo de compra original)
                    $costoSumar = ($upgradeCostoInput !== null && $upgradeCostoInput !== '' && floatval($upgradeCostoInput) >= 0)
                        ? floatval($upgradeCostoInput)
                        : floatval($detalleComponente->precioCompra ?? 0);

                    // 2. Aislar el costo en un DC exclusivo ANTES de crear el egreso
                    // (para que el createEgreso registre el RP ya con el DC correcto)
                    $idNuevoDc = null;
                    if ($costoSumar > 0) {
                        // Crear nuevo DC exclusivo con el costo del upgrade
                        $maxDcId = \App\Models\Ventas\DetalleComprobante::max('idDetalleComprobante');
                        $idNuevoDc = $maxDcId + 1;
                        \App\Models\Ventas\DetalleComprobante::insert([
                            'idDetalleComprobante' => $idNuevoDc,
                            'idComprobante'        => $detalleComponente->idComprobante,
                            'idProducto'           => $detalleComponente->idProducto,
                            'medida'               => $detalleComponente->medida,
                            'precioUnitario'       => $detalleComponente->precioUnitario,
                            'precioCompra'         => $costoSumar,
                        ]);
                        // Reasignar el RP al nuevo DC antes de crear el egreso
                        $registroComponente->idDetalleComprobante = $idNuevoDc;
                        $registroComponente->save();
                        // Refrescar para que createEgreso use el DC correcto
                        $detalleComponente = \App\Models\Ventas\DetalleComprobante::find($idNuevoDc);
                    }

                    // 1. Crear el egreso para el componente (con el DC ya reasignado)
                    $arrayEgreso = [
                        'numeroOrden'   => $egresoLaptop->numeroOrden,
                        'fechaCompra'   => $egresoLaptop->fechaCompra,
                        'fechaDespacho' => $egresoLaptop->fechaDespacho
                    ];
                    $items = [
                        [
                            'idregistro'   => $idRegistroUpgrade,
                            'idpublicacion' => 'NULO',
                            'precioVenta'  => 0 // Upgrade: incluido en el precio del equipo
                        ]
                    ];
                    $createResult = $this->egresoService->createEgreso($arrayEgreso, $items);

                    // 3. Añadir observación cruzada
                    $serialComponente = $registroComponente->numeroSerie;
                    $serialLaptop = $registroLaptop->numeroSerie;

                    $this->egresoService->updateEgreso('update', $idegreso,
                        $registroLaptop->observacion . " [UPGRADE AÑADIDO: " . $serialComponente . " (S/" . number_format($costoSumar, 2) . ")]"
                    );

                    $idEgresoNuevo = $createResult['egresos'][$idRegistroUpgrade] ?? null;
                    if ($idEgresoNuevo) {
                        $this->egresoService->updateEgreso('update', $idEgresoNuevo,
                            "Usado como UPGRADE para " . $serialLaptop
                        );
                    }

                    // 4. Agregar a Venta con precio 0 para que figure en los registros
                    $idVenta = null;
                    if ($egresoLaptop->DetalleVenta) {
                        $idVenta = $egresoLaptop->DetalleVenta->idVenta;
                    } elseif ($egresoLaptop->RegistroProducto && $egresoLaptop->RegistroProducto->DetalleVenta) {
                        $idVenta = $egresoLaptop->RegistroProducto->DetalleVenta->idVenta;
                    } else {
                        $venta = \App\Models\Ventas\Venta::where('numeroOrden', $egresoLaptop->numeroOrden)->first();
                        if ($venta) {
                            $idVenta = $venta->idVenta;
                        }
                    }

                    if ($idVenta && $idEgresoNuevo) {
                        \App\Models\Ventas\DetalleVenta::create([
                            'idVenta'      => $idVenta,
                            'idEgreso'     => $idEgresoNuevo,
                            'idProducto'   => $detalleComponente->idProducto,
                            'idPublicacion' => null,
                            'precioVenta'  => 0,   // No genera ingreso — va en el equipo
                            'cantidad'     => 1,
                            'origenPrecio' => 'TIENDA',
                            'estado'       => 'COMPLETADO',
                        ]);
                    }

                    \Illuminate\Support\Facades\DB::commit();
                    $this->headerService->sendFlashAlerts('Upgrade Exitoso', 'El componente ha sido descontado y su costo (S/ '.number_format($costoSumar, 2).') sumado a la Laptop.', 'success', 'btn-success');
                    return back();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un problema al aplicar el upgrade: ' . $e->getMessage(), 'error', 'btn-danger');
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta acción', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
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

    public function searchProductoAjax(Request $request)
    {
        $query = $request->input('query');
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        $words = array_filter(explode(' ', trim($query)));
        $tipo = $request->input('tipo');

        $queryBuilder = \App\Models\Catalogo\Producto::query()
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
        $calculadora1 = \App\Models\Precios\Calculadora::find(1);
        $calculadora2 = \App\Models\Precios\Calculadora::find(2);

        $tcSunat = $calculadora1 ? $calculadora1->tasaCambio : 3.42;
        $tcFijo = $calculadora2 ? $calculadora2->tasaCambio : 3.80;
        $igv = $calculadora1 ? $calculadora1->igv : 18;
        $facturacion = $calculadora1 ? $calculadora1->facturacion : 1;
        $empresaUnik = \App\Models\Empresa\Empresa::find(2);
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
                $comisionesPorGrupo[$p->idGrupo] = \App\Models\Precios\Comision::where('idGrupoProducto', $p->idGrupo)->with('RangoPrecio')->get();
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
            ->whereIn('RegistroProducto.estado', ['NUEVO', 'ABIERTO', 'DEVOLUCION'])
            ->select('RegistroProducto.idRegistro', 'RegistroProducto.numeroSerie', 'Almacen.descripcion as almacen', 'Almacen.idAlmacen', 'RegistroProducto.estado', 'RegistroProducto.es_herramienta')
            ->orderBy('RegistroProducto.idRegistro', 'desc')
            ->get();

        return response()->json($series);
    }

    private function calcularCostoRealRegistro($registro, $tasaCambio)
    {
        if (!$registro || $registro->es_herramienta == 1) return 0;

        $dc = $registro->DetalleComprobante;
        if (!$dc) return 0;

        $idProducto = $dc->idProducto ?? 0;
        $comp = $dc->Comprobante;

        if ($comp && stripos($comp->numeroComprobante ?? '', 'INVENTARIO') === false && $dc->precioUnitario > 0) {
            $moneda = $comp->moneda ?? 'SOLES';
            $precio = $dc->precioUnitario;
            return (strtoupper($moneda) === 'DOLAR' || strtoupper($moneda) === 'USD') ? $precio * $tasaCambio : $precio;
        }

        // Buscar otro
        $otroDc = \App\Models\Ventas\DetalleComprobante::where('idProducto', $idProducto)
            ->where('precioUnitario', '>', 0)
            ->whereHas('Comprobante', function ($q) {
                $q->where('numeroComprobante', 'NOT LIKE', '%INVENTARIO%');
            })
            ->orderBy('idDetalleComprobante', 'desc')
            ->first();

        if ($otroDc) {
            $mon = $otroDc->Comprobante->moneda ?? 'SOLES';
            $precio = $otroDc->precioUnitario ?? 0;
            return (strtoupper($mon) === 'DOLAR' || strtoupper($mon) === 'USD') ? $precio * $tasaCambio : $precio;
        }

        // Fallback
        $precioDolar = $dc->Producto->precioDolar ?? 0;
        return $precioDolar * $tasaCambio * 1.18;
    }

    public function getCostoRegistro(Request $request)
    {
        $idRegistro = $request->input('idRegistro');
        if (empty($idRegistro)) {
            return response()->json(['costo' => 0]);
        }

        $registro = RegistroProducto::with(['DetalleComprobante.Comprobante', 'DetalleComprobante.Producto'])->find($idRegistro);
        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 3.70;

        $costo = $this->calcularCostoRealRegistro($registro, $tasaCambio);

        return response()->json(['costo' => round($costo, 2)]);
    }

    public function checkStock(Request $request)
    {
        $idRegistro = $request->input('idRegistro');
        if (empty($idRegistro)) {
            return response()->json(['alertaTipo' => null]);
        }

        $registro = RegistroProducto::with('DetalleComprobante.Producto')->find($idRegistro);
        if (!$registro || !$registro->DetalleComprobante || !$registro->DetalleComprobante->Producto) {
            return response()->json(['alertaTipo' => null]);
        }

        $producto = $registro->DetalleComprobante->Producto;
        $idProducto = $producto->idProducto;
        $idAlmacenOrigen = $registro->idAlmacen;
        $stockMin = $producto->stockMin ?? 2;

        // Obtener stock por almacén
        $inventarios = \App\Models\Inventario\Inventario::where('idProducto', $idProducto)->get();
        $almacenes = \App\Models\Inventario\Almacen::all()->keyBy('idAlmacen');

        $stockAlmacenOrigen = 0;
        $stockOtros = [];
        $stockTotal = 0;

        foreach ($inventarios as $inv) {
            $stockTotal += $inv->stock;
            if ($inv->idAlmacen == $idAlmacenOrigen) {
                $stockAlmacenOrigen = $inv->stock;
            } else {
                if ($inv->stock > 0) {
                    $stockOtros[] = [
                        'almacen' => $almacenes->has($inv->idAlmacen) ? $almacenes[$inv->idAlmacen]->descripcion : 'Almacén ' . $inv->idAlmacen,
                        'idAlmacen' => $inv->idAlmacen,
                        'stock' => $inv->stock
                    ];
                }
            }
        }

        // Stock del proveedor
        $invProveedor = \App\Models\Inventario\Inventario_Proveedor::where('idProducto', $idProducto)->first();
        $stockProveedor = $invProveedor ? $invProveedor->stock : 0;

        $almacenOrigenNombre = $almacenes->has($idAlmacenOrigen) ? $almacenes[$idAlmacenOrigen]->descripcion : 'Almacén ' . $idAlmacenOrigen;

        // Determinar tipo de alerta
        // El stock después de este egreso será (stockAlmacenOrigen - 1)
        $stockDespuesEgreso = $stockAlmacenOrigen - 1;
        $stockTotalDespues = $stockTotal - 1;
        $alertaTipo = null;

        if ($stockDespuesEgreso <= $stockMin && count($stockOtros) > 0) {
            $alertaTipo = 'traer_almacen';
        } elseif ($stockTotalDespues <= $stockMin && $stockProveedor > 0) {
            $alertaTipo = 'traer_proveedor';
        }

        return response()->json([
            'producto' => $producto->nombreProducto,
            'stockMin' => $stockMin,
            'stockAlmacenOrigen' => $stockAlmacenOrigen,
            'almacenOrigen' => $almacenOrigenNombre,
            'stockDespuesEgreso' => $stockDespuesEgreso,
            'stockOtrosAlmacenes' => $stockOtros,
            'stockProveedor' => $stockProveedor,
            'stockTotalDespues' => $stockTotalDespues,
            'alertaTipo' => $alertaTipo
        ]);
    }

    public function calcularCostoEnsamble(Request $request)
    {
        $idRegistroPrincipal = $request->input('idRegistroPrincipal');
        $componentes = $request->input('componentes', []);

        $costoBase = 0;
        $modeloPrincipal = '';
        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 3.70;

        if (!empty($idRegistroPrincipal)) {
            $registro = RegistroProducto::with(['DetalleComprobante.Comprobante', 'DetalleComprobante.Producto'])->find($idRegistroPrincipal);
            if ($registro && $registro->DetalleComprobante) {
                $modeloPrincipal = $registro->DetalleComprobante->Producto->modelo ?? '';
                $costoBase = round($this->calcularCostoRealRegistro($registro, $tasaCambio), 2);
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
}
