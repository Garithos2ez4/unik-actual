<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class UbicacionEstante extends Model
{
    protected $table = 'UbicacionEstante';
    protected $primaryKey = 'idUbicacionExacta';

    protected $fillable = [
        'idAlmacen',
        'nombre_rack',
        'fila_estante',
        'estado',
    ];

    protected $casts = [
        'estado'        => 'boolean',
        'fila_estante'  => 'integer',
    ];

    /** Relación con Almacén */
    public function Almacen()
    {
        return $this->belongsTo(Almacen::class, 'idAlmacen', 'idAlmacen');
    }

    /** Accessor: nombre legible tipo "Vitrina Alta 1 - Fila 2" */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre_rack} - Fila {$this->fila_estante}";
    }
}
