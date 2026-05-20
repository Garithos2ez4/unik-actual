<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agencia extends Model
{
    protected $table = 'agencias';
    protected $primaryKey = 'idAgencia';

    protected $fillable = [
        'nombre',
        'estado'
    ];

    public function SubAgencias()
    {
        return $this->hasMany(SubAgencia::class, 'idAgencia', 'idAgencia');
    }
}
