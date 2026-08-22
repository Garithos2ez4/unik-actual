<?php

namespace App\Services;

use App\Models\Ecommerce\ReglaComision;

class ComisionPlataformaService
{
    /**
     * Construye la expresión SQL (CASE WHEN) para el cálculo de la comisión (incluye porcentaje y monto fijo)
     * basándose en las reglas almacenadas en la base de datos.
     *
     * @param string $plataforma Nombre de la plataforma (FALABELLA, RIPLEY, etc.)
     * @param string $colCategoria Columna SQL para idCategoria (ej: GrupoProducto.idCategoria)
     * @param string $colGrupo Columna SQL para idGrupoProducto (ej: GrupoProducto.idGrupoProducto)
     * @param string $colPrecio Columna SQL para precioVenta (ej: DetalleVenta.precioVenta)
     * @return string
     */
    public function getComisionExpr(string $plataforma, string $colCategoria, string $colGrupo, string $colPrecio, string $colFechaVenta = 'Venta.fechaVenta'): string
    {
        $reglas = ReglaComision::where('plataforma', $plataforma)
            ->where('estado', true)
            ->orderBy('prioridad', 'desc')
            ->get();

        if ($reglas->isEmpty()) {
            return "0";
        }

        // Construir CASE para porcentaje
        $exprPorcentaje = "CASE ";
        $hasPorcentaje = false;
        
        foreach ($reglas as $regla) {
            if ($regla->porcentaje_comision === null) continue;
            
            $condicion = $this->buildCondition($regla, $colCategoria, $colGrupo, $colPrecio);
            $fechaCond = $this->buildFechaCondition($regla, $colFechaVenta);
            $exprPorcentaje .= "WHEN ({$condicion}) AND ({$fechaCond}) THEN {$regla->porcentaje_comision} ";
            $hasPorcentaje = true;
        }
        
        $exprPorcentaje .= $hasPorcentaje ? "ELSE 0 END" : "0";

        // Construir CASE para monto fijo
        $exprFijo = "CASE ";
        $hasFijo = false;
        
        foreach ($reglas as $regla) {
            if ($regla->monto_fijo === null) continue;
            
            $condicion = $this->buildCondition($regla, $colCategoria, $colGrupo, $colPrecio);
            $fechaCond = $this->buildFechaCondition($regla, $colFechaVenta);
            $exprFijo .= "WHEN ({$condicion}) AND ({$fechaCond}) THEN {$regla->monto_fijo} ";
            $hasFijo = true;
        }
        
        $exprFijo .= $hasFijo ? "ELSE 0 END" : "0";

        // Comision = (Precio * Porcentaje) + Fijo
        return "(({$colPrecio} * ({$exprPorcentaje})) + ({$exprFijo}))";
    }

    public function getTarifaEnvioExpr(string $plataforma, string $pesoExpr, string $colFechaVenta = 'Venta.fechaVenta'): string
    {
        $tarifas = \App\Models\Ecommerce\ReglaTarifaEnvio::where('plataforma', $plataforma)
            ->where('estado', true)
            ->orderByRaw('peso_maximo IS NULL, peso_maximo ASC')
            ->get();

        if ($tarifas->isEmpty()) {
            return "0";
        }

        $expr = "CASE ";
        $hasExpr = false;
        foreach ($tarifas as $tarifa) {
            $fechaCond = $this->buildFechaCondition($tarifa, $colFechaVenta);
            if ($tarifa->peso_maximo === null) {
                $expr .= "WHEN ({$fechaCond}) THEN {$tarifa->monto_fijo} ";
            } else {
                $expr .= "WHEN ({$pesoExpr}) <= {$tarifa->peso_maximo} AND ({$fechaCond}) THEN {$tarifa->monto_fijo} ";
            }
            $hasExpr = true;
        }
        $expr .= $hasExpr ? "ELSE 0 END" : "0";
        
        return $expr;
    }

    private function buildFechaCondition($regla, $colFechaVenta): string
    {
        $conds = [];
        if ($regla->fecha_inicio) {
            $conds[] = "DATE({$colFechaVenta}) >= '{$regla->fecha_inicio}'";
        }
        if ($regla->fecha_fin) {
            $conds[] = "DATE({$colFechaVenta}) <= '{$regla->fecha_fin}'";
        }
        return empty($conds) ? "1=1" : implode(' AND ', $conds);
    }

    private function buildCondition($regla, $colCategoria, $colGrupo, $colPrecio): string
    {
        if ($regla->tipo_condicion === 'DEFAULT') {
            return "1=1";
        }

        if ($regla->tipo_condicion === 'CATEGORIA_IN') {
            $valores = implode(',', array_map('trim', explode(',', $regla->valor_condicion)));
            return "{$colCategoria} IN ({$valores})";
        }

        if ($regla->tipo_condicion === 'GRUPO_IN') {
            $valores = implode(',', array_map('trim', explode(',', $regla->valor_condicion)));
            return "{$colGrupo} IN ({$valores})";
        }

        if ($regla->tipo_condicion === 'PRECIO_MENOR_IGUAL') {
            return "{$colPrecio} <= {$regla->valor_condicion}";
        }

        return "1=1";
    }
}
