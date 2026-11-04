<?php

namespace App\Services\Deltron\Contracts;

interface DeltronCategoryClassifierInterface
{
    /**
     * ID de la categoría en Unike Store que representa este clasificador.
     */
    public function getCategoriaId(): int;

    /**
     * Nombre legible de la categoría en Unike Store.
     */
    public function getCategoriaNombre(): string;

    /**
     * Lista de IDs de GrupoProducto que pertenecen a esta categoría.
     *
     * @return int[]
     */
    public function getGruposIds(): array;

    /**
     * Indica si este clasificador maneja un idGrupo específico.
     */
    public function supportsGrupo(int $idGrupo): bool;

    /**
     * Diccionario de sinónimos y claves de Deltron específico para los grupos de esta categoría.
     * Retorna [CLAVE_DELTRON => idCaracteristica].
     */
    public function getMapaClavesCategoria(?int $idGrupo = null): array;

    /**
     * Normalización de valores específicos por ID de característica dentro de esta categoría.
     * Retorna null si no tiene regla especializada para delegar al fallback universal.
     */
    public function normalizarValorCategoria(int $idCaracteristica, string $valorDeltron, ?string $claveDeltron = null, array $sugerencias = []): ?string;

    /**
     * Reglas de clasificación de alta prioridad que deben evaluarse ANTES
     * que otras categorías generales (ej. Mochilas antes que Laptops, Combos antes que individuales).
     *
     * @param array $context ['catDeltron' => string, 'subDeltron' => string, 'titulo' => string, 'name' => string, 'item' => array]
     * @return int|null idGrupo sugerido o null si no coincide en esta fase
     */
    public function classifyEarly(array $context): ?int;

    /**
     * Reglas principales de clasificación para los grupos de esta categoría.
     *
     * @param array $context ['catDeltron' => string, 'subDeltron' => string, 'titulo' => string, 'name' => string, 'item' => array]
     * @return int|null idGrupo sugerido o null si no pertenece a esta categoría
     */
    public function classify(array $context): ?int;

    /**
     * IDs de características recomendadas/obligatorias por grupo dentro de esta categoría
     * para asegurar compatibilidad total con el ERP y los Mappers de Falabella.
     *
     * @return int[]
     */
    public function getAllowedCaracteristicasForGrupo(int $idGrupo): array;

    /**
     * Enriquecimiento, inferencia desde texto/título y normalización de especificaciones
     * con la misma lógica que los Mappers de Falabella y el ERP.
     *
     * @param int   $idGrupo
     * @param array $specsMapeadas      [idCaracteristica => valor]
     * @param array $rawSpecs           [CLAVE_DELTRON => valor]
     * @param string $textoCompleto     Título + descripción + specs_texto de Deltron
     * @param array $sugerenciasPorChar [idCaracteristica => string[]]
     * @return array [idCaracteristica => valorNormalizado]
     */
    public function enrichAndNormalizeSpecs(
        int $idGrupo,
        array $specsMapeadas,
        array $rawSpecs,
        string $textoCompleto,
        array $sugerenciasPorChar = []
    ): array;
}

