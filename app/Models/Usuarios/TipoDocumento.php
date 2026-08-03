<?php
namespace App\Models\Usuarios;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
 
    protected $table = 'TipoDocumento';

    protected $guarded = ['idTipoDocumento'];
    
    protected $primaryKey = 'idTipoDocumento';
    
    protected $fillable = ['idTipoDocumento',
                            'descripcion'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idTipoDocumento' => 'int'
    ];
    /**
     * Obtener las relaciones del modelo.
     */
    
     public function Cliente()
     {
        return $this->hasMany(Cliente::class,'idTipoDocumento','idTipoDocumento');
     }
}