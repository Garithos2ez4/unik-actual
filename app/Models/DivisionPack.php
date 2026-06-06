<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivisionPack extends Model
{
    protected $table = 'DivisionPack';

    protected $primaryKey = 'idDivision';

    protected $guarded = ['idDivision'];

    protected $fillable = [
        'idDivision',
        'idRegistroPack',
        'idUser',
        'tipo',
        'fechaDivision',
        'observacion'
    ];

    protected $casts = [
        'idDivision' => 'int',
        'idRegistroPack' => 'int',
        'idUser' => 'int',
        'fechaDivision' => 'date'
    ];

    public function RegistroProducto()
    {
        return $this->belongsTo(RegistroProducto::class, 'idRegistroPack', 'idRegistro');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }

    public function Detalles()
    {
        return $this->hasMany(DivisionPackDetalle::class, 'idDivision', 'idDivision');
    }
}
