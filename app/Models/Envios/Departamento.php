<?php
namespace App\Models\Envios;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';
    protected $primaryKey = 'idDepartamento';
    protected $fillable = ['nombre'];

    public function Provincias()
    {
        return $this->hasMany(Provincia::class, 'idDepartamento', 'idDepartamento');
    }
}
