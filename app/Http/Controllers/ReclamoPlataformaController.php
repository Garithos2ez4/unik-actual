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
                $plataformas = \App\Models\Plataforma::with('CuentasPlataforma')->get();
                $tipos = $this->reclamoService->getAllTipos();
                $tipoDocumentos = \App\Models\TipoDocumento::all();
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
                $tipoDocumentos = \App\Models\TipoDocumento::all();
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
            $data = $request->all();
            $data['idUser'] = $this->headerService->getModelUser()->idUser;
            $this->reclamoService->addSeguimiento($id, $data);
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
