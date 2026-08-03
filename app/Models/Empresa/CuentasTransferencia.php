<?php
namespace App\Models\Empresa;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CuentasTransferencia extends Model
{
    public $timestamps = false;
    protected $table = 'CuentasTransferencia';
    
    protected $primaryKey = 'idCuentaBancaria';

    protected $guarded = ['idCuentaBancaria'];
    
    protected $fillable = ['idCuentaBancaria',
                            'idBanco',
                            'idEmpresa',
                            'numeroCuenta',
                            'tipoMoneda',
                            'tipoCuenta',
                            'titular'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idCuentaBancaria' => 'int',
        'idBanco' => 'int',
        'idEmpresa' => 'int',
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function Empresa()
    {
        return $this->belongsTo(Empresa::class,'idEmpresa','idEmpresa');
    }
    
    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'idBanco', 'idBanco');
    }
    
}