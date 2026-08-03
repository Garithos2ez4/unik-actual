<?php
namespace App\Models\Precios;
use App\Models\Empresa\Plataforma;

use Illuminate\Database\Eloquent\Model;

class ComisionPlataforma extends Model
{
    public $timestamps = false;
 
    protected $table = 'ComisionPlataforma';
    
    protected $primaryKey = 'idComisionPlataforma';

    protected $guarded = ['idComisionPlataforma'];
    
    protected $fillable = ['idComisionPlataforma',
                            'idPlataforma',
                            'flete',
                            'comision'
                            ];

    
    protected $hidden = [
    ];

    
    protected $casts = [
        'idComisionPlataforma' => 'int'
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function Plataforma()
    {
        return $this->belongsTo(Plataforma::class,'idPlataforma','idPlataforma');
    }
    
}