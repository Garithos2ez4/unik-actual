<?php

namespace App\Models\Ventas;
use App\Models\Usuarios\Cliente;
use App\Models\Inventario\RegistroProducto;

use Illuminate\Database\Eloquent\Model;

class Garantia extends Model
{
   public $timestamps = false;

   protected $table = 'Garantia';

   protected $guarded = ['idGarantia'];

   protected $primaryKey = 'idGarantia';

   protected $fillable = [
      'idGarantia',
      'idRegistro',
      'idCliente',
      'fechaGarantia',
      'numeroComprobante',
      'recepcion',
      'estado',
      'falla'
   ];


   protected $hidden = [];


   protected $casts = [
      'idGarantia' => 'int',
      'idRegistro' => 'int',
      'idCliente' => 'int',
      'fechaGarantia' => 'date'
   ];
   /**
    * Obtener las relaciones del modelo.
    */

   public function Cliente()
   {
      return $this->belongsTo(Cliente::class, 'idCliente', 'idCliente');
   }

   public function RegistroProducto()
   {
      return $this->belongsTo(RegistroProducto::class, 'idRegistro', 'idRegistro');
   }
}
