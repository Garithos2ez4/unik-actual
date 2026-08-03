<?php
namespace App\Models\Ventas;

use Illuminate\Database\Eloquent\Model;

class TipoComprobante extends Model
{
    public $timestamps = false;
 
    protected $table = 'TipoComprobante';
    
    protected $primaryKey = 'idTipoComprobante';

    protected $guarded = [''];
    
    protected $fillable = ['descripcion'
                            
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idTipoComprobante' => 'int',
    ];
    
    public function Comprobante()
    {
        return $this->hasMany(Comprobante::class,'idTipoComprobante','idTipoComprobante');
    }
    
    /**
     * Obtener las relaciones del modelo.
     */
}