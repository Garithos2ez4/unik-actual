<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoLicencia extends Model
{
    protected $table = 'tipo_licencia';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'estado'
    ];
    /**
     * Relacion con la tabla de licencias. 
     */
    public function licencias()
    {
        return $this->hasMany(Licencia::class, 'id_tipo');
    }
}
