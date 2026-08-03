<?php
// app/Models/CategoriaLicencia.php

namespace App\Models\Licencias;

use Illuminate\Database\Eloquent\Model;

class CategoriaLicencia extends Model
{
    protected $table = 'categoria_licencia';
    protected $primaryKey = 'id_categoria';
    public $incrementing = true;

    protected $guarded = ['id_categoria'];

    protected $fillable = ['tipo_categoria'];

    protected $casts = [
        'id_categoria' => 'int'
    ];
    public function Licencias() {
        return $this->hasMany(Licencia::class,'id_categoria', 'id_categoria');
    }
}
