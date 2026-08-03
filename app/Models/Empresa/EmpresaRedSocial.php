<?php
namespace App\Models\Empresa;

use Illuminate\Database\Eloquent\Model;

class EmpresaRedSocial extends Model
{
 
    public $timestamps = false;
    protected $table = 'EmpresaRedSocial';
    protected $primaryKey = ['idRedSocial', 'idEmpresa'];
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

    protected $guarded = ['idRedSocial','idEmpresa'];
    
    protected $fillable = ['titular',
                            'enlace'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idRedSocial' => 'int',
        'idEmpresa' => 'int'
    ];
    
     public function Empresa()
    {
        return $this->belongsTo(Empresa::class,'idEmpresa','idEmpresa');
    }
    
     public function RedSocial()
    {
        return $this->belongsTo(RedSocial::class,'idRedSocial','idRedSocial');
    }
    

    /**
     * Obtener las relaciones del modelo.
     */
    
}