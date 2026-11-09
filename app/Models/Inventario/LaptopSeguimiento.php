<?php

namespace App\Models\Inventario;

use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\Producto;
use App\Models\Usuarios\Usuario;
use Illuminate\Database\Eloquent\Model;

class LaptopSeguimiento extends Model
{
    protected $table = 'laptop_seguimientos';

    protected $primaryKey = 'idLaptopSeguimiento';

    protected $fillable = [
        'idProducto',
        'idMarca',
        'marca_nombre',
        'plataforma_cpu',
        'gama',
        'idUser',
        'responsable_texto',
        'modelo',
        'serie',
        'tamano',
        'frecuencia',
        'procesador',
        'ram',
        'disco',
        'video',
        'part_number',
        'stock_texto',
        'stock_unik',
        'fecha_revision',
        'estado_fila',
        'proveedores_data',
        'cuentas_ml_data',
        'oculto',
        'orden',
        'observacion',
    ];

    protected $casts = [
        'idProducto'       => 'int',
        'idMarca'          => 'int',
        'idUser'           => 'int',
        'fecha_revision'   => 'date',
        'proveedores_data' => 'array',
        'cuentas_ml_data'  => 'array',
        'oculto'           => 'boolean',
        'orden'            => 'int',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto', 'idProducto');
    }

    public function marca()
    {
        return $this->belongsTo(MarcaProducto::class, 'idMarca', 'idMarca');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUser', 'idUser');
    }

    /**
     * Indica si la fecha de última revisión está vencida (< hoy).
     */
    public function estaVencido(): bool
    {
        if (!$this->fecha_revision) {
            return false;
        }

        return $this->fecha_revision->lt(now()->startOfDay());
    }

    /**
     * Retorna el nombre visible del responsable (Usuario vinculado o texto libre importado).
     */
    public function getNombreResponsableAttribute(): string
    {
        if ($this->relationLoaded('usuario') && $this->usuario) {
            return (string) $this->usuario->user;
        }

        return (string) ($this->responsable_texto ?? '');
    }

    /**
     * Estado efectivo de la fila (calculado o manual).
     */
    public function getEstadoEfectivoAttribute(): string
    {
        if (in_array($this->estado_fila, ['CON_STOCK', 'AGOTADO', 'EN_TRANSITO', 'DESHABILITADA', 'PAUSADA'], true)) {
            return $this->estado_fila;
        }

        $provs = $this->proveedores_data ?? [];
        $tieneStock = false;
        $tieneProv = false;

        foreach ($provs as $p) {
            $tipo = strtoupper($p['tipo'] ?? '');
            $val = strtoupper(trim((string) ($p['valor'] ?? '')));
            if ($tipo === 'STOCK' || $tipo === 'CODIGO' || $val === 'X' || preg_match('/^W\d+/i', $val)) {
                $tieneStock = true;
            } elseif ($tipo === 'PROV' || str_starts_with($val, 'PROV')) {
                $tieneProv = true;
            }
        }

        if ($tieneStock) {
            return 'CON_STOCK';
        }
        if ($tieneProv) {
            return 'AGOTADO';
        }

        return 'NORMAL';
    }
}

