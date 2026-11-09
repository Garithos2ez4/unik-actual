<?php

namespace App\ViewModels;

use App\Models\Catalogo\Producto;

class ProductoViewModel
{
    public function __construct(
        public Producto $producto
    ) {}

    /**
     * Devuelve la clase CSS de Bootstrap según el estado del producto en la web.
     */
    public function estadoColor(): string
    {
        return match ($this->producto->estadoProductoWeb) {
            'DISPONIBLE' => 'text-success',
            'OFERTA'     => 'text-danger',
            default      => 'text-danger text-decoration-line-through',
        };
    }

    /**
     * Devuelve la clase de badge de Bootstrap según el estado del producto.
     */
    public function estadoBadgeClass(): string
    {
        return match ($this->producto->estadoProductoWeb) {
            'DISPONIBLE'    => 'bg-success',
            'OFERTA'        => 'bg-danger',
            'LIQUIDACION'   => 'bg-warning text-dark',
            'EXCLUSIVO'     => 'bg-primary',
            'AGOTADO'       => 'bg-secondary',
            'DESCONTINUADO' => 'bg-dark',
            default         => 'bg-secondary',
        };
    }

    /**
     * Devuelve el texto amigable para mostrar en vistas.
     */
    public function estadoLabel(): string
    {
        return match ($this->producto->estadoProductoWeb) {
            'DISPONIBLE'    => 'Disponible',
            'OFERTA'        => 'Oferta',
            'LIQUIDACION'   => 'Liquidación',
            'EXCLUSIVO'     => 'Exclusivo',
            'AGOTADO'       => 'Agotado',
            'DESCONTINUADO' => 'Descontinuado',
            default         => (string) $this->producto->estadoProductoWeb,
        };
    }
}

