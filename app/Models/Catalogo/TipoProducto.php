<?php
namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TipoProducto extends Model
{
 
    protected $table = 'TipoProducto';

    protected $guarded = ['idTipoProducto'];
    
    protected $primaryKey = 'idTipoProducto';
    
    protected $fillable = ['idTipoProducto',
                            'tipoProducto',
                            'slugTipo'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idTipoProducto' => 'int'
    ];
    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($tipo) {
            $tipo->slugTipo = Str::slug($tipo->tipoProducto);
        });

        static::updating(function ($tipo) {
            $tipo->slugTipo = Str::slug($tipo->tipoProducto);
        });
    }

    /**
     * Obtener las relaciones del modelo.
     */
    public function GrupoProducto()
    {
        return $this->hasMany(GrupoProducto::class,'idTipoProducto','idTipoProducto');
    }
    
}