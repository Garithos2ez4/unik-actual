<?php

namespace App\Services\MercadoLibre;

use App\Models\Catalogo\Producto;
use App\Models\Catalogo\PlataformaMapper;
use App\Services\MercadoLibre\Contracts\MercadoLibreCategoryMapper;

class MercadoLibreCategoryResolverService
{
    /**
     * Resuelve qué mapper utilizar para un producto dado, consultando
     * la tabla `plataforma_mappers` para la plataforma MERCADOLIBRE.
     * Busca primero por grupo de producto y luego por categoría.
     */
    public function resolve(Producto $producto): MercadoLibreCategoryMapper
    {
        $plataformaML = \App\Models\Empresa\Plataforma::where('nombrePlataforma', 'like', '%Mercado%')->first();
        $idPlataformaML = $plataformaML ? $plataformaML->idPlataforma : 2;

        $idCategoria = optional($producto->GrupoProducto)->idCategoria;
        $idGrupo = $producto->idGrupo;

        // 1. Buscar por grupo de producto (más específico)
        if ($idGrupo) {
            $mapperInfo = PlataformaMapper::where('idPlataforma', $idPlataformaML)
                ->where('idGrupoProducto', $idGrupo)
                ->first();

            if ($mapperInfo && $mapperInfo->mapper_class && class_exists($mapperInfo->mapper_class)) {
                $class = $mapperInfo->mapper_class;
                return new $class();
            }
        }

        // 2. Buscar por categoría (más general)
        if ($idCategoria) {
            $mapperInfo = PlataformaMapper::where('idPlataforma', $idPlataformaML)
                ->where('idCategoria', $idCategoria)
                ->first();

            if ($mapperInfo && $mapperInfo->mapper_class && class_exists($mapperInfo->mapper_class)) {
                $class = $mapperInfo->mapper_class;
                return new $class();
            }
        }

        throw new \Exception("No hay mapper de MercadoLibre configurado para el producto {$producto->idProducto}");
    }
}
