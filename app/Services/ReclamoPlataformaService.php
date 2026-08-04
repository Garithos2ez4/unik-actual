<?php

namespace App\Services;

use App\Models\Reclamos\ReclamoPlataforma;
use App\Models\Reclamos\TipoReclamoPlataforma;
use App\Models\Reclamos\SeguimientoReclamo;
use App\Models\Reclamos\DiagnosticoReclamo;
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

        // Si el estado general cambia a CERRADO, actualizamos la Garantía a ENTREGADO
        if (isset($data['estadoGeneral']) && $data['estadoGeneral'] === 'CERRADO') {
            if ($reclamo->idRegistro) {
                $garantia = \App\Models\Ventas\Garantia::where('idRegistro', $reclamo->idRegistro)->first();
                if ($garantia) {
                    $garantia->estado = 'ENTREGADO';
                    $garantia->save();
                }
            }
        }

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
        return DB::transaction(function () use ($idReclamo, $data) {
            $data['idReclamoPlataforma'] = $idReclamo;
            $seguimiento = SeguimientoReclamo::create($data);

            // Guardamos el mensaje principal en Detalles (lo que antes era contactoRealizado)
            $seguimiento->Detalles()->create([
                'tipoEvidencia' => 'NINGUNO',
                'mensajeRespuesta' => $data['contactoRealizado'],
                'urlArchivo' => null
            ]);

            if (!empty($data['urlVideo'])) {
                $seguimiento->Detalles()->create([
                    'tipoEvidencia' => 'VIDEO',
                    'urlArchivo' => $data['urlVideo'],
                    'mensajeRespuesta' => 'Evidencia de Video'
                ]);
            }

            if (!empty($data['urlFoto'])) {
                $seguimiento->Detalles()->create([
                    'tipoEvidencia' => 'FOTO',
                    'urlArchivo' => $data['urlFoto'],
                    'mensajeRespuesta' => 'Evidencia de Foto'
                ]);
            }

            return $seguimiento;
        });
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
