<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    public $timestamps = false;
    
    protected $table = 'MetodoPago';
    
    protected $primaryKey = 'idMetodoPago';

    protected $guarded = ['idMetodoPago'];
    
    protected $fillable = [
        'idTipoMetodo',
        'idBanco',
        'nombreMetodo',
        'estado'
    ];

    protected $casts = [
        'idMetodoPago' => 'int',
        'idTipoMetodo' => 'int',
        'idBanco'      => 'int',
        'estado'       => 'int'
    ];

    public function TipoMetodoPago()
    {
        return $this->belongsTo(TipoMetodoPago::class, 'idTipoMetodo', 'idTipoMetodo');
    }

    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'idBanco', 'idBanco');
    }
}
