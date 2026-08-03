<?php
namespace App\Models\Empresa;
use App\Models\Catalogo\Publicacion;

use Illuminate\Database\Eloquent\Model;

class CuentasPlataforma extends Model
{
    public $timestamps = false;
 
    protected $table = 'CuentasPlataforma';
    
    protected $primaryKey = 'idCuentaPlataforma';

    protected $guarded = ['idCuentaPlataforma'];
    
    protected $fillable = ['idCuentaPlataforma',
                            'idPlataforma',
                            'nombreCuenta',
                            'estadoCuenta'
                            ];

    
    protected $hidden = [
    ];

    
    protected $casts = [
        'idPlataforma' => 'int',
        'idCuentaPlataforma' => 'int'
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function Plataforma()
    {
        return $this->belongsTo(Plataforma::class,'idPlataforma','idPlataforma');
    }
    
    public function Publicacion()
    {
        return $this->hasMany(Publicacion::class,'idCuentaPlataforma','idCuentaPlataforma');
    }
    
}