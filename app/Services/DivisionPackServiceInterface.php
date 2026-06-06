<?php

namespace App\Services;

interface DivisionPackServiceInterface
{
    /**
     * Divide un pack en sus componentes individuales
     * @param int $idRegistro ID del RegistroProducto del pack
     * @return array Información de la división realizada
     */
    public function dividirPack($idRegistro);

    /**
     * Reúne componentes individuales de vuelta en un pack
     * @param int $idProductoPack ID del producto pack
     * @param array $idRegistrosHijos IDs de los RegistroProducto de los componentes a reunir
     * @return array Información de la reunión realizada
     */
    public function reunirPack($idProductoPack, array $idRegistrosHijos);

    /**
     * Verifica si un registro pertenece a un producto pack y puede ser dividido
     * @param int $idRegistro
     * @return array|null Datos del pack si es divisible, null si no
     */
    public function verificarPackDivisible($idRegistro);

    /**
     * Obtiene los componentes disponibles para reunir en un pack
     * @param int $idProductoPack ID del producto pack
     * @return array Componentes disponibles agrupados por serie
     */
    public function getComponentesParaReunion($idProductoPack);
}
