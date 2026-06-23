<?php

namespace App\Http\Controllers;

use App\Services\HeaderServiceInterface;
use Carbon\Carbon;
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
                    'Detalle',
                    'SubAgencia'
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
            $data['fecha_envio'] = resolver_fecha_envio();
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
                $tiposPaquete = \App\Models\TipoPaqueteEnvio::all();

                return view('envios.edit', [
                    'user' => $userModel,
                    'envio' => $envio,
                    'plataformas' => $plataformas,
                    'agencias' => $agencias,
                    'departamentos' => $departamentos,
                    'provincias' => $provincias,
                    'destinos' => $destinos,
                    'subagencias' => $subagencias,
                    'documentos' => $documentos,
                    'tiposPaquete' => $tiposPaquete
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
        $query = EnvioProvincia::with(['Cliente', 'Agencia', 'Destino', 'SubAgencia', 'Productos.Producto', 'Detalle', 'Dimension.tipoPaquete'])
            ->whereHas('Agencia', function ($q) {
                $q->where('nombre', 'LIKE', '%SHALOM%');
            });

        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
        } else {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $envios = $query->whereDate('fecha_envio', $fecha)->get();
        }

        // Cargar la plantilla oficial de Shalom (preserva metadata, versión, hojas ocultas, validaciones)
        $templatePath = storage_path('Formato-Pro-Masivo-2026_06_12_13.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Plantilla de Shalom no encontrada. Coloque el archivo en storage/');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet(0); // Hoja1

        // Limpiar fila de ejemplo (fila 2) que trae la plantilla
        for ($col = 1; $col <= 13; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '2', '');
        }

        // Cargar nombres de subagencias de Shalom desde la Hoja2 del Excel cargado para mapeo exacto
        $shalomNamesMap = [];
        $normalize = function ($str) {
            $str = mb_strtoupper($str, 'UTF-8');
            $unwanted_array = [
                'Š' => 'S',
                'š' => 's',
                'Ž' => 'Z',
                'ž' => 'z',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'A',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ø' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'B',
                'ß' => 'Ss',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'a',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'â' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'o',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'b',
                'ÿ' => 'y'
            ];
            $str = strtr($str, $unwanted_array);
            $str = preg_replace('/\s+/', ' ', $str);
            return trim($str);
        };

        if ($spreadsheet->getSheetCount() > 1) {
            $sheet2 = $spreadsheet->getSheet(1); // Hoja2
            $highestRow = $sheet2->getHighestRow();
            for ($r = 1; $r <= $highestRow; $r++) {
                $nameVal = $sheet2->getCell('B' . $r)->getValue();
                if ($nameVal) {
                    $trimmed = trim($nameVal);
                    if ($trimmed !== '') {
                        $normalized = $normalize($trimmed);
                        $shalomNamesMap[$normalized] = $trimmed;
                    }
                }
            }
        }

        // Escribir datos de envíos
        $row = 2;
        foreach ($envios as $envio) {
            // Shalom exige que MERCADERIA sea un valor de su lista desplegable, no el nombre del producto
            // Valores válidos: SOBRE, PAQUETE XXS, PAQUETE XS, PAQUETE S, PAQUETE M, PAQUETE L
            $mercaderia = 'PAQUETE L'; // Default fallback

            if (optional(optional($envio->Dimension)->tipoPaquete)->nombre) {
                $nombreTipo = strtoupper(trim($envio->Dimension->tipoPaquete->nombre));
                $validos = ['SOBRE', 'PAQUETE XXS', 'PAQUETE XS', 'PAQUETE S', 'PAQUETE M', 'PAQUETE L'];
                if (in_array($nombreTipo, $validos)) {
                    $mercaderia = $nombreTipo;
                }
            }

            // Por solicitud del usuario, la cantidad en el Excel masivo SIEMPRE debe ser 1
            $cantidad = 1;

            $destinoRaw = optional($envio->SubAgencia)->nombre_oficina ?? optional($envio->Destino)->nombre ?? '';
            $partes = explode(' / ', $destinoRaw);
            $destino = trim(end($partes));
            $zona = trim(optional($envio->Destino)->nombre ?? '');

            // Shalom a veces añade " - NOMBRE_ZONA" al final del nombre de la sucursal en su API, pero en su Excel lo omiten.
            $suffix = " - " . $zona;
            if (!empty($zona) && substr($destino, -strlen($suffix)) === $suffix) {
                $destino = trim(substr($destino, 0, -strlen($suffix)));
            }

            // Intentar buscar match exacto o parcial en la lista de shalom_excel.txt
            if (!empty($shalomNamesMap)) {
                $destinoNorm = $normalize($destino);
                if (isset($shalomNamesMap[$destinoNorm])) {
                    $destino = $shalomNamesMap[$destinoNorm];
                } else {
                    foreach ($shalomNamesMap as $normKey => $exactValue) {
                        if (strpos($normKey, $destinoNorm) !== false || strpos($destinoNorm, $normKey) !== false) {
                            $destino = $exactValue;
                            break;
                        }
                    }
                }
            }

            $sheet->setCellValueExplicit('A' . $row, optional($envio->Cliente)->numeroDocumento ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, optional($envio->Cliente)->telefono ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, ''); // CONTACTO (DOC)
            $sheet->setCellValue('D' . $row, ''); // TELF. CONTACTO
            $sheet->setCellValue('E' . $row, $envio->numero_guia ?? ''); // NRO GRR
            $sheet->setCellValue('F' . $row, 'JR. RAYMONDI'); // ORIGEN
            $sheet->setCellValue('G' . $row, $destino); // DESTINO
            $sheet->setCellValue('H' . $row, $mercaderia); // MERCADERIA
            $sheet->setCellValue('I' . $row, floatval(optional($envio->Dimension)->alto_final ?? optional($envio->Detalle)->alto ?? 0.1));
            $sheet->setCellValue('J' . $row, floatval(optional($envio->Dimension)->ancho_final ?? optional($envio->Detalle)->ancho ?? 0.1));
            $sheet->setCellValue('K' . $row, floatval(optional($envio->Dimension)->largo_final ?? optional($envio->Detalle)->largo ?? 0.1));
            $sheet->setCellValue('L' . $row, floatval(optional($envio->Dimension)->peso_final ?? optional($envio->Detalle)->peso ?? 7));
            $sheet->setCellValue('M' . $row, intval($cantidad));

            $row++;
        }

        // Generar archivo .xlsx (mismo formato que la plantilla original)
        $filename = 'Formato-Pro-Masivo-' . date('Y_m_d_H') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'shalom_excel_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        // 1. Buscar Registros donde el numero de serie coincida con el query (búsqueda manual de serie)
        $registrosPorSerie = \App\Models\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('estado', '!=', 'ENTREGADO')
            ->where('estado', '!=', 'INVALIDO')
            ->where('numeroSerie', 'LIKE', '%' . $query . '%')
            ->take(15)
            ->get();

        // 2. Buscar Productos que coincidan con los términos (nombre, codigo, modelo)
        $productos = \App\Models\Producto::where(function ($subQ) use ($terminos) {
            foreach ($terminos as $termino) {
                $subQ->where(function ($wQ) use ($termino) {
                    $wQ->where('nombreProducto', 'LIKE', '%' . $termino . '%')
                        ->orWhere('codigoProducto', 'LIKE', '%' . $termino . '%')
                        ->orWhere('modelo', 'LIKE', '%' . $termino . '%');
                });
            }
        })
        ->take(20)
        ->get();

        $resultadosFinales = collect();
        $productosAgregados = [];

        // Agregar los encontrados por serie directamente, pero solo UNO por producto
        // Así, si buscan "524", no agregamos los 15 seriales de la misma tinta, sino solo el primero.
        // Pero si buscan "100004", encontrará ese específicamente y lo agregará.
        foreach ($registrosPorSerie as $reg) {
            $idProd = $reg->DetalleComprobante?->Producto?->idProducto;
            if ($idProd && !in_array($idProd, $productosAgregados)) {
                $resultadosFinales->push($reg);
                $productosAgregados[] = $idProd;
            }
        }

        // Para los productos encontrados que no han sido agregados por la búsqueda de serie,
        // buscar UN registro disponible (el primero)
        foreach ($productos as $prod) {
            if (!in_array($prod->idProducto, $productosAgregados)) {
                $primerRegistro = \App\Models\RegistroProducto::with(['DetalleComprobante.Producto'])
                    ->where('estado', '!=', 'ENTREGADO')
                    ->where('estado', '!=', 'INVALIDO')
                    ->whereHas('DetalleComprobante', function ($q) use ($prod) {
                        $q->where('idProducto', $prod->idProducto);
                    })
                    ->first();

                if ($primerRegistro) {
                    $resultadosFinales->push($primerRegistro);
                } else {
                    $resultadosFinales->push([
                        'numeroSerie' => null,
                        'detalle_comprobante' => [
                            'producto' => [
                                'idProducto' => $prod->idProducto,
                                'nombreProducto' => $prod->nombreProducto,
                                'codigoProducto' => $prod->codigoProducto,
                                'modelo' => $prod->modelo,
                            ]
                        ]
                    ]);
                }
                $productosAgregados[] = $prod->idProducto;
            }
        }

        return response()->json($resultadosFinales->values()->all());
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
                'idUser'           => $userModel->idUser,
                'token'            => $token,
                'estado'           => 'PENDIENTE',
                'token_expires_at' => now()->addMinutes(20),
            ]);

            $baseUrl = rtrim(config('app.public_envio_url', url('/')), '/');
            $link = "{$baseUrl}/formulario-envio/{$token}";

            // Determinar si se está generando el link después del horario de cierre (17:00 Lima)
            $esDespues5pm    = es_despues_del_corte();
            $fechaRegistroReal = calcular_fecha_real_legible();

            return response()->json([
                'success'           => true,
                'link'              => $link,
                'idSolicitud'       => $solicitud->idSolicitud,
                'expira_en'         => '20 minutos',
                'es_despues_5pm'    => $esDespues5pm,
                'fecha_registro_real' => $fechaRegistroReal,
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

    public function syncAllAgencias()
    {
        // Al sincronizar 6 agencias masivamente se superan los 30 segundos límite de PHP,
        // por lo que desactivamos el límite de tiempo.
        set_time_limit(0);

        try {
            // No truncamos la tabla porque las sucursales ya están siendo referenciadas en envíos anteriores.
            // Los comandos de Artisan ya tienen lógica de "updateOrCreate" interna.

            // Ejecutar los comandos de sincronización
            \Illuminate\Support\Facades\Artisan::call('sync:marvisur');
            \Illuminate\Support\Facades\Artisan::call('sync:emtrafesa');
            \Illuminate\Support\Facades\Artisan::call('sync:olva');
            \Illuminate\Support\Facades\Artisan::call('sync:shalom');
            \Illuminate\Support\Facades\Artisan::call('sync:espinoza');
            \Illuminate\Support\Facades\Artisan::call('sync:flores');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización masiva completada correctamente.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización masiva: ' . $th->getMessage()
            ], 500);
        }
    }
}
