<?php
namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarcaProducto extends Model
{
    public $timestamps = false;
    
    protected $table = 'MarcaProducto';

    protected $primaryKey = 'idMarca';

    protected $guarded = ['idMarca'];
    
    protected $fillable = ['idMarca',
                            'nombreMarca',
                            'imagenMarca',
                            'slugMarca'
                            ];

    
    protected $hidden = [
    ];

    
    protected $casts = [
        'idMarca' => 'int'
    ];
    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($marca) {
            $marca->slugMarca = Str::slug($marca->nombreMarca);
        });

        static::updating(function ($marca) {
            $marca->slugMarca = Str::slug($marca->nombreMarca);
        });
    }

    /**
     * Obtener las relaciones del modelo.
     */
    public function Producto()
    {
        return $this->hasMany(Producto::class,'idMarca','idMarca');
    }
    
    
}