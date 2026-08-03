<?php
namespace App\Models\Precios;

use Illuminate\Database\Eloquent\Model;

class RangoPrecio extends Model
{
 
    protected $table = 'RangoPrecio';
    
    protected $primaryKey = 'idRango';

    protected $guarded = ['idRango'];
    
    protected $fillable = ['rangoMin',
                            'rangoMax',
                            'descripcion',
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idRango' => 'int',
        'rangoMin' => 'decimal:2',
        'rangoMax' => 'decimal:2'
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function Comision()
    {
        return $this->hasMany(Comision::class, 'idRango', 'idRango');
    }
}