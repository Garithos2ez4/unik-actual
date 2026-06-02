<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class CategoriaProducto extends Model
{
 
    protected $table = 'CategoriaProducto';

    public $timestamps = false;

    protected $guarded = ['idCategoria'];
    
    protected $fillable = ['idCategoria',
                            'nombreCategoria',
                            'iconCategoria',
                            'slugCategoria'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idCategoria' => 'int'
    ];
    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            $categoria->slugCategoria = Str::slug($categoria->nombreCategoria);
        });

        static::updating(function ($categoria) {
            $categoria->slugCategoria = Str::slug($categoria->nombreCategoria);
        });
    }

    /**
     * Obtener las relaciones del modelo.
     */
    public function GrupoProducto()
    {
        return $this->hasMany(GrupoProducto::class,'idCategoria','idCategoria');
    }
    
}