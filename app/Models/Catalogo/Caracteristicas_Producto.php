<?php
namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class Caracteristicas_Producto extends Model
{
    public $timestamps = false;
    protected $table = 'Caracteristicas_Producto';
    protected $primaryKey = ['idCaracteristica','idProducto'];
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

    protected $guarded = ['idCaracteristica','idProducto'];
    
    protected $fillable = ['idCaracteristica',
                            'idProducto',
                            'caracteristicaProducto'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idGrupoProducto' => 'int',
        'idProducto' => 'int',
    ];
    
    public function Producto()
    {
        return $this->belongsTo(Producto::class,'idProducto','idProducto');
    }
    
    public function Caracteristicas()
    {
        return $this->belongsTo(Caracteristicas::class,'idCaracteristica','idCaracteristica');
    }
    

    /**
     * Obtener las relaciones del modelo.
     */
    
}