<?php
namespace App\Models\Catalogo;
use App\Models\Empresa\CuentasPlataforma;
use App\Models\Usuarios\Usuario;
use App\Models\Inventario\EgresoProducto;

use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    public $timestamps = false;
 
    protected $table = 'Publicacion';
    
    protected $primaryKey = 'idPublicacion';

    protected $guarded = ['idPublicacion'];
    
    protected $fillable = ['idPublicacion',
                            'idCuentaPlataforma',
                            'idUser',
                            'idProducto',
                            'sku',
                            'titulo',
                            'estado',
                            'fechaPublicacion',
                            'precioPublicacion'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idPublicacion' => 'int',
        'idCuentaPlataforma' => 'int',
        'idUser' => 'int',
        'idProducto' => 'int',
        'estado' => 'int',
        'fechaPublicacion' => 'date',
        'precioPublicacion' => 'float'
    ];
    
    public function CuentasPlataforma()
    {
        return $this->belongsTo(CuentasPlataforma::class,'idCuentaPlataforma','idCuentaPlataforma');
    }
    
    public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }
    
    public function Producto()
    {
        return $this->belongsTo(Producto::class,'idProducto','idProducto');
    }

    public function EgresoProducto()
    {
        return $this->hasMany(EgresoProducto::class,'idPublicacion','idPublicacion');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}