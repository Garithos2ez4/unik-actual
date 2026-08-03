<?php

namespace App\Models\Licencias;
use App\Models\Inventario\RegistroProducto;

use Illuminate\Database\Eloquent\Model;

class LicenciaUsada extends Model
{
    protected $table = 'licencias_usadas';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'id' => 'int',
        'id_licencia' => 'int',
        'id_registro_producto' => 'int',
    ];
    
    /**
     * Relación con Licencia
     * La licencia usada pertenece a una licencia.
     */
    public function licencia()
    {
        return $this->belongsTo(Licencia::class, 'id_licencia');
    }

    /**
     * Relación con RegistroProducto
     * Opcional: si la licencia usada se relaciona con un producto.
     */
    public function registroProducto()
    {
        return $this->belongsTo(RegistroProducto::class, 'id_registro_producto');
    }
}

