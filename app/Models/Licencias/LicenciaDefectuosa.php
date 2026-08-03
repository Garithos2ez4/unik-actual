<?php

namespace App\Models\Licencias;
use App\Models\Empresa\Preveedor;

use Illuminate\Database\Eloquent\Model;

class LicenciaDefectuosa extends Model
{
    protected $table = 'licencias_defectuosas';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    
     /**
     * Relación con Licencia
     */
    public function licencia()
    {
        return $this->belongsTo(Licencia::class, 'id_licencia');
    }

    /**
     * Relación con Licencias Recuperadas
     * Una defectuosa puede tener varias recuperadas asociadas.
     */
    public function licenciasRecuperadas()
    {
        return $this->hasMany(LicenciaRecuperada::class, 'serial_defectuosa', 'clave_key');
    }
    public function proveedor()
    {
        return $this->belongsTo(Preveedor::class, 'idProveedor', 'idProveedor');
    }
    
}
