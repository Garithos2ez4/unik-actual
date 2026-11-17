<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transforma el recurso Producto en un arreglo seguro para respuestas JSON de API o vistas.
     * Filtra columnas internas como costos, configuraciones de TC fijas o márgenes sensibles.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'idProducto' => $this->idProducto,
            'codigoProducto' => $this->codigoProducto,
            'nombreProducto' => $this->nombreProducto,
            'partNumber' => $this->partNumber,
            'modelo' => $this->modelo,
            'idMarca' => $this->idMarca,
            'marca' => $this->whenLoaded('MarcaProducto', fn() => $this->MarcaProducto?->nombreMarca),
            'idGrupo' => $this->idGrupo,
            'grupo' => $this->whenLoaded('GrupoProducto', fn() => $this->GrupoProducto?->nombreGrupo),
            'precioDolar' => (float) $this->precioDolar,
            'precioSoles' => $this->when(isset($this->precioSoles), $this->precioSoles),
            'estadoProductoWeb' => $this->estadoProductoWeb,
            'garantia' => $this->garantia,
            'descripcionProducto' => $this->descripcionProducto,
            'imagenes' => array_values(array_filter([
                $this->imagenProducto1,
                $this->imagenProducto2,
                $this->imagenProducto3,
                $this->imagenProducto4,
            ])),
            'slugProducto' => $this->slugProducto,
            'es_padre' => (bool) $this->es_padre,
            'idProductoPadre' => $this->idProductoPadre,
        ];
    }
}
