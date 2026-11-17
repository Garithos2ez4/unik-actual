<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Transforma el recurso Cliente en un arreglo seguro para respuestas JSON.
     * Solo expone campos necesarios para la interacción del usuario/cliente.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $telefonoStr = trim((string) ($this->telefono ?? ''));
        $telefonoEnmascarado = $telefonoStr !== ''
            ? substr($telefonoStr, 0, 4) . str_repeat('*', max(0, strlen($telefonoStr) - 4))
            : null;

        return [
            'nombre' => $this->nombre,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'nombreCompleto' => trim("{$this->nombre} {$this->apellidoPaterno} {$this->apellidoMaterno}"),
            'idTipoDocumento' => $this->idTipoDocumento,
            'telefono' => $telefonoEnmascarado,
            'correo' => $this->correo,
        ];
    }
}
