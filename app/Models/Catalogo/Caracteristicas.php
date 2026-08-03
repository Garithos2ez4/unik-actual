<?php
namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class Caracteristicas extends Model
{
    public $timestamps = false;
    protected $table = 'Caracteristicas';
    
    protected $primaryKey = 'idCaracteristica';

    protected $guarded = ['idCaracteristica'];
    
    protected $fillable = ['idCaracteristica',
                            'especificacion',
                            'tipo'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idCaracteristica' => 'int'
    ];

    public function Caracteristicas_Grupo(){
        return $this->hasMany(Caracteristicas_Grupo::class,'idCaracteristica','idCaracteristica');
    }

    public function Caracteristicas_Producto(){
        return $this->hasMany(Caracteristicas_Producto::class,'idCaracteristica','idCaracteristica');
    }

    public function Caracteristicas_Sugerencias(){
        return $this->hasMany(Caracteristicas_Sugerencias::class,'idCaracteristica','idCaracteristica');
    }

    /**
     * Obtener las relaciones del modelo.
     */
}