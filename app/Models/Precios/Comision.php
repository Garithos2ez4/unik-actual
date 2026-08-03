<?php
namespace App\Models\Precios;
use App\Models\Catalogo\GrupoProducto;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
 
    public $timestamps = false;
    protected $table = 'Comision';
    protected $primaryKey = ['idGrupoProducto', 'idRango'];
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

    protected $guarded = ['idGrupoProducto','idRango'];
    
    protected $fillable = ['idGrupoProducto','idRango','comision',
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idGrupoProducto' => 'int',
        'idRango' => 'int',
        'comision' => 'decimal:2'
    ];
    
     public function GrupoProducto()
    {
        return $this->belongsTo(GrupoProducto::class,'idGrupoProducto','idGrupoProducto');
    }
    
     public function RangoPrecio()
    {
        return $this->belongsTo(RangoPrecio::class,'idRango','idRango');
    }
    

    /**
     * Obtener las relaciones del modelo.
     */
    
}