<?php
namespace App\Models\Empresa;
use App\Models\Precios\ComisionPlataforma;

use Illuminate\Database\Eloquent\Model;

class Plataforma extends Model
{
    public $timestamps = false;
 
    protected $table = 'Plataforma';
    
    protected $primaryKey = 'idPlataforma';

    protected $guarded = ['idPlataforma'];
    
    protected $fillable = ['idPlataforma',
                            'nombrePlataforma',
                            'tipoPlataforma',
                            'imagenPlataforma'
                            ];

    
    protected $hidden = [
    ];

    
    protected $casts = [
        'idPlataforma' => 'int'
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function CuentasPlataforma()
    {
        return $this->hasMany(CuentasPlataforma::class,'idPlataforma','idPlataforma');
    }
    
    public function ComisionPlataforma()
    {
        return $this->hasMany(ComisionPlataforma::class,'idPlataforma','idPlataforma');
    }
    
}