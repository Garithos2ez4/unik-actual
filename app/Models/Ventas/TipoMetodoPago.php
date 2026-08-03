<?php

namespace App\Models\Ventas;

use Illuminate\Database\Eloquent\Model;

class TipoMetodoPago extends Model
{
    public $timestamps = false;
    
    protected $table = 'TipoMetodoPago';
    
    protected $primaryKey = 'idTipoMetodo';

    protected $guarded = ['idTipoMetodo'];
    
    protected $fillable = [
        'nombreTipo'
    ];

    protected $casts = [
        'idTipoMetodo' => 'int'
    ];

    public function MetodosPago()
    {
        return $this->hasMany(MetodoPago::class, 'idTipoMetodo', 'idTipoMetodo');
    }
}
