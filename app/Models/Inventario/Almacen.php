<?php
namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    public $timestamps = false;
 
    protected $table = 'Almacen';
    
    protected $primaryKey = 'idAlmacen';

    protected $guarded = ['idAlmacen'];
    
    protected $fillable = ['idAlmacen','descripcion'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idAlmacen' => 'int'
    ];
    
    public function Inventario()
    {
        return $this->hasMany(Inventario::class,'idAlmacen','idAlmacen');
    }

    public function RegistroProducto(){
        return $this->hasMany(RegistroProducto::class,'idAlmacen','idAlmacen');
    }

    public function Ubicaciones()
    {
        return $this->hasMany(UbicacionAlmacen::class, 'idAlmacen', 'idAlmacen');
    }

    public function Filas()
    {
        return $this->hasMany(UbicacionEstante::class, 'idAlmacen', 'idAlmacen');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}