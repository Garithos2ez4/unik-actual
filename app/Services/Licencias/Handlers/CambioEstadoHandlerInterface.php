<?php

namespace App\Services\Licencias\Handlers;

use App\Models\Licencias\Licencia;
interface CambioEstadoHandlerInterface
{
    public function handle(Licencia $licencia, array $datosForm): void;
}
