<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroProducto extends Model
{
    public $timestamps = false;
 
    protected $table = 'RegistroProducto';
    
    protected $primaryKey = 'idRegistro';

    protected $guarded = ['idRegistro'];
    
    protected $fillable = ['idRegistro',
                            'idDetalleComprobante',
                            'idAlmacen',
                            'numeroSerie',
                            'estado',
                            'fechaMovimiento',
                            'observacion'
                            ];

    protected $hidden = [
        
    ];

    protected $appends = ['ultima_devolucion'];

    public function getUltimaDevolucionAttribute()
    {
        return $this->Devoluciones()->latest('created_at')->first();
    }
    protected $casts = [
        'idRegistro' => 'int',
        'idComprobante' => 'int',
        'idProducto' => 'int',
        'idAlmacen' => 'int',
        'fechaMovimiento' => 'date'
    ];
    
    public function DetalleComprobante()
    {
        return $this->belongsTo(DetalleComprobante::class,'idDetalleComprobante','idDetalleComprobante');
    }
        public function producto()
{
    return $this->belongsTo(Producto::class, 'idProducto', 'idProducto'); 
}
    
    public function IngresoProducto()
    {
        return $this->belongsTo(IngresoProducto::class,'idRegistro','idRegistro');
    }
    
    public function EgresoProducto()
    {
        return $this->belongsTo(EgresoProducto::class,'idRegistro','idRegistro');
    }

    public function Egresos()
    {
        return $this->hasMany(EgresoProducto::class, 'idRegistro', 'idRegistro');
    }

    public function Garantia(){
        return $this->hasMany(Garantia::class,'idRegistro','idRegistro');
     }

    public function Almacen(){
        return $this->belongsTo(Almacen::class,'idAlmacen','idAlmacen');
    }
    public function licenciasUsadas()
    {
        return $this->hasMany(LicenciaUsada::class, 'id_registro_producto');
    }
    /**
     * Obtener las relaciones del modelo.
     */
    public function Devoluciones()
    {
        return $this->hasMany(Devolucion::class, 'idRegistro', 'idRegistro');
    }
}