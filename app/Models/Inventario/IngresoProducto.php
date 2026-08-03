<?php
namespace App\Models\Inventario;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class IngresoProducto extends Model
{
    public $timestamps = false;
 
    protected $table = 'IngresoProducto';
    
    protected $primaryKey = 'idIngreso';

    protected $guarded = ['idIngreso'];
    
    protected $fillable = ['idIngreso',
                            'idRegistro',
                            'idUser',
                            'fechaIngreso'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idIngreso' => 'int',
        'idRegistro' => 'int',
        'idUser' => 'int',
        'fechaIngreso' => 'date'
    ];
    
     public function RegistroProducto()
    {
        return $this->hasOne(RegistroProducto::class,'idRegistro','idRegistro');
    }
    
     public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}