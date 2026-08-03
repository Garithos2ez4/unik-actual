<?php
namespace App\Models\Inventario;
use App\Models\Catalogo\Producto;

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
                            'idUbicacionExacta'
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
    

    public function UbicacionExacta()
    {
        return $this->belongsTo(UbicacionEstante::class, 'idUbicacionExacta', 'idUbicacionExacta');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}