<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComprobante extends Model
{
    public $timestamps = false;
    public $incrementing = false;
 
    protected $table = 'DetalleComprobante';
    
    protected $primaryKey = 'idDetalleComprobante';

    protected $guarded = ['idDetalleComprobante'];
    
    protected $fillable = ['idDetalleComprobante',
                            'idComprobante',
                            'idProducto',
                            'cantidad',
                            'medida',
                            'precioUnitario',
                            'precioCompra'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idDetalleComprobante' => 'int',
        'idComprobante' => 'int',
        'idProducto' => 'int',
        'cantidad' => 'int',
        'precioUnitario' => 'float',
        'precioCompra' => 'float'
    ];
    
    public function RegistroProducto()
    {
        return $this->hasMany(RegistroProducto::class,'idDetalleComprobante','idDetalleComprobante');
    }
    
    public function Producto()
    {
        return $this->belongsTo(Producto::class,'idProducto','idProducto');
    }
    
    public function Comprobante()
    {
        return $this->belongsTo(Comprobante::class,'idComprobante','idComprobante');
    }
    /**
     * Obtener las relaciones del modelo.
     */
    
}