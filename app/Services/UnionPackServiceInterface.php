<?php

namespace App\Services;

interface UnionPackServiceInterface
{
    /**
     * Reúne componentes individuales de vuelta en un pack
     * @param int $idProductoPack ID del producto pack
     * @param array $idRegistrosHijos IDs de los RegistroProducto de los componentes a reunir
     * @return array Información de la reunión realizada
     */
    public function reunirPack($idProductoPack, array $idRegistrosHijos);

    /**
     * Obtiene los componentes disponibles para reunir en un pack (desde una división previa)
     * @param int $idProductoPack ID del producto pack
     * @return array Componentes disponibles agrupados por serie
     */
    public function getComponentesParaReunion($idProductoPack);

    /**
     * Une componentes individuales (no necesariamente de una división previa) para formar un pack nuevo
     * @param int $idProductoPack  ID del producto pack destino
     * @param array $idRegistrosHijos IDs de RegistroProducto a consumir
     * @param int $idAlmacenDestino Almacén donde se creará el nuevo registro pack
     * @return array Resultado de la operación
     */
    public function unirComponentesEnPack(int $idProductoPack, array $idRegistrosHijos, int $idAlmacenDestino): array;

    /**
     * Retorna los productos-pack para los que hay suficiente stock de sus componentes
     * @return array [{idProducto, nombreProducto, componentes}]
     */
    public function getPacksDisponiblesParaUnion(): array;

    /**
     * Dado un producto-pack, retorna sus componentes requeridos y los RegistroProducto NUEVO disponibles
     * @param int $idProductoPack
     * @return array [{idProductoHijo, nombreProducto, cantidadNecesaria, disponibles:[{idRegistro,numeroSerie}]}]
     */
    public function getComponentesRequeridosParaUnion(int $idProductoPack): array;
}
