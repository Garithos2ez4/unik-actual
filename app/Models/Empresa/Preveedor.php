<?php
namespace App\Models\Empresa;
use App\Models\Catalogo\Producto;
use App\Models\Ventas\Comprobante;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Licencias\Licencia;
use App\Models\Licencias\LicenciaDefectuosa;

use Illuminate\Database\Eloquent\Model;

class Preveedor extends Model
{
    public $timestamps = false;
    protected $table = 'Preveedor';

    protected $guarded = ['idProveedor'];
    
    protected $fillable = ['idProveedor',
                            'nombreProveedor',
                            'razSocialProveedor',
                            'rucProveedor'
                            ];

    
    protected $hidden = [
    ];

    
    protected $casts = [
        'idProveedor' => 'int'
    ];

    /**
     * Obtener las relaciones del modelo.
     */
    public function Producto()
    {
        return $this->hasMany(Producto::class,'idProveedor','idProveedor');
    }
    public function Comprobante()
    {
        return $this->hasMany(Comprobante::class,'idProveedor','idProveedor');
    }
    public function Inventario_Proveedor()
    {
        return $this->hasMany(Inventario_Proveedor::class,'idProveedor','idProveedor');
    }
    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'idProveedor', 'idProveedor');
    }
    public function licenciasDefectuosas()
    {
        return $this->hasMany(LicenciaDefectuosa::class, 'idProveedor', 'idProveedor');
    }
    public function licenciasRecuperadas()
    {
        return $this->hasMany(licenciaRecuperada::class,'IdProveedor', 'IdProveedor');
    }
}