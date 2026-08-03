<?php
namespace App\Models\Empresa;

use Illuminate\Database\Eloquent\Model;

class Publicidad extends Model
{
    public $timestamps = false;
    protected $table = 'Publicidad';
    protected $primaryKey = 'idPublicidad';

    protected $guarded = ['idPublicidad'];
    
    protected $fillable = ['descripcionPublicidad',
                            'imagenPublicidad',
                            'tipoPublicidad',
                            'estadoPublicidad'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idPublicidad' => 'int'
    ];
    
     public function Empresa()
    {
        return $this->belongsTo(Empresa::class,'idEmpresa','idEmpresa');
    }

}