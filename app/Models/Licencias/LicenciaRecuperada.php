<?php

namespace App\Models\Licencias;
use App\Models\Empresa\Preveedor;

use Illuminate\Database\Eloquent\Model;             

class LicenciaRecuperada extends Model
{
    protected $table = 'licencias_recuperadas';
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
     * Relación con LicenciaDefectuosa
     */
    public function licenciaDefectuosa()
    {
        return $this->belongsTo(LicenciaDefectuosa::class, 'serial_defectuosa', 'clave_key');
    }
    public function proveedor()
    {
        return $this->belongsTo(Preveedor::class,'IdProveedor','IdProveedor');   
    }
    
}
