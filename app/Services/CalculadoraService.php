<?php

namespace App\Services;

use App\Models\Calculadora;
use App\Repositories\CalculadoraRepositoryInterface;
use App\Repositories\PlataformaRepositoryInterface;
use App\Repositories\CategoriaProductoRepositoryInterface;
use App\Repositories\ComisionRepositoryInterface;
use App\Repositories\RegistroUpdateRepositoryInterface;
use App\Repositories\HistorialTipoCambioRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CalculadoraService implements CalculadoraServiceInterface
{
    protected $calcRepository;
    protected $plataformaRepository;
    protected $categoriaRepository;
    protected $comisionRepository;
    protected $registroRepository;
    protected $historialRepository;

    public function __construct(
        CalculadoraRepositoryInterface $calcRepository,
        PlataformaRepositoryInterface $plataformaRepository,
        CategoriaProductoRepositoryInterface $categoriaRepository,
        ComisionRepositoryInterface $comisionRepository,
        RegistroUpdateRepositoryInterface $registroRepository,
        HistorialTipoCambioRepositoryInterface $historialRepository
    ) {
        $this->calcRepository = $calcRepository;
        $this->plataformaRepository = $plataformaRepository;
        $this->categoriaRepository = $categoriaRepository;
        $this->comisionRepository = $comisionRepository;
        $this->registroRepository = $registroRepository;
        $this->historialRepository = $historialRepository;
    }
    //Get al primer registro con el api de la sunat para el cambio del dolar
    public function get()
    {
        return $this->calcRepository->get();
    }
    //Get al registro con el id(2) con una tasa de cambio fija(editable) 
    public function getTasaFija()
    {
        return $this->calcRepository->findById();
    }
    public function allComision()
    {
        return $this->comisionRepository->all();
    }
    public function getTasaCambio()
    {
        $tc = $this->calcRepository->get()->tasaCambio;
        $this->updateTipoCambio($tc);
        return $this->calcRepository->get()->tasaCambio;
    }
    public function getIgv()
    {
        $igv = $this->calcRepository->get()->value('igv');
        return ($igv / 100) + 1;
    }
    public function getComisionByRelation($table)
    {
        return $this->plataformaRepository->getByRelation($table);
    }
    public function getAllLabelCategory()
    {
        $categoriaModel = $this->categoriaRepository->all();
        $categoria = $categoriaModel->map(function ($cat) {
            return [
                'idCategoria' => $cat->idCategoria,
                'nombreCategoria' => $cat->nombreCategoria,
                'GrupoProducto' => $cat->GrupoProducto
            ];
        });
        return $categoria;
    }

    public function updateTipoCambio($backup)
    {
        $switch = false;
        $horaActual = date("H:i:s");
        $fechaActual = now()->format('Y-m-d');
        $lastUpdate = $this->registroRepository->get();

        if (!empty($lastUpdate) && isset($lastUpdate->ultimaFecha)) {
            if ($lastUpdate->ultimaFecha->format('Y-m-d') != $fechaActual && $horaActual > '10:30:00') {
                $switch = true;
            }
        } else {
            // Si no hay registro previo, forzar la actualización
            $switch = true;
        }

        if ($switch) {
            $tc = $this->getApiDolar();
            if ($tc != null) {
                $this->calcRepository->updateTC($tc);
                $this->registroRepository->update();
                $this->historialRepository->updateOrCreateByDate($fechaActual, $tc);
            } else {
                $this->calcRepository->updateTC($backup);
                $this->registroRepository->update();
                $this->historialRepository->updateOrCreateByDate($fechaActual, $backup);
            }
        } else {
            // Asegurarnos de que el historial del día exista, por si el switch fue false
            $this->historialRepository->updateOrCreateByDate($fechaActual, $backup);
        }
    }

    public function getApiDolar()
    {
        try {
            $fecha = now()->format('Y-m-d');
            $response = Http::withOptions([
                'verify' => app()->isProduction(), // Solo verifica SSL en produccion
            ])->get('https://api.apis.net.pe/v2/sunat/tipo-cambio', [
                'fecha' => $fecha,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['venta'] ?? null;
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    public function obtenerCambioDolar()
    {
        $calculadora = $this->calcRepository->get();
        $this->updateTipoCambio($calculadora->tasaCambio);
        return $calculadora->tasaCambio;
    }

    public function obtenerCambioDolarFijo()
    {
        $calculadora = $this->calcRepository->findById();
        return $calculadora ? $calculadora->tasaCambio : null;
    }
}
