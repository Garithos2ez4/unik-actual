<?php

namespace App\Services;

use App\Models\Precios\Calculadora;
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
    public function getGruposCostoExcepcion(): string
    {
        return "46, 48, 120, 129, 130, 160, 161";
    }

    public function getCostoVentaExpr(string $tcInner, string $tcOuter = null, string $aliasDetalleVenta = 'DetalleVenta', string $aliasProducto = 'Producto'): string
    {
        $tcOuter = $tcOuter ?? $tcInner;
        $gruposExcepcion = $this->getGruposCostoExcepcion();

        return "COALESCE(
            (SELECT CASE 
                    WHEN rp_inner.es_herramienta = 1 THEN 0
                    -- Componente de upgrade: INVENTARIO sin precio unitario pero con precioCompra asignado manualmente
                    WHEN dc_inner.precioUnitario <= 0 AND dc_inner.precioCompra > 0 AND c_inner.numeroComprobante LIKE '%INVENTARIO%' THEN dc_inner.precioCompra
                    WHEN dc_inner.precioUnitario <= 0 THEN NULL
                    WHEN c_inner.numeroComprobante LIKE '%INVENTARIO%' THEN NULL
                    WHEN c_inner.moneda = 'DOLAR' THEN dc_inner.precioUnitario * {$tcInner} 
                    ELSE dc_inner.precioUnitario 
                END
             FROM EgresoProducto ep_inner
             INNER JOIN RegistroProducto rp_inner ON rp_inner.idRegistro = ep_inner.idRegistro
             INNER JOIN DetalleComprobante dc_inner ON dc_inner.idDetalleComprobante = rp_inner.idDetalleComprobante
             INNER JOIN Comprobante c_inner ON c_inner.idComprobante = dc_inner.idComprobante
             WHERE ep_inner.idEgreso = {$aliasDetalleVenta}.idEgreso 
               AND (
                   dc_inner.precioUnitario > 0 
                   OR rp_inner.es_herramienta = 1 
                   OR (dc_inner.precioUnitario <= 0 AND dc_inner.precioCompra > 0 AND c_inner.numeroComprobante LIKE '%INVENTARIO%')
                   OR (SELECT idGrupo FROM Producto p WHERE p.idProducto = dc_inner.idProducto) IN ({$gruposExcepcion})
               )
             LIMIT 1),
            (NULLIF({$aliasProducto}.precioDolar, 0) * {$tcOuter} * 1.18),
            (SELECT CASE 
                    WHEN c2.moneda = 'DOLAR' THEN dc2.precioUnitario * COALESCE((SELECT hs.tasa_cambio FROM historial_tipo_cambio hs ORDER BY ABS(DATEDIFF(hs.fecha, DATE(c2.fechaRegistro))) ASC LIMIT 1), {$tcOuter})
                    ELSE dc2.precioUnitario 
                END
             FROM DetalleComprobante dc2
             INNER JOIN Comprobante c2 ON c2.idComprobante = dc2.idComprobante
             WHERE dc2.idProducto = {$aliasProducto}.idProducto
               AND dc2.precioUnitario > 0.50
               AND c2.numeroComprobante NOT LIKE '%INVENTARIO%'
             ORDER BY dc2.idDetalleComprobante DESC
             LIMIT 1),
            0
        )";
    }
}
