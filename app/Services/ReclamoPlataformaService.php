<?php

namespace App\Services;

use App\Models\ReclamoPlataforma;
use App\Models\TipoReclamoPlataforma;
use App\Models\SeguimientoReclamo;
use App\Models\DiagnosticoReclamo;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReclamoPlataformaService implements ReclamoPlataformaServiceInterface
{
    public function getAllReclamos()
    {
        return ReclamoPlataforma::with(['Usuario', 'Plataforma', 'CuentaPlataforma', 'TipoReclamo', 'Cliente', 'Publicacion', 'ProductoFisico'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getReclamoById($id)
    {
        return ReclamoPlataforma::with(['Seguimientos.Operador', 'Diagnosticos.Tecnico', 'Diagnosticos.ProductoFisico', 'Cliente'])
            ->findOrFail($id);
    }

    public function createReclamo(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['codigoReclamo'] = $this->generateCodigo();
            return ReclamoPlataforma::create($data);
        });
    }

    public function updateReclamo($id, array $data)
    {
        $reclamo = ReclamoPlataforma::findOrFail($id);
        $reclamo->update($data);
        return $reclamo;
    }

    public function getAllTipos()
    {
        return TipoReclamoPlataforma::where('estado', 'ACTIVO')->get();
    }

    public function createTipo(array $data)
    {
        return TipoReclamoPlataforma::create($data);
    }


    public function addSeguimiento($idReclamo, array $data)
    {
        $data['idReclamoPlataforma'] = $idReclamo;
        return SeguimientoReclamo::create($data);
    }

    public function addDiagnostico($idReclamo, array $data)
    {
        $data['idReclamoPlataforma'] = $idReclamo;
        return DiagnosticoReclamo::create($data);
    }

    private function generateCodigo()
    {
        $lastReclamo = ReclamoPlataforma::orderBy('idReclamoPlataforma', 'desc')->first();
        $nextId = $lastReclamo ? $lastReclamo->idReclamoPlataforma + 1 : 1;
        return 'REC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
