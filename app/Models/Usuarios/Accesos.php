<?php

namespace App\Models\Usuarios;

use Illuminate\Database\Eloquent\Model;

class Accesos extends Model
{
    public $timestamps = false;
    protected $table = 'Accesos';
    protected $primaryKey = ['idUser','idVista'];
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

    protected $guarded = ['idUser','idVista'];
    
    protected $fillable = ['idUser',
                            'idVista'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idUser' => 'int',
        'idVista' => 'int',
    ];
    
     public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }
    
     public function Vista()
    {
        return $this->belongsTo(Vista::class,'idVista','idVista');
    }
    
}