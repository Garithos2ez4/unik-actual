<?php

namespace App\Services\Falabella;

use App\Models\Catalogo\Producto;
use App\Services\Falabella\Contracts\FalabellaCategoryMapper;
use App\Services\Falabella\Mappers\LaptopMapper;
use App\Services\Falabella\Mappers\MonitorMapper;
use App\Services\Falabella\Mappers\ImpresoraMapper;
use App\Services\Falabella\Mappers\CableMapper;
use App\Services\Falabella\Mappers\MouseMapper;

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

        // 1. Buscar primero por Grupo (Tiene prioridad específica)
        $mapperConfig = \App\Models\Catalogo\PlataformaMapper::whereHas('plataforma', function($q) {
                $q->where('nombrePlataforma', 'like', '%Falabella%');
            })
            ->where('idGrupoProducto', $idGrp)
            ->first();

        // 2. Si no hay regla de Grupo, buscar por Categoría general
        if (!$mapperConfig) {
            $mapperConfig = \App\Models\Catalogo\PlataformaMapper::whereHas('plataforma', function($q) {
                    $q->where('nombrePlataforma', 'like', '%Falabella%');
                })
                ->where('idCategoria', $idCat)
                ->first();
        }

        // 3. Instanciar la clase dinámicamente si existe en la BD
        if ($mapperConfig && $mapperConfig->mapper_class && class_exists($mapperConfig->mapper_class)) {
            return new $mapperConfig->mapper_class();
        }

        // Fallback por defecto si no se encuentra mapeo
        return new MonitorMapper(); 
    }
}
