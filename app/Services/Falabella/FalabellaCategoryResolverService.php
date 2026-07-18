<?php

namespace App\Services\Falabella;

use App\Models\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;
use App\Services\Falabella\Mappers\LaptopMapper;
use App\Services\Falabella\Mappers\MonitorMapper;
use App\Services\Falabella\Mappers\ImpresoraMapper;

/**
 * Resuelve qué FalabellaCategoryMapper corresponde a un producto
 * según su idCategoria. Para agregar una nueva categoría:
 *   1. Crea un nuevo Mapper en App\Services\Falabella\Mappers\
 *   2. Agrega su idCategoria en el switch de resolve()
 */
class FalabellaCategoryResolverService
{
    // idCategoria de la base de datos → Mapper
    const CATEGORIA_LAPTOP    = 1;
    const CATEGORIA_MONITOR   = 3;
    const CATEGORIA_IMPRESORA = 6;

    public function resolve(Producto $producto): FalabellaCategoryMapper
    {
        $grupo = $producto->GrupoProducto;
        $idCat = $grupo ? (int) $grupo->idCategoria : 0;
        $idGrp = $grupo ? (int) $grupo->idGrupoProducto : 0;

        if (in_array($idGrp, [155, 156, 157, 158, 159, 160, 169])) {
            return new \App\Services\Falabella\Mappers\SuministrosMapper();
        }

        if (in_array($idGrp, [49])) {
            return new \App\Services\Falabella\Mappers\ProcesadorMapper();
        }

        if (in_array($idGrp, [24, 117])) {
            return new \App\Services\Falabella\Mappers\TecladoMapper();
        }

        return match ($idCat) {
            self::CATEGORIA_LAPTOP    => new LaptopMapper(),
            self::CATEGORIA_IMPRESORA => new ImpresoraMapper(),
            default                   => new MonitorMapper(), // monitores + fallback
        };
    }
}
