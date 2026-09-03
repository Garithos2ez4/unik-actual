<?php

namespace App\Http\Controllers\Envios;

use App\Http\Controllers\Controller;

use App\Services\HeaderServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Envios\EnvioProvincia;
use App\Models\Envios\Agencia;
use App\Models\Envios\Provincia;
use App\Models\Envios\Destino;
use App\Models\Empresa\Plataforma;
use App\Models\Envios\Departamento;
use App\Models\Envios\SubAgencia;
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

    public function toggleDespachado(Request $request)
    {
        try {
            $id = $request->input('idEnvioProvincia');
            $estado = $request->input('despachado');

            $detalle = \App\Models\Envios\EnvioProvinciaDetalle::firstOrCreate(
                ['idEnvioProvincia' => $id],
                ['entrega_domicilio' => 0, 'dir' => null, 'ref' => null]
            );

            $detalle->despachado = $estado ? 1 : 0;
            $detalle->save();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
                $documentos = \App\Models\Usuarios\TipoDocumento::all();
                $tiposPaquete = \App\Models\Envios\TipoPaqueteEnvio::with('dimensiones')->where('estado', 1)->get();

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
                $documentos = \App\Models\Usuarios\TipoDocumento::all();
                $tiposPaquete = \App\Models\Envios\TipoPaqueteEnvio::all();

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
}

