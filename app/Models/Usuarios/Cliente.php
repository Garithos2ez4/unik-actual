<?php
namespace App\Models\Usuarios;
use App\Models\Ventas\Garantia;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
   public $timestamps = false;

    protected $table = 'Cliente';

    protected $guarded = ['idCliente'];
    
    protected $primaryKey = 'idCliente';
    
    public $incrementing = false;
    
    protected $fillable = ['idCliente',
                            'nombre',
                            'apellidoPaterno',
                            'apellidoMaterno',
                            'numeroDocumento',
                            'idTipoDocumento',
                            'telefono',
                            'correo'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idCliente' => 'int',
        'idTipoDocumento' => 'int'
    ];
    /**
     * Obtener las relaciones del modelo.
     */

     public function TipoDocumento()
     {
        return $this->belongsTo(TipoDocumento::class,'idTipoDocumento','idTipoDocumento');
     }

     public function Garantia()
     {
        return $this->hasMany(Garantia::class,'idCliente','idCliente');
     }
    
}