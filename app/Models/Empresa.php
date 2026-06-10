<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    public $timestamps = false;
 
    protected $table = 'Empresa';
    
    protected $primaryKey = 'idEmpresa';

    protected $guarded = ['idEmpresa'];
    
    protected $fillable = ['idEmpresa',
                            'rucEmpresa',
                            'razonSocial',
                            'nombreComercial',
                            'colorUno',
                            'colorDos',
                            'colorTres',
                            'logo',
                            'icon',
                            'fondo',
                            'correoEmpresa',
                            'ubicacion',
                            'ubicacionLink',
                            'linkPaginaWeb',
                            'comision'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idEmpresa' => 'int'
    ];
    
     public function EmpresaRedSocial()
    {
        return $this->hasMany(EmpresaRedSocial::class,'idEmpresa','idEmpresa');
    }
    
     public function Publicidad()
    {
        return $this->hasMany(Publicidad::class,'idEmpresa','idEmpresa');
    }
    
     public function CuentasTransferencia()
    {
        return $this->hasMany(CuentasTransferencia::class,'idEmpresa','idEmpresa');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}