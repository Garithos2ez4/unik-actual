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
     * Verifica si un registro pertenece a un producto pack y puede ser dividido
     * @param int $idRegistro
     * @return array|null Datos del pack si es divisible, null si no
     */
    public function verificarPackDivisible($idRegistro);
}
