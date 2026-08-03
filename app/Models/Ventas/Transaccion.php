<?php
namespace App\Models\Ventas;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    public $timestamps = false;
 
    protected $table = 'Transaccion';
    
    protected $primaryKey = 'idTransaccion';

    protected $guarded = ['idTransaccion','idUser'];
    
    protected $fillable = ['descripcion','tipo'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idTransaccion' => 'int',
        'idUser' => 'int'
    ];
    
    public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}