<?php

namespace App\Http\Controllers;

use App\Services\HeaderServiceInterface;
use App\Services\ReclamoPlataformaServiceInterface;
use App\Services\PlataformaServiceInterface;
use Illuminate\Http\Request;
use Throwable;

class ReclamoPlataformaController extends Controller
{
    protected $headerService;
    protected $reclamoService;
    protected $plataformaService;

    public function __construct(
        HeaderServiceInterface $headerService,
        ReclamoPlataformaServiceInterface $reclamoService,
        PlataformaServiceInterface $plataformaService
    ) {
        $this->headerService = $headerService;
        $this->reclamoService = $reclamoService;
        $this->plataformaService = $plataformaService;
    }

    public function index()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 11) {
                $reclamos = $this->reclamoService->getAllReclamos();
                return view('reclamos.index', [
                    'user' => $userModel,
                    'reclamos' => $reclamos
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function create()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 11) {
                $plataformas = \App\Models\Empresa\Plataforma::with('CuentasPlataforma')->get();
                $tipos = $this->reclamoService->getAllTipos();
                $tipoDocumentos = \App\Models\Usuarios\TipoDocumento::all();
                return view('reclamos.create', [
                    'user' => $userModel,
                    'plataformas' => $plataformas,
                    'tipos' => $tipos,
                    'tipoDocumentos' => $tipoDocumentos
                ]);
            }
        }

        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['idUser'] = $this->headerService->getModelUser()->idUser;

            $this->reclamoService->createReclamo($data);

            $this->headerService->sendFlashAlerts('Reclamo registrado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('reclamos.index');
        } catch (Throwable $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back()->withInput();
        }
    }

    public function edit($id)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 11) {
                $reclamo = $this->reclamoService->getReclamoById($id);
                $tipos = $this->reclamoService->getAllTipos();
                $tipoDocumentos = \App\Models\Usuarios\TipoDocumento::all();
                return view('reclamos.edit', [
                    'user' => $userModel,
                    'reclamo' => $reclamo,
                    'tipos' => $tipos,
                    'tipoDocumentos' => $tipoDocumentos
                ]);
            }
        }

        return redirect()->route('dashboard');
    }

    public function update(Request $request, $id)
    {
        try {
            $this->reclamoService->updateReclamo($id, $request->all());
            $this->headerService->sendFlashAlerts('Reclamo actualizado', 'Operación exitosa', 'success', 'btn-success');
            return redirect()->route('reclamos.index');
        } catch (Throwable $e) {
            $this->headerService->sendFlashAlerts('Error', $e->getMessage(), 'error', 'btn-danger');
            return redirect()->back();
        }
    }

    public function addSeguimiento(Request $request, $id)
    {
        try {
            $request->validate([
                'respondioCanal'   => 'required|string',
                'mensajeRespuesta' => 'required|string',
                'urlFoto'          => 'nullable|string|max:500',
                'urlVideo'         => 'nullable|string|max:500'
            ]);

            $userModel = $this->headerService->getModelUser();

            // 1. Guardar el Seguimiento Padre (incluye el mensaje)
            $seguimiento = \App\Models\Reclamos\SeguimientoReclamo::create([
                'idReclamoPlataforma' => $id,
                'idUser'              => $userModel->idUser,
                'respondioCanal'      => $request->input('respondioCanal'),
                'mensajeRespuesta'    => $request->input('mensajeRespuesta')
            ]);

            // 2. ¿Pegó link de Foto? Lo guardamos
            if ($request->filled('urlFoto')) {
                \App\Models\Envios\EvidenciaSeguimiento::create([
                    'idSeguimiento' => $seguimiento->idSeguimiento,
                    'tipoEvidencia' => 'FOTO',
                    'urlArchivo'    => $request->input('urlFoto')
                ]);
            }

            // 3. ¿Pegó link de Video? Lo guardamos
            if ($request->filled('urlVideo')) {
                \App\Models\Envios\EvidenciaSeguimiento::create([
                    'idSeguimiento' => $seguimiento->idSeguimiento,
                    'tipoEvidencia' => 'VIDEO',
                    'urlArchivo'    => $request->input('urlVideo')
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() . ' Línea: ' . $e->getLine()
            ], 500);
        }
    }

    public function historia(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $orden = $request->input('orden');
        $caso = $request->input('caso');

        $reclamos = collect();

        if ($orden || $caso) {
            $query = \App\Models\Reclamos\ReclamoPlataforma::query();
            if ($orden) {
                $query->where('ordenCompra', 'like', "%$orden%");
            }
            if ($caso) {
                $query->where('numeroCaso', 'like', "%$caso%");
            }
            $reclamos = $query->with(['Plataforma', 'Seguimientos.Evidencias', 'Diagnosticos'])->get();
        }

        return view('reclamos.historia', [
            'user' => $userModel,
            'reclamos' => $reclamos,
            'orden' => $orden,
            'caso' => $caso
        ]);
    }

    public function searchAjax(Request $request)
    {
        $query = $request->query('query');
        $field = $request->query('field'); // 'orden' o 'caso'

        if (!$query || strlen($query) < 3) return response()->json([]);

        $results = \App\Models\Reclamos\ReclamoPlataforma::where($field == 'orden' ? 'ordenCompra' : 'numeroCaso', 'like', "%$query%")
            ->select('idReclamoPlataforma', 'ordenCompra', 'numeroCaso', 'idPlataforma')
            ->with('Plataforma:idPlataforma,nombrePlataforma')
            ->distinct()
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function addDiagnostico(Request $request, $id)
    {
        try {
            $data = $request->all();
            $data['idUser'] = $this->headerService->getModelUser()->idUser;
            $this->reclamoService->addDiagnostico($id, $data);
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
