<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EgresoProducto extends Model
{
    public $timestamps = false;
 
    protected $table = 'EgresoProducto';
    
    protected $primaryKey = 'idEgreso';

    protected $guarded = ['idEgreso'];
    
    protected $fillable = ['idEgreso',
                            'idRegistro',
                            'idPublicacion',
                            'idUser',
                            'numeroOrden',
                            'fechaCompra',
                            'fechaDespacho'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idEgreso' => 'int',
        'idRegistro' => 'int',
        'idUser' => 'int',
        'idPublicacion' => 'int',
        'fechaCompra' => 'datetime',
        'fechaDespacho' => 'datetime'
        
    ];
    
     public function RegistroProducto()
    {
        return $this->hasOne(RegistroProducto::class,'idRegistro','idRegistro');
    }
    
     public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }
    
     public function Publicacion()
    {
        return $this->belongsTo(Publicacion::class,'idPublicacion','idPublicacion');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    public function Devoluciones()
    {
        return $this->hasMany(Devolucion::class, 'idEgreso', 'idEgreso');
    }
}