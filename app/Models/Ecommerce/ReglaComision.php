<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ReglaComision extends Model
{
    protected $table = 'reglas_comisiones';
    protected $guarded = ['id'];

    public function getValorCondicionNombresAttribute()
    {
        if (!$this->valor_condicion) {
            return '-';
        }

        if ($this->tipo_condicion === 'GRUPO_IN') {
            $ids = array_map('trim', explode(',', $this->valor_condicion));
            $nombres = \App\Models\Catalogo\GrupoProducto::whereIn('idGrupoProducto', $ids)
                ->pluck('nombreGrupo')
                ->toArray();
            return !empty($nombres) ? implode(', ', $nombres) : $this->valor_condicion;
        }

        if ($this->tipo_condicion === 'CATEGORIA_IN') {
            $ids = array_map('trim', explode(',', $this->valor_condicion));
            $nombres = \App\Models\Catalogo\CategoriaProducto::whereIn('idCategoria', $ids)
                ->pluck('nombreCategoria')
                ->toArray();
            return !empty($nombres) ? implode(', ', $nombres) : $this->valor_condicion;
        }

        return $this->valor_condicion;
    }
}
