<?php

namespace App\Services;

class GananciaQueryService
{
    protected $calculadoraService;
    protected $comisionPlataformaService;

    public function __construct(CalculadoraServiceInterface $calculadoraService, ComisionPlataformaService $comisionPlataformaService)
    {
        $this->calculadoraService = $calculadoraService;
        $this->comisionPlataformaService = $comisionPlataformaService;
    }

    public function getSubqueryTc(string $dateCol, float $tcFallback): string
    {
        return "COALESCE((SELECT tasa_cambio FROM historial_tipo_cambio ORDER BY ABS(DATEDIFF(fecha, DATE({$dateCol}))) ASC LIMIT 1), {$tcFallback})";
    }

    public function getSqlExpressions(float $tc): array
    {
        $subqueryTipoCambioCosto = $this->getSubqueryTc('c_inner.fechaRegistro', $tc);
        $costoVentaExpr = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc);

        $falabellaExpr = $this->comisionPlataformaService->getComisionExpr('FALABELLA', 'GrupoProducto.idCategoria', 'GrupoProducto.idGrupoProducto', 'DetalleVenta.precioVenta');
        $comisionFalabellaExpr = "CASE WHEN UPPER(Venta.canal) = 'FALABELLA' THEN {$falabellaExpr} ELSE 0 END";

        $costosComponentesSubInner = $this->calculadoraService->getCostoVentaExpr($subqueryTipoCambioCosto, (string)$tc, 'dv_comp', 'p_comp');
        $costosComponentesSub = "COALESCE((SELECT SUM(({$costosComponentesSubInner}) * dv_comp.cantidad)
        FROM DetalleVenta dv_comp
        LEFT JOIN Producto p_comp ON dv_comp.idProducto = p_comp.idProducto
        WHERE dv_comp.idVenta = DetalleVenta.idVenta AND dv_comp.precioVenta <= 0.10) / 
        GREATEST((SELECT COUNT(*) FROM DetalleVenta dv_main WHERE dv_main.idVenta = DetalleVenta.idVenta AND dv_main.precioVenta > 0.10), 1), 0)";

        $subqueryTcDia = $this->getSubqueryTc('Venta.fechaVenta', $tc);

        $pesoRipleySubqueryInner = function ($col) {
            return "CASE 
                WHEN UPPER($col.caracteristicaProducto) LIKE '%GR%' OR (UPPER($col.caracteristicaProducto) LIKE '%G%' AND UPPER($col.caracteristicaProducto) NOT LIKE '%KG%') 
                THEN CAST(REPLACE($col.caracteristicaProducto, ',', '.') AS DECIMAL(10,3)) / 1000
                ELSE CAST(REPLACE($col.caracteristicaProducto, ',', '.') AS DECIMAL(10,3))
            END";
        };

        $pesoRipleySubquery = "COALESCE(
            (SELECT " . $pesoRipleySubqueryInner('cp52') . "
             FROM Caracteristicas_Producto cp52
             WHERE cp52.idProducto = Producto.idProducto AND cp52.idCaracteristica = 52 LIMIT 1),
            (SELECT " . $pesoRipleySubqueryInner('cp10') . "
             FROM Caracteristicas_Producto cp10
             WHERE cp10.idProducto = Producto.idProducto AND cp10.idCaracteristica = 10 LIMIT 1)
        )";

        $tarifaLogisticaRipleyExpr = $this->comisionPlataformaService->getTarifaEnvioExpr('RIPLEY', $pesoRipleySubquery);
        $ripleyExpr = $this->comisionPlataformaService->getComisionExpr('RIPLEY', 'GrupoProducto.idCategoria', 'GrupoProducto.idGrupoProducto', 'DetalleVenta.precioVenta');
        $comisionRipleyExpr = "CASE WHEN UPPER(Venta.canal) = 'RIPLEY' THEN {$ripleyExpr} + (({$tarifaLogisticaRipleyExpr}) / GREATEST(DetalleVenta.cantidad, 1)) ELSE 0 END";

        $comisionMercadoLibreExpr = "0";

        return [
            'subqueryTipoCambioCosto' => $subqueryTipoCambioCosto,
            'costoVentaExpr' => $costoVentaExpr,
            'comisionFalabellaExpr' => $comisionFalabellaExpr,
            'costosComponentesSub' => $costosComponentesSub,
            'subqueryTcDia' => $subqueryTcDia,
            'pesoRipleySubquery' => $pesoRipleySubquery,
            'tarifaLogisticaRipleyExpr' => $tarifaLogisticaRipleyExpr,
            'ripleyExpr' => $ripleyExpr,
            'comisionRipleyExpr' => $comisionRipleyExpr,
            'comisionMercadoLibreExpr' => $comisionMercadoLibreExpr
        ];
    }

    /**
     * Centraliza el formateo, redondeo y cálculos finales de las ventas para la vista.
     */
    public function formatVentaItem(object $venta, bool $isTienda = false): object
    {
        if (isset($venta->total_ingresos) && !isset($venta->ingresos)) {
            $venta->ingresos = $venta->total_ingresos;
            $venta->total_ingresos = round((float)$venta->total_ingresos, 2);
        }

        if (isset($venta->total_costos) && !isset($venta->costos)) {
            $venta->costos = $venta->total_costos;
            $venta->total_costos = round((float)$venta->total_costos, 2);
        }

        if (isset($venta->ingresos)) {
            $venta->ingresos = round((float)$venta->ingresos, 2);
        }

        $comisionesArestar = 0;

        // Determinar el costo y si debemos restar comisiones desglosadas
        if (isset($venta->costos_base)) {
            $venta->costos_base = round((float)$venta->costos_base, 2);
            $venta->costos = $venta->costos_base;

            // Ripley desglose
            if (isset($venta->comision_ripley_base)) {
                $venta->comision_ripley = round((float)$venta->comision_ripley_base, 2);
                $comisionesArestar += $venta->comision_ripley;
            } elseif (isset($venta->comision_ripley)) {
                $venta->comision_ripley = round((float)$venta->comision_ripley, 2);
                $comisionesArestar += $venta->comision_ripley;
            }

            if (isset($venta->tarifa_peso_ripley)) {
                $venta->tarifa_peso_ripley = round((float)$venta->tarifa_peso_ripley, 2);
                $comisionesArestar += $venta->tarifa_peso_ripley;
            }
        } elseif (isset($venta->costos)) {
            $venta->costos = round((float)$venta->costos, 2);
        }

        // Otras comisiones o variables para redondear
        $commissions = [
            'comision_falabella',
            'comision_ripley',
            'comision_mercadolibre',
            'comision_tienda',
            'tc_dia'
        ];

        foreach ($commissions as $comm) {
            if (isset($venta->$comm)) {
                $venta->$comm = round((float)$venta->$comm, $comm === 'tc_dia' ? 3 : 2);
            }
        }

        // Calculamos la ganancia y el margen
        if (isset($venta->ingresos) && isset($venta->costos)) {
            $venta->ganancia = round($venta->ingresos - $venta->costos - $comisionesArestar, 2);

            if (!isset($venta->margen)) {
                if ($isTienda) {
                    if ($venta->costos > 0) {
                        $venta->margen = round(($venta->ganancia / $venta->costos) * 100, 2);
                    } else {
                        $venta->margen = $venta->ganancia > 0 ? 100 : 0;
                    }
                } else {
                    $venta->margen = $venta->ingresos > 0 ? round(($venta->ganancia / $venta->ingresos) * 100, 2) : 0;
                }
            }
        }

        // Deduplicar modelos
        $modeloField = isset($venta->modelo_raw) ? 'modelo_raw' : (isset($venta->modelo) ? 'modelo' : null);
        if ($modeloField && $venta->$modeloField) {
            $modelos = array_map('trim', explode(',', $venta->$modeloField));
            $counts = array_count_values($modelos);
            $parts = [];
            foreach ($counts as $modelo => $count) {
                $parts[] = $count >= 2 ? "{$modelo} (x{$count})" : $modelo;
            }
            $venta->modelo = implode(', ', $parts);
        }

        if (isset($venta->modelo_raw)) {
            unset($venta->modelo_raw);
        }

        return $venta;
    }
}
