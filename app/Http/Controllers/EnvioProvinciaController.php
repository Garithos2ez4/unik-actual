<?php

namespace App\Http\Controllers;

use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;
use App\Models\EnvioProvincia;
use App\Models\Agencia;
use App\Models\Provincia;
use App\Models\Destino;
use App\Models\Plataforma;
use App\Models\Departamento;
use App\Models\SubAgencia;
use Throwable;

class EnvioProvinciaController extends Controller
{
    protected $headerService;
    protected $envioService;

    public function __construct(HeaderServiceInterface $headerService, \App\Services\EnvioProvinciaServiceInterface $envioService)
    {
        $this->headerService = $headerService;
        $this->envioService = $envioService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $fecha = $request->query('fecha', date('Y-m-d'));
                $envios = EnvioProvincia::with([
                    'Usuario',
                    'Cliente',
                    'Plataforma',
                    'CuentaPlataforma',
                    'Agencia',
                    'Destino',
                    'Productos.Producto',
                    'Detalle'
                ])->whereDate('fecha_envio', $fecha)
                    ->orderBy('fecha_envio', 'desc')
                    ->get();

                return view('envios.index', [
                    'user' => $userModel,
                    'envios' => $envios,
                    'fecha' => $fecha
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function create()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $plataformas = Plataforma::with('CuentasPlataforma')->get();
                $agencias = Agencia::where('estado', 1)->orderBy('nombre', 'asc')->get();
                $departamentos = Departamento::orderBy('nombre', 'asc')->get();
                $provincias = Provincia::orderBy('nombre', 'asc')->get();
                $documentos = \App\Models\TipoDocumento::all();
                $tiposPaquete = \App\Models\TipoPaqueteEnvio::with('dimensiones')->where('estado', 1)->get();

                return view('envios.create', [
                    'user' => $userModel,
                    'plataformas' => $plataformas,
                    'agencias' => $agencias,
                    'tiposPaquete' => $tiposPaquete,
                    'departamentos' => $departamentos,
                    'provincias' => $provincias,
                    'documentos' => $documentos
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['idUser'] = $this->headerService->getModelUser()->idUser;
            $data['fecha_envio'] = date('Y-m-d');
            $data['pago_destino'] = $request->has('pago_destino') ? 1 : 0;
            $productos = $request->input('productos', []);

            $this->envioService->createEnvio($data, $productos);

            $this->headerService->sendFlashAlerts('Envío registrado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('envios.index');
        } catch (\Throwable $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $envio = $this->envioService->getEnvioById($id);
                $plataformas = Plataforma::with('CuentasPlataforma')->get();
                $agencias = Agencia::where('estado', 1)->orderBy('nombre', 'asc')->get();
                $departamentos = Departamento::orderBy('nombre', 'asc')->get();

                $selectedDeptoId = optional(optional(optional($envio->Destino)->Provincia)->Departamento)->idDepartamento;
                $provincias = $selectedDeptoId ? Provincia::where('idDepartamento', $selectedDeptoId)->orderBy('nombre', 'asc')->get() : collect();

                $selectedProvId = optional(optional($envio->Destino)->Provincia)->idProvincia;
                $destinos = $selectedProvId ? Destino::where('idProvincia', $selectedProvId)->orderBy('nombre', 'asc')->get() : collect();

                $subagencias = ($envio->idAgencia && $envio->idDestino) ? SubAgencia::where('idAgencia', $envio->idAgencia)->where('idDestino', $envio->idDestino)->orderBy('nombre_oficina', 'asc')->get() : collect();
                $documentos = \App\Models\TipoDocumento::all();

                return view('envios.edit', [
                    'user' => $userModel,
                    'envio' => $envio,
                    'plataformas' => $plataformas,
                    'agencias' => $agencias,
                    'departamentos' => $departamentos,
                    'provincias' => $provincias,
                    'destinos' => $destinos,
                    'subagencias' => $subagencias,
                    'documentos' => $documentos
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            $data['pago_destino'] = $request->has('pago_destino') ? 1 : 0;
            $productos = $request->input('productos', []);

            $this->envioService->updateEnvio($id, $data, $productos);

            $this->headerService->sendFlashAlerts('Envío actualizado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('envios.index');
        } catch (\Throwable $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back()->withInput();
        }
    }

    public function pdf(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $ids = $request->query('ids');

                $query = EnvioProvincia::with(['Usuario', 'Cliente', 'Plataforma', 'CuentaPlataforma', 'Agencia', 'Destino.Provincia', 'Productos.Producto.GrupoProducto', 'Productos.Producto.MarcaProducto', 'Detalle']);

                if (!empty($ids)) {
                    $idArray = explode(',', $ids);
                    $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
                    $fecha = null;
                } else {
                    $fecha = $request->query('fecha', date('Y-m-d'));
                    $envios = $query->whereDate('fecha_envio', $fecha)->get();
                }

                return view('envios.pdf', [
                    'envios' => $envios,
                    'fecha' => $fecha
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function listaProductos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $ids = $request->query('ids');

                $query = EnvioProvincia::with(['Usuario', 'Cliente', 'Plataforma', 'Agencia', 'Productos.Producto']);

                if (!empty($ids)) {
                    $idArray = explode(',', $ids);
                    $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
                    $fecha = null;
                } else {
                    $fecha = $request->query('fecha', date('Y-m-d'));
                    $envios = $query->whereDate('fecha_envio', $fecha)->get();
                }

                return view('envios.lista_productos', [
                    'envios' => $envios,
                    'fecha' => $fecha
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function excel(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $ids = $request->query('ids');
        $query = EnvioProvincia::with(['Cliente', 'Agencia', 'Destino', 'Productos.Producto', 'Detalle']);

        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
        } else {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $envios = $query->whereDate('fecha_envio', $fecha)->get();
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="envios_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($envios) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM para Excel
            fputcsv($file, [
                'DESTINATARIO (DOC)',
                'TELF. DESTINATARIO',
                'CONTACTO (DOC)',
                'TELF. CONTACTO',
                'NRO GRR',
                'ORIGEN',
                'DESTINO',
                'MERCADERIA',
                'ALTO',
                'ANCHO',
                'LARGO',
                'PESO',
                'CANTIDAD'
            ], ';');

            foreach ($envios as $envio) {
                $mercaderia = $envio->Productos->map(function ($p) {
                    return $p->Producto->nombreProducto ?? 'Producto';
                })->implode(' / ');

                if (empty($mercaderia)) {
                    $mercaderia = 'PAQUETE L';
                }

                $cantidad = $envio->Productos->sum('cantidad');
                if ($cantidad == 0) $cantidad = 1; // Para evitar cantidad 0

                fputcsv($file, [
                    optional($envio->Cliente)->numeroDocumento ?? '',
                    optional($envio->Cliente)->telefono ?? '',
                    '', // CONTACTO (DOC)
                    '', // TELF. CONTACTO
                    $envio->numero_guia ?? '',
                    'AV. GRAU', // ORIGEN
                    optional($envio->Destino)->nombre ?? '',
                    $mercaderia,
                    optional($envio->Detalle)->alto ?? '0.1', // ALTO
                    optional($envio->Detalle)->ancho ?? '0.1', // ANCHO
                    optional($envio->Detalle)->largo ?? '0.1', // LARGO
                    optional($envio->Detalle)->peso ?? '7',   // PESO
                    $cantidad
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function etiquetas(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $ids = $request->query('ids');
        $query = EnvioProvincia::with(['Cliente', 'Agencia', 'Destino.Provincia.Departamento', 'SubAgencia', 'Productos.Producto', 'Detalle']);

        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
        } else {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $envios = $query->whereDate('fecha_envio', $fecha)->get();
        }

        return view('envios.etiquetas2', compact('envios', 'fecha'));
    }

    public function storeAgencia(Request $request)
    {
        try {
            $agencia = $this->envioService->createAgencia($request->nombre);
            return response()->json(['success' => true, 'agencia' => $agencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeProvincia(Request $request)
    {
        try {
            $provincia = $this->envioService->createProvincia($request->nombre);
            return response()->json(['success' => true, 'provincia' => $provincia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeDestino(Request $request)
    {
        try {
            $destino = $this->envioService->createDestino($request->idProvincia, $request->nombre);
            return response()->json(['success' => true, 'destino' => $destino]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function getUltimoEnvioCliente($idCliente)
    {
        $ultimoEnvio = $this->envioService->getUltimoEnvioCliente($idCliente);

        if ($ultimoEnvio) {
            return response()->json([
                'success' => true,
                'data' => $ultimoEnvio
            ]);
        }

        return response()->json(['success' => false]);
    }

    public function buscarRegistro(Request $request)
    {
        $query = trim($request->get('query'));

        if (empty($query)) {
            return response()->json([]);
        }

        $terminos = array_filter(explode(' ', $query), 'strlen');

        // 1. Buscar productos serializados en RegistroProducto
        $registros = \App\Models\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('estado', '!=', 'ENTREGADO')
            ->where('estado', '!=', 'INVALIDO')
            ->where(function ($queryGroup) use ($terminos, $query) {
                $queryGroup->where('numeroSerie', 'LIKE', '%' . $query . '%')
                    ->orWhereHas('DetalleComprobante.Producto', function ($q) use ($terminos) {
                        $q->where(function ($subQ) use ($terminos) {
                            foreach ($terminos as $termino) {
                                $subQ->where(function ($wQ) use ($termino) {
                                    $wQ->where('nombreProducto', 'LIKE', '%' . $termino . '%')
                                        ->orWhere('codigoProducto', 'LIKE', '%' . $termino . '%')
                                        ->orWhere('modelo', 'LIKE', '%' . $termino . '%');
                                });
                            }
                        });
                    });
            })
            ->take(50)
            ->get();

        // Recopilar idProducto de registros para evitar duplicados
        $idsFromRegistros = $registros->map(function ($r) {
            return $r->DetalleComprobante?->Producto?->idProducto;
        })->filter()->unique()->toArray();

        // 2. Buscar directamente en tabla Producto (para productos sin serialización)
        $productosFormateados = collect();

        $productos = \App\Models\Producto::where(function ($q) use ($terminos) {
            $q->where(function ($subQ) use ($terminos) {
                foreach ($terminos as $termino) {
                    $subQ->where(function ($wQ) use ($termino) {
                        $wQ->where('nombreProducto', 'LIKE', '%' . $termino . '%')
                            ->orWhere('codigoProducto', 'LIKE', '%' . $termino . '%')
                            ->orWhere('modelo', 'LIKE', '%' . $termino . '%');
                    });
                }
            });
        })
            ->whereNotIn('idProducto', $idsFromRegistros)
            ->take(50)
            ->get();

        // Formatear para mantener la misma estructura que el frontend espera
        $productosFormateados = $productos->map(function ($prod) {
            return [
                'numeroSerie' => null,
                'detalle_comprobante' => [
                    'producto' => [
                        'idProducto' => $prod->idProducto,
                        'nombreProducto' => $prod->nombreProducto,
                        'codigoProducto' => $prod->codigoProducto,
                    ]
                ]
            ];
        });

        // 3. Combinar ambos resultados
        $resultados = array_merge($registros->toArray(), $productosFormateados->toArray());

        return response()->json($resultados);
    }

    public function getDestinosPorProvincia($idProvincia)
    {
        $destinos = Destino::where('idProvincia', $idProvincia)
            ->orderBy('nombre', 'asc')
            ->get(['idDestino', 'nombre']);

        return response()->json($destinos);
    }

    public function getProvinciasPorDepartamento($idDepartamento)
    {
        $provincias = Provincia::where('idDepartamento', $idDepartamento)
            ->orderBy('nombre', 'asc')
            ->get(['idProvincia', 'nombre']);

        return response()->json($provincias);
    }

    public function getSubAgenciasPorAgenciaYDestino($idAgencia, $idDestino)
    {
        $subagencias = SubAgencia::where('idAgencia', $idAgencia)
            ->where('idDestino', $idDestino)
            ->where('estado', 1)
            ->orderBy('nombre_oficina', 'asc')
            ->get(['idSubAgencia', 'nombre_oficina', 'direccion', 'telefono']);

        return response()->json($subagencias);
    }

    public function storeSubAgencia(Request $request)
    {
        try {
            $subagencia = $this->envioService->createSubAgencia(
                $request->idAgencia,
                $request->idDestino,
                $request->nombre_oficina,
                $request->direccion,
                $request->telefono
            );
            return response()->json(['success' => true, 'subagencia' => $subagencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }


    public function generarLinkPublico(Request $request)
    {
        try {
            $userModel = $this->headerService->getModelUser();

            $hasAccess = false;
            foreach ($userModel->Accesos as $acceso) {
                if ($acceso->idVista == 14) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso para generar enlaces.']);
            }

            $token = \Illuminate\Support\Str::random(32);

            $solicitud = \App\Models\SolicitudEnvio::create([
                'idUser' => $userModel->idUser,
                'token' => $token,
                'estado' => 'PENDIENTE',
                'token_expires_at' => now()->addMinutes(20),
            ]);

            $baseUrl = rtrim(config('app.public_envio_url', url('/')), '/');
            $link = "{$baseUrl}/formulario-envio/{$token}";

            return response()->json([
                'success' => true,
                'link' => $link,
                'idSolicitud' => $solicitud->idSolicitud,
                'expira_en' => '20 minutos'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }


    public function obtenerSolicitudes(Request $request)
    {
        try {
            // Actualizar a EXPIRADO las solicitudes que ya pasaron su tiempo límite
            \App\Models\SolicitudEnvio::where('estado', 'PENDIENTE')
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<', now())
                ->update(['estado' => 'EXPIRADO']);

            // Solo traemos idUser y user para no cargar la bandeja (que es muy pesada)
            $query = \App\Models\SolicitudEnvio::with(['Usuario:idUser,user']);

            // Si pasan parametro ?estado=PROCESADO (u otro)
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $solicitudes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Regenera un nuevo token para una solicitud existente (extiende la expiración).
     */
    public function regenerarLink($id)
    {
        try {
            $solicitud = \App\Models\SolicitudEnvio::findOrFail($id);
            $token = \Illuminate\Support\Str::random(32);

            $solicitud->update([
                'token' => $token,
                'estado' => 'PENDIENTE',
                'token_expires_at' => now()->addMinutes(20),
            ]);

            $baseUrl = rtrim(config('app.public_envio_url', url('/')), '/');
            $link = "{$baseUrl}/formulario-envio/{$token}";

            return response()->json([
                'success' => true,
                'link' => $link,
                'expira_en' => '20 minutos'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function trackFlores($id)
    {
        try {
            $envio = EnvioProvincia::findOrFail($id);

            if (empty($envio->numero_guia)) {
                return response()->json(['success' => false, 'message' => 'El envío no tiene un número de guía registrado.']);
            }

            // Validar que tenga el formato SERIE-NUMERO
            if (!str_contains($envio->numero_guia, '-')) {
                return response()->json(['success' => false, 'message' => 'El formato de la guía debe ser SERIE-NUMERO (Ej: 5984-49745364).']);
            }

            $partes = explode('-', $envio->numero_guia);
            $serie = trim($partes[0]);
            $numero = trim($partes[1]);

            // Flores usa diferentes códigos de documento: 09 (Guía), 03 (Boleta), 01 (Factura)
            // Intentaremos con los 3 hasta obtener resultados.
            $codigosDocumento = ['09', '03', '01'];
            $resultadosList = [];
            $ultimoMensaje = 'No se pudo conectar con el servidor de Transporte Flores.';

            foreach ($codigosDocumento as $codDoc) {
                $url = 'https://sfe.floreshnos.pe/ConsultaEncomiendas/Encomienda/ListEncomienda';
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->get($url, [
                        'Serie' => $serie,
                        'Numero' => $numero,
                        'Codi_documento' => $codDoc,
                        'Codi_empresa' => '1',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['EsCorrecto']) && $data['EsCorrecto'] == true) {
                        $list = $data['Valor']['List'] ?? [];
                        if (count($list) > 0) {
                            $resultadosList = $list;
                            break; // Encontramos datos, salimos del bucle
                        }
                    } else {
                        $ultimoMensaje = $data['Mensaje'] ?? 'Error desconocido de la agencia Flores.';
                    }
                }
            }

            if (count($resultadosList) > 0) {
                return response()->json([
                    'success' => true,
                    'data' => $resultadosList
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [] // Devolvemos vacío para que el frontend maneje el mensaje
                ]);
            }

            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el servidor de Transporte Flores.']);

        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $th->getMessage()]);
        }
    }
}
