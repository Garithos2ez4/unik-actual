<?php
namespace App\Models\Ventas;
use App\Models\Empresa\Preveedor;
use App\Models\Usuarios\Usuario;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    public $timestamps = false;
 
    protected $table = 'Comprobante';
    
    protected $primaryKey = 'idComprobante';

    protected $guarded = ['idComprobante'];
    
    protected $fillable = ['idComprobante',
                            'idProveedor',
                            'idTipoComprobante',
                            'idUser',
                            'numeroComprobante',
                            'moneda',
                            'adquisicion',
                            'totalCompra',
                            'fechaRegistro',
                            'estado'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idComprobante' => 'int',
        'idProveedor' => 'int',
        'idTipoComprobante' => 'int',
        'idUser' => 'int',
        'totalCompra' => 'float',
        'fechaRegistro' => 'date'
    ];
    
    public function Preveedor()
    {
        return $this->belongsTo(Preveedor::class,'idProveedor','idProveedor');
    }
    public function TipoComprobante()
    {
        return $this->belongsTo(TipoComprobante::class,'idTipoComprobante','idTipoComprobante');
    }
    public function Usuario()
    {
        return $this->belongsTo(Usuario::class,'idUser','idUser');
    }
    
    public function DetalleComprobante(){
        return $this->hasMany(DetalleComprobante::class,'idComprobante','idComprobante');
    }

    /**
     * Obtener las relaciones del modelo.
     */
    
}