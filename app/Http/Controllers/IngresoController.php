<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\IngresoProductoServiceInterface;
use App\Services\UsuarioServiceInterface;
use App\Services\ComprobanteServiceInterface;
use App\Services\HeaderServiceInterface;
use App\Services\DivisionPackServiceInterface;
use App\Services\UnionPackServiceInterface;
use App\Models\Catalogo\Producto;
use Illuminate\Support\Facades\Log;

class IngresoController extends Controller
{
    protected $userService;
    protected $ingresoService;
    protected $comprobanteService;
    protected $headerService;
    protected $divisionPackService;
    protected $unionPackService;

    public function __construct(
        HeaderServiceInterface $headerService,
        UsuarioServiceInterface $userService,
        IngresoProductoServiceInterface $ingresoService,
        ComprobanteServiceInterface $comprobanteService,
        DivisionPackServiceInterface $divisionPackService,
        UnionPackServiceInterface $unionPackService
    ) {
        $this->userService = $userService;
        $this->ingresoService = $ingresoService;
        $this->comprobanteService = $comprobanteService;
        $this->headerService = $headerService;
        $this->divisionPackService = $divisionPackService;
        $this->unionPackService = $unionPackService;
    }

    public function index($month, Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                Carbon::setLocale('es');
                $fechacompleta = $month . '-01';
                $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);

                $registros = $this->ingresoService->getByMonth($month, 250, $request->query('filtro'))->appends($request->all());

                if ($request->query('page') || $request->query('filtro')) {
                    $view = view('components.lista_ingresos', ['registros' => $registros, 'container' => $request->query('container')])->render();
                    return response()->json(['html' => $view]);
                }

                $proveedores = $this->ingresoService->getAllLabelProveedor();

                $documentos = $this->ingresoService->getAllTipoComprobante();

                $almacenes = \App\Models\Inventario\Almacen::with('Ubicaciones')->get();

                $estados = [
                    ['value' => 'NUEVO', 'name' => 'Nuevo'],
                    ['value' => 'ABIERTO', 'name' => 'Abierto'],
                    ['value' => 'DEFECTUOSO', 'name' => 'Defectuoso'],
                    ['value' => 'DEVOLUCION', 'name' => 'Devolución'],
                    ['value' => 'ENTREGADO', 'name' => 'Entregado'],
                    ['value' => 'GARANTIA', 'name' => 'Garantía']
                ];

                $filtros = [
                    'users' => $this->ingresoService->filtroUsuario($month),
                    'proveedores' => $this->ingresoService->filtroProveedor($month),
                    'almacenes' => $this->ingresoService->filtroAlmacen($month),
                    'estados' => $this->ingresoService->filtroEstado($month)
                ];


                return view('ingresos.ingresos', [
                    'user' => $userModel,
                    'registros' => $registros,
                    'documentos' => $documentos,
                    'proveedores' => $proveedores,
                    'fecha' => $carbonMonth,
                    'almacenes' => $almacenes,
                    'estados' => $estados,
                    'filtros' => $filtros
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return back();
    }

    public function insertIngreso(Request $request, $comprobante)
    {
        Log::info("=== [insertIngreso] NUEVA PETICION RECIBIDA ===");
        Log::info("[insertIngreso] Metodo: " . $request->method() . " | IP: " . $request->ip() . " | URL: " . $request->fullUrl());
        Log::info("[insertIngreso] PHP Configs -> max_input_vars: " . ini_get('max_input_vars') . " | max_execution_time: " . ini_get('max_execution_time') . " | memory_limit: " . ini_get('memory_limit') . " | post_max_size: " . ini_get('post_max_size'));
        Log::info("[insertIngreso] Content-Length header: " . ($request->header('Content-Length') ?? 'N/A'));
        Log::info("[insertIngreso] Total campos POST recibidos: " . count($request->all()));

        // Si por error de redireccion o recarga del navegador llega como GET, redirigir al documento registrado
        if ($request->isMethod('get')) {
            Log::warning("[insertIngreso] Peticion llego como GET para comprobante enc: {$comprobante}. Redirigiendo a vista documento.");
            return redirect()->route('documento', ['id' => $comprobante, 'bool' => 0]);
        }

        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $idComprobanteDecrypted = null;
        try {
            $idComprobanteDecrypted = decrypt($comprobante);
            Log::info("[insertIngreso] idComprobante descifrado: {$idComprobanteDecrypted}");
        } catch (\Throwable $e) {
            Log::error("[insertIngreso] Error al descifrar parametro comprobante: " . $e->getMessage());
        }

        $userModel = $this->headerService->getModelUser();
        Log::info("[insertIngreso] Usuario: " . ($userModel ? ($userModel->idUser . ' - ' . $userModel->user) : 'NULL'));

        // Si el frontend envió los miles de campos empaquetados en 1 sola variable para evitar bloqueos del servidor:
        if ($request->has('compressed_form_data')) {
            $parsedData = [];
            parse_str($request->input('compressed_form_data'), $parsedData);
            $request->merge($parsedData);
            Log::info("[insertIngreso] Datos descomprimidos desde compressed_form_data exitosamente. Comprobante: " . json_encode($request->input('comprobante')) . " | Total productos en detalle: " . count($request->input('detalle') ?? []));
        }

        $datacomprobante = $request->input('comprobante');
        $detalle = $request->input('detalle');

        Log::info("[insertIngreso] comprobante data: " . json_encode($datacomprobante));

        if (!is_array($detalle)) {
            Log::warning("[insertIngreso] 'detalle' NO ES ARRAY o es NULL. Tipo: " . gettype($detalle));
        } else {
            Log::info("[insertIngreso] 'detalle' contiene " . count($detalle) . " producto(s).");
            foreach ($detalle as $idx => $item) {
                $cantIngreso = isset($item['ingreso']) && is_array($item['ingreso']) ? count($item['ingreso']) : 0;
                Log::info("[insertIngreso] Detalle [{$idx}]: idProducto=" . ($item['producto'] ?? 'NULL') . ", medida=" . ($item['medida'] ?? 'NULL') . ", pUnit=" . ($item['preciounitario'] ?? 'NULL') . ", pTotal=" . ($item['preciototal'] ?? 'NULL') . ", series en 'ingreso'=" . $cantIngreso);
            }
        }

        $tienePermisoVista8 = false;
        if ($userModel && $userModel->Accesos) {
            foreach ($userModel->Accesos as $acceso) {
                if ($acceso->idVista == 8) {
                    $tienePermisoVista8 = true;
                    break;
                }
            }
        }
        Log::info("[insertIngreso] Permiso idVista=8: " . ($tienePermisoVista8 ? 'SI' : 'NO'));

        if (!$tienePermisoVista8) {
            Log::warning("[insertIngreso] RECHAZADO: Usuario no cuenta con permiso idVista=8. Redirigiendo a dashboard.");
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'No tienes permiso para ingresar a esta pestaña'], 403);
            }
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        if (!$datacomprobante) {
            Log::warning("[insertIngreso] RECHAZADO: 'comprobante' esta vacio o nulo en el request! Keys recibidas: " . json_encode(array_keys($request->all())));
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Faltan datos del comprobante (Moneda, Almacén, etc.)'], 422);
            }
            $this->headerService->sendFlashAlerts('Datos Faltantes', 'Revisa los campos del comprobante', 'info', 'btn-warning');
            return back();
        }

        // Verificacion: Ningun producto en detalle puede ser un producto contenedor padre (es_padre == 1)
        if (is_array($detalle)) {
            foreach ($detalle as $item) {
                $idProd = $item['producto'] ?? null;
                if ($idProd) {
                    $producto = Producto::find($idProd);
                    if ($producto && $producto->es_padre) {
                        Log::warning("[insertIngreso] RECHAZADO: El producto {$producto->nombreProducto} es contenedor padre.");
                        $msgPadre = "El producto '{$producto->nombreProducto}' ({$producto->modelo}) es un contenedor Padre/Base de variantes y no puede recibir stock fisico directo.";
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json(['status' => 'error', 'message' => $msgPadre], 422);
                        }
                        $this->headerService->sendFlashAlerts('Producto no valido', $msgPadre, 'warning', 'btn-danger');
                        return back();
                    }
                }
            }
        }

        try {
            Log::info("[insertIngreso] Ejecutando comprobanteService->updateComprobante...");
            $tInicio = microtime(true);
            $this->comprobanteService->updateComprobante($idComprobanteDecrypted, $datacomprobante, $detalle ?? []);
            $duracion = round(microtime(true) - $tInicio, 3);
            Log::info("[insertIngreso] updateComprobante COMPLETADO EXITOSAMENTE en {$duracion}s. Redirigiendo a documentos...");

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Comprobante y series registrados correctamente',
                    'redirect' => route('documento', ['id' => $comprobante, 'bool' => 0])
                ]);
            }

            return redirect()->route('documento', ['id' => $comprobante, 'bool' => 0]);
        } catch (\Throwable $e) {
            Log::error("[insertIngreso] EXCEPCION en updateComprobante: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            Log::error("[insertIngreso] Stacktrace:\n" . $e->getTraceAsString());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocurrio un error en el servidor: ' . $e->getMessage()
                ], 500);
            }
            $this->headerService->sendFlashAlerts('Error al registrar', 'Ocurrio un error en el servidor: ' . $e->getMessage(), 'error', 'btn-danger');
            return back();
        }
    }

    public function searchIngreso(Request $request)
    {
        $query = $request->input('query');

        $results = $this->ingresoService->searchAjaxIngreso($query);

        return response()->json($results);
    }

    public function getOneIngreso(Request $request)
    {
        $query = $request->input('query');

        $results = $this->ingresoService->getOneIngreso($query);

        return response()->json($results);
    }

    public function deleteIngreso(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idIngreso = $request->input('idingreso');
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                $this->ingresoService->deleteIngreso($idIngreso);
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    public function insertComprobante(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $proveedor = $request->input('proveedor');
        $tipoComprobante = $request->input('tipocomprobante');
        $numeroComprobante = $request->input('numerocomprobante');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                if ($proveedor && $tipoComprobante && $numeroComprobante) {
                    $array = array();
                    $array['idProveedor'] = $proveedor;
                    $array['idTipoComprobante'] = $tipoComprobante;
                    $array['idUser'] = $userModel->idUser;
                    $array['numeroComprobante'] = $numeroComprobante;
                    $array['moneda'] = 'SOL';
                    $array['totalCompra'] = 0;
                    $array['fechaRegistro'] = now();
                    $array['estado'] = 'PENDIENTE';

                    $operation = $this->comprobanteService->insertComprobante($array);
                    if ($operation) {
                        return redirect()->route('documento', [encrypt($operation), true]);
                    } else {
                        $this->headerService->sendFlashAlerts('Operacion Fallida', 'Ocurrio un error en la transaccion', 'error', 'btn-danger');
                        return back()->withInput();
                    }
                } else {
                    $this->headerService->sendFlashAlerts('Datos Repetidos', 'Ya se encuentran en la base de datos', 'info', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateRegistro(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idRegistro =  $request->input('idregistro');
        $estado =  $request->input('estado');
        $observacion =  $request->input('observacion');
        $ubicacion_especifica = $request->input('ubicacion_especifica');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 2) {
                if (isset($idRegistro)) {
                    $this->ingresoService->updateRegistro($idRegistro, $estado, $observacion, $ubicacion_especifica);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * AJAX: Verificar si un registro es un pack divisible
     */
    public function verificarPack(Request $request)
    {
        $idRegistro = $request->input('idRegistro');
        $result = $this->divisionPackService->verificarPackDivisible($idRegistro);
        return response()->json($result);
    }

    /**
     * POST: Dividir un pack en sus componentes
     */
    public function dividirPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idRegistro = $request->input('idRegistro');
                    $result = $this->divisionPackService->dividirPack($idRegistro);

                    $this->headerService->sendFlashAlerts(
                        'Pack Dividido',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Dividir',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * POST: Reunir componentes en un pack
     */
    public function reunirPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idProductoPack = $request->input('idProductoPack');
                    $idRegistrosHijos = $request->input('idRegistrosHijos', []);
                    $result = $this->unionPackService->reunirPack($idProductoPack, $idRegistrosHijos);

                    $this->headerService->sendFlashAlerts(
                        'Pack Reunido',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Reunir',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * AJAX: Obtener componentes disponibles para reunión
     */
    public function getComponentesReunion(Request $request)
    {
        $idProductoPack = $request->input('idProductoPack');
        $result = $this->unionPackService->getComponentesParaReunion($idProductoPack);
        return response()->json($result);
    }

    /**
     * AJAX: Lista de packs que se pueden armar con el stock actual
     */
    public function getPacksParaUnion(Request $request)
    {
        $result = $this->unionPackService->getPacksDisponiblesParaUnion();
        return response()->json($result);
    }

    /**
     * AJAX: Componentes requeridos + disponibles para un pack específico (con modelo)
     */
    public function getComponentesUnion(Request $request)
    {
        $idProductoPack = (int) $request->input('idProductoPack');
        $result = $this->unionPackService->getComponentesRequeridosParaUnion($idProductoPack);
        return response()->json($result);
    }

    /**
     * POST: Unir componentes individuales para formar un pack nuevo
     */
    public function unirComponentesEnPack(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 8) {
                try {
                    $idProductoPack   = (int) $request->input('idProductoPack');
                    $idRegistrosHijos = $request->input('idRegistrosHijos', []);
                    $idAlmacenDestino = (int) $request->input('idAlmacenDestino');

                    $result = $this->unionPackService->unirComponentesEnPack(
                        $idProductoPack,
                        array_map('intval', $idRegistrosHijos),
                        $idAlmacenDestino
                    );

                    $this->headerService->sendFlashAlerts(
                        'Pack Armado',
                        $result['message'],
                        'success',
                        'btn-success'
                    );
                    return back();
                } catch (\Exception $e) {
                    $this->headerService->sendFlashAlerts(
                        'Error al Armar Pack',
                        $e->getMessage(),
                        'error',
                        'btn-danger'
                    );
                    return back();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta accion', 'warning', 'btn-danger');
        return back();
    }

    /**
     * AJAX: Buscar un registro de pack por su número de serie
     */
    public function buscarPackPorSerie(Request $request)
    {
        $serie = $request->input('serie');

        $registro = \App\Models\Inventario\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('numeroSerie', $serie)
            ->first();

        if (!$registro) {
            return response()->json(['success' => false, 'message' => 'No se encontró ningún producto con esta serie en el inventario.']);
        }

        if (!$registro->DetalleComprobante || !$registro->DetalleComprobante->Producto) {
            return response()->json(['success' => false, 'message' => 'No se encontró el producto asociado a esta serie.']);
        }

        $producto = $registro->DetalleComprobante->Producto;
        $estado = $registro->estado;

        $esPack = \App\Models\Catalogo\ProductoPack::where('idProductoPack', $producto->idProducto)->exists();

        if (!$esPack) {
            return response()->json(['success' => false, 'message' => 'El producto asociado a esta serie no es un pack.']);
        }

        if ($estado !== 'NUEVO') {
            return response()->json(['success' => false, 'message' => "El pack no puede ser dividido porque su estado actual es: $estado"]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'idRegistro' => $registro->idRegistro,
                'nombreProducto' => $producto->nombreProducto,
                'serie' => $registro->numeroSerie
            ]
        ]);
    }

    /**
     * AJAX: Sugerencias de series de packs
     */
    public function buscarSeriesPackAjax(Request $request)
    {
        $query = $request->input('query');
        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $registros = \App\Models\Inventario\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('numeroSerie', 'like', "%$query%")
            ->where('estado', 'NUEVO')
            ->take(10)
            ->get();

        $resultados = [];
        foreach ($registros as $reg) {
            $prod = $reg->DetalleComprobante->Producto ?? null;
            if ($prod) {
                // Verificar si es pack
                $esPack = \App\Models\Catalogo\ProductoPack::where('idProductoPack', $prod->idProducto)->exists();
                if ($esPack) {
                    $resultados[] = [
                        'serie' => $reg->numeroSerie,
                        'nombreProducto' => $prod->nombreProducto
                    ];
                }
            }
        }

        return response()->json($resultados);
    }

    /**
     * AJAX: Buscar productos que pueden ser padres (packs) filtrados por grupos específicos.
     */
    public function buscarPadresPackAjax(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) return response()->json([]);

        // IDs de grupos permitidos: Cabezales, Tintas, Cartuchos...
        $gruposPermitidos = [124, 155, 156, 157, 158, 159, 44, 78, 79];

        $productos = \App\Models\Catalogo\Producto::whereIn('idGrupo', $gruposPermitidos)
            ->whereIn('idProducto', function ($q) {
                $q->select('idProductoPack')->from('ProductoPack');
            })
            ->where(function ($q) use ($query) {
                $q->where('modelo', 'like', "%$query%")
                    ->orWhere('nombreProducto', 'like', "%$query%")
                    ->orWhere('codigoProducto', 'like', "%$query%")
                    ->orWhere('partNumber', 'like', "%$query%");
            })
            ->select('idProducto', 'nombreProducto', 'modelo', 'codigoProducto', 'partNumber')
            ->take(15)
            ->get();

        $resultados = [];
        foreach ($productos as $prod) {
            $resultados[] = [
                'idProducto' => $prod->idProducto,
                'nombreProducto' => $prod->nombreProducto,
                'modelo' => $prod->modelo,
                'codigo' => $prod->codigoProducto
            ];
        }

        return response()->json($resultados);
    }
}
