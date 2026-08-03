<?php

namespace App\Models\Usuarios;

use Illuminate\Database\Eloquent\Model;

class Vista extends Model
{
    public $timestamps = false;
 
    protected $table = 'Vista';
    
    protected $primaryKey = 'idVista';

    protected $guarded = [];
    
    protected $fillable = ['idVista','descripcion'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idVista' => 'int'
    ];
    
    public function Accesos()
    {
        return $this->hasMany(Accesos::class,'idVista','idVista');
    }
    
}
