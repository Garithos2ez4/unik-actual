<?php
namespace App\Models\Precios;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class RegistroUpdate extends Model
{
    public $timestamps = false;
 
    protected $table = 'registroUpdate';
    
    protected $primaryKey = 'idRegistro';

    protected $guarded = ['idRegistro'];
    
    protected $fillable = ['columna',
                            'ultimaFecha',
                            'cantidadUpdate',
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'ultimaFecha' => 'date',
        'cantidadUpdate' => 'int',
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    
}