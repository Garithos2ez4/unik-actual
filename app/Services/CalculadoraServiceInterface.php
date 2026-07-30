<?php

namespace App\Services;

interface CalculadoraServiceInterface
{
    public function get();
    public function getTasaFija();
    public function getTasaCambio();
    public function getIgv();
    public function getComisionByRelation($table);
    public function getAllLabelCategory();
    public function allComision();
    public function updateTipoCambio($backup);
    public function getApiDolar();
    public function obtenerCambioDolar();
    public function obtenerCambioDolarFijo();
    public function getGruposCostoExcepcion(): string;
    public function getCostoVentaExpr(string $tcInner, string $tcOuter = null, string $aliasDetalleVenta = 'DetalleVenta', string $aliasProducto = 'Producto'): string;
}
