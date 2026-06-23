<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    public $timestamps = false;
    protected $table = 'Inventario';
    protected $primaryKey = ['idProducto','idAlmacen'];
    public $incrementing = false;
    protected $keyType = 'int';
    
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected $guarded = ['idProducto','idAlmacen'];
    
    protected $fillable = ['idProducto',
                            'idAlmacen',
                            'stock',
                            'ubicacion_fisica'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idProducto' => 'int',
        'idAlmacen' => 'int',
    ];
    
    public function Almacen()
    {
        return $this->belongsTo(Almacen::class,'idAlmacen','idAlmacen');
    }
    
    public function Producto()
    {
        return $this->belongsTo(Producto::class,'idProducto','idProducto');
    }
    

    public function UbicacionAlmacen()
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_fisica', 'idUbicacion');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}