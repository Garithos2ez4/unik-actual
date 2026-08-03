<?php
namespace App\Models\Catalogo;

use Illuminate\Database\Eloquent\Model;

class Caracteristicas_Grupo extends Model
{
    public $timestamps = false;
    protected $table = 'Caracteristicas_grupo';
    protected $primaryKey = ['idGrupoProducto','idCaracteristica'];
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

    protected $guarded = ['idGrupoProducto','idCaracteristica'];
    
    protected $fillable = ['idGrupoProducto',
                            'idCaracteristica'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idGrupoProducto' => 'int',
        'idCaracteristica' => 'int',
    ];
    
    public function GrupoProducto()
    {
        return $this->belongsTo(GrupoProducto::class,'idGrupoProducto','idGrupoProducto');
    }
    
    public function Caracteristicas()
    {
        return $this->belongsTo(Caracteristicas::class,'idCaracteristica','idCaracteristica');
    }
    

    /**
     * Obtener las relaciones del modelo.
     */
    
}