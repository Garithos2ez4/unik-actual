<?php
namespace App\Models\Empresa;

use Illuminate\Database\Eloquent\Model;

class RedSocial extends Model
{
 
    protected $table = 'RedSocial';

    protected $guarded = ['idRedSocial'];
    
    protected $fillable = ['plataforma',
                            'imagenRedSocial'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idRedSocial' => 'int'
    ];
    
    public function EmpresaRedSocial()
    {
        return $this->hasMany(EmpresaRedSocial::class,'idRedSocial','idRedSocial');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}