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

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $fecha = $request->query('fecha', date('Y-m-d'));
                $envios = EnvioProvincia::with(['Usuario', 'Cliente', 'Plataforma', 'CuentaPlataforma', 'Agencia', 'Destino', 'Productos.Producto', 'Detalle'
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

                return view('envios.create', [
                    'user' => $userModel,
                    'plataformas' => $plataformas,
                    'agencias' => $agencias,
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

            $envio = EnvioProvincia::create($data);

            if ($request->filled('dir') || $request->filled('ref')) {
                \App\Models\EnvioProvinciaDetalle::create([
                    'idEnvioProvincia' => $envio->idEnvioProvincia,
                    'dir' => $request->input('dir'),
                    'ref' => $request->input('ref')
                ]);
            }

            // Registrar múltiples productos
            $productos = $request->input('productos', []);
            foreach ($productos as $prodData) {
                if (!empty($prodData['idProducto'])) {
                    \App\Models\EnvioProvinciaProducto::create([
                        'idEnvioProvincia' => $envio->idEnvioProvincia,
                        'idProducto' => $prodData['idProducto'],
                        'cantidad' => $prodData['cantidad'] ?? 1,
                        'nota_producto' => $prodData['nota_producto'] ?? null
                    ]);
                }
            }

            $this->headerService->sendFlashAlerts('Envío registrado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('envios.index');
        } catch (Throwable $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $envio = EnvioProvincia::with(['Detalle', 'Destino.Provincia.Departamento', 'Productos.Producto'])->findOrFail($id);
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
            $envio = EnvioProvincia::findOrFail($id);
            $data = $request->all();
            $data['pago_destino'] = $request->has('pago_destino') ? 1 : 0;
            $envio->update($data);

            if ($request->filled('dir') || $request->filled('ref')) {
                \App\Models\EnvioProvinciaDetalle::updateOrCreate(
                    ['idEnvioProvincia' => $envio->idEnvioProvincia],
                    [
                        'dir' => $request->input('dir'),
                        'ref' => $request->input('ref')
                    ]
                );
            } else {
                \App\Models\EnvioProvinciaDetalle::where('idEnvioProvincia', $envio->idEnvioProvincia)->delete();
            }

            // Actualizar múltiples productos
            \App\Models\EnvioProvinciaProducto::where('idEnvioProvincia', $envio->idEnvioProvincia)->delete();
            
            $productos = $request->input('productos', []);
            foreach ($productos as $prodData) {
                if (!empty($prodData['idProducto'])) {
                    \App\Models\EnvioProvinciaProducto::create([
                        'idEnvioProvincia' => $envio->idEnvioProvincia,
                        'idProducto' => $prodData['idProducto'],
                        'cantidad' => $prodData['cantidad'] ?? 1,
                        'nota_producto' => $prodData['nota_producto'] ?? null
                    ]);
                }
            }

            $this->headerService->sendFlashAlerts('Envío actualizado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('envios.index');
        } catch (Throwable $e) {
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

    public function storeAgencia(Request $request)
    {
        try {
            $agencia = Agencia::create([
                'nombre' => $request->nombre,
                'estado' => 1
            ]);
            return response()->json(['success' => true, 'agencia' => $agencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeProvincia(Request $request)
    {
        try {
            $provincia = Provincia::create([
                'nombre' => $request->nombre
            ]);
            return response()->json(['success' => true, 'provincia' => $provincia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function storeDestino(Request $request)
    {
        try {
            $destino = Destino::create([
                'idProvincia' => $request->idProvincia,
                'nombre' => $request->nombre
            ]);
            return response()->json(['success' => true, 'destino' => $destino]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
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
            ->where(function($queryGroup) use ($terminos, $query) {
                $queryGroup->where('numeroSerie', 'LIKE', '%' . $query . '%')
                    ->orWhereHas('DetalleComprobante.Producto', function ($q) use ($terminos) {
                        $q->where(function ($subQ) use ($terminos) {
                            foreach ($terminos as $termino) {
                                $subQ->where(function($wQ) use ($termino) {
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
        $idsFromRegistros = $registros->map(function($r) {
            return $r->DetalleComprobante?->Producto?->idProducto;
        })->filter()->unique()->toArray();

        // 2. Buscar directamente en tabla Producto (para productos sin serialización)
        $productosFormateados = collect();

        $productos = \App\Models\Producto::where(function($q) use ($terminos) {
                $q->where(function ($subQ) use ($terminos) {
                    foreach ($terminos as $termino) {
                        $subQ->where(function($wQ) use ($termino) {
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
        $productosFormateados = $productos->map(function($prod) {
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
            if ($request->filled('nombre_oficina')) {
                $existe = SubAgencia::where('idAgencia', $request->idAgencia)
                    ->where('idDestino', $request->idDestino)
                    ->where('nombre_oficina', $request->nombre_oficina)
                    ->first();
                if ($existe) {
                    return response()->json(['success' => false, 'message' => 'Ya existe una oficina con este nombre en el destino seleccionado.']);
                }
            }

            $subagencia = SubAgencia::create([
                'idAgencia' => $request->idAgencia,
                'idDestino' => $request->idDestino,
                'nombre_oficina' => $request->nombre_oficina,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'estado' => 1
            ]);
            return response()->json(['success' => true, 'subagencia' => $subagencia]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }
}
