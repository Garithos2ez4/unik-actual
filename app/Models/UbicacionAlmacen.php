<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionAlmacen extends Model
{
    protected $table = 'UbicacionAlmacen';
    protected $primaryKey = 'idUbicacion';
    
    // Le indicamos a Laravel que inyecte este atributo virtual
    protected $appends = ['foto_url'];
    
    protected $fillable = [
        'idAlmacen',
        'nombre',
        'descripcion',
        'foto'
    ];

    public function Almacen()
    {
        return $this->belongsTo(Almacen::class, 'idAlmacen', 'idAlmacen');
    }

    /**
     * Devuelve la URL pública de la foto del estante.
     * Si no tiene foto, reutiliza tu imagen por defecto del sistema.
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return asset('storage/' . $this->foto);
        }
        
        // Reutilizamos la misma imagen por defecto que usas en tus productos
        return asset('storage/productos/no-image.jpg'); 
    }
}
