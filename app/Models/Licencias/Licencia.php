<?php
namespace App\Models\Licencias;
use App\Models\Empresa\Preveedor;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model {

    protected $table = 'licencias';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'id' => 'int',
        'id_tipo' => 'int',
        'id_categoria' => 'int'
    ];
    public function tipoLicencia()
    {
        return $this->belongsTo(TipoLicencia::class, 'id_tipo');
    }

    public function licenciasUsadas()
    {
        return $this->hasMany(LicenciaUsada::class, 'id_licencia');
    }

    public function licenciasDefectuosas()
    {
        return $this->hasMany(LicenciaDefectuosa::class, 'id_licencia');
    }

    public function licenciasRecuperadas()
    {
        return $this->hasMany(LicenciaRecuperada::class, 'id_licencia');
    }
    public function getEstadoTextoAttribute()
    {
        return match($this->estado) {
            'NUEVA' => 'Licencia Nueva',
            'USADA' => 'Licencia Usada',
            'DEFECTUOSA' => 'Licencia Defectuosa',
            'RECUPERADA' => 'Licencia Recuperada',
            default => 'Desconocido',
        };
    }
    public function proveedor()
    {
        return $this->belongsTo(Preveedor::class, 'idProveedor', 'idProveedor');
    }
    public function categoriaLicencia()
    {
        return $this->belongsTo(CategoriaLicencia::class, 'id_categoria', 'id_categoria');
    }
    public function esMultifuncional(){
        return optional($this->categoriaLicencia)->tipo_categoria === 'Multiusuario';
    }
    public function tieneUsosDisponibles(): bool
    {   
        return $this->cantidad_usos > 0;
    }

    public function descontarUso(int $cantidad = 1): void
    {
        if ($this->cantidad_usos < $cantidad) {
            throw new \Exception('No hay suficientes usos disponibles');
        }

        $this->cantidad_usos -= $cantidad;
        $this->save();
    }

}
