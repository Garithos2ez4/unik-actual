<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $table = 'provincias';
    protected $primaryKey = 'idProvincia';
    protected $fillable = ['idDepartamento', 'nombre'];

    public function Departamento()
    {
        return $this->belongsTo(Departamento::class, 'idDepartamento', 'idDepartamento');
    }

    public function Destinos()
    {
        return $this->hasMany(Destino::class, 'idProvincia', 'idProvincia');
    }
}
