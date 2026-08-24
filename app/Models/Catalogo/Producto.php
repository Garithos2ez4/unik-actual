<?php
namespace App\Models\Catalogo;
use App\Models\Ventas\DetalleComprobante;
use App\Models\Inventario\Inventario;
use App\Models\Precios\PrecioTienda;
use App\Models\Precios\HistorialPrecioTienda;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\RegistroProducto;
use App\Models\Inventario\Liquidacion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Producto extends Model
{
    public $timestamps = false;
 
    protected $table = 'Producto';
    
    protected $primaryKey = 'idProducto';

    protected $guarded = ['idProducto'];
    
    protected $fillable = ['idProducto',
                            'idMarca',
                            'idGrupo',
                            'nombreProducto',
                            'codigoProducto',
                            'UPC',
                            'partNumber',
                            'numeroSerie',
                            'modelo',
                            'precioDolar',
                            'gananciaExtra',
                            'garantia',
                            'descripcionProducto',
                            'imagenProducto1',
                            'imagenProducto2',
                            'imagenProducto3',
                            'imagenProducto4',
                            'videoUrl1',   // Test Url 1     
                            'videoUrl2',   // Test Url 2
                            'estadoProductoWeb',
                            'stockMin',
                            'slugProducto',
                            'usar_tc_fijo', // Valor true o false (1 o 0) para usar un determinado tipo de cambio
                            'tc_fijo'
                            ];

    
    protected $hidden = [
        
    ];

    
    protected $casts = [
        'idProducto' => 'int',
        'idMarca' => 'int',
        'idGrupo' => 'int',
        'stockMin' => 'int',
        'precioDolar' => 'float',
        'gananciaExtra' => 'float',
        'usar_tc_fijo' => 'boolean',
        'tc_fijo' => 'float'
    ];

    protected $attributes = [
        'usar_tc_fijo' => true, // Por defecto usa el TC de la API de SUNAT
    ];
    
    public static function boot()
    {
        parent::boot();

        static::creating(function ($producto) {
            $producto->slugProducto = Str::slug($producto->nombreProducto);
        });

        static::updating(function ($producto) {
            $producto->slugProducto = Str::slug($producto->nombreProducto);
        });
    }
    
    public function Publicacion()
    {
        return $this->hasMany(Publicacion::class, 'idProducto', 'idProducto');
    }

    public function Caracteristicas_Producto()
    {
        return $this->hasMany(Caracteristicas_Producto::class, 'idProducto', 'idProducto');
    }
    
    public function DetalleComprobante()
    {
        return $this->hasMany(DetalleComprobante::class, 'idProducto', 'idProducto');
    }

    public function MarcaProducto()
    {
        return $this->belongsTo(MarcaProducto::class,'idMarca','idMarca');
    }
    
    public function GrupoProducto()
    {
        return $this->belongsTo(GrupoProducto::class, 'idGrupo', 'idGrupoProducto');
    }
    
    public function Inventario()
    {
        return $this->hasMany(Inventario::class, 'idProducto', 'idProducto');
    }

    public function PrecioTienda()
    {
        return $this->hasOne(PrecioTienda::class, 'idProducto', 'idProducto');
    }

    public function DetalleProducto()
    {
        return $this->hasOne(DetalleProducto::class, 'idProducto', 'idProducto');
    }

    public function HistorialPrecioTienda()
    {
        return $this->hasMany(HistorialPrecioTienda::class, 'idProducto', 'idProducto')
                     ->orderByDesc('created_at');
    }
    
    public function Inventario_Proveedor()
    {
        return $this->belongsTo(Inventario_Proveedor::class, 'idProducto', 'idProducto');
    }
    
    public function publicImages(){
        
            $default = asset('storage/noimagen.webp');
    
            $imagen1 = $this->imagenProducto1 ? asset('storage/'.$this->imagenProducto1) : $default;
            $imagen2 = $this->imagenProducto2 ? asset('storage/'.$this->imagenProducto2) : $default;
            $imagen3 = $this->imagenProducto3 ? asset('storage/'.$this->imagenProducto3) : $default;
            $imagen4 = $this->imagenProducto4 ? asset('storage/'.$this->imagenProducto4) : $default;
    
            $images = [$imagen1, $imagen2, $imagen3, $imagen4];
            
    
        return $images;
    }
       public function registros() {
        return $this->hasMany(RegistroProducto::class, 'idProducto', 'idProducto');
    }

    /**
     * Componentes hijos de este producto (si es un pack)
     */
    public function packHijos()
    {
        return $this->hasMany(ProductoPack::class, 'idProductoPack', 'idProducto');
    }

    /**
     * Packs donde este producto es componente hijo
     */
    public function packPadres()
    {
        return $this->hasMany(ProductoPack::class, 'idProductoHijo', 'idProducto');
    }

    /**
     * Determina si este producto es un pack divisible
     */
    public function esPack()
    {
        return $this->packHijos()->exists();
    }
    
    
    public function estadoColor(){
        if ($this->estadoProductoWeb == 'DISPONIBLE') {
            return 'text-success';
        } elseif ($this->estadoProductoWeb == 'OFERTA') {
            return 'text-danger';
        } else {
            return 'text-danger text-decoration-line-through';
        }
    }
    
    public function displayImg($img){
        if($img == "asset('storage/images/noimagen.webp')"){
            return "d-none";
        }else{
            return "";
        }
    }

    public function liquidacion()
    {
        return $this->hasOne(Liquidacion::class, 'idProducto', 'idProducto');
    }

    public function hasMapper(string $plataformaNombre): bool
    {
        $idCategoria = optional($this->GrupoProducto)->idCategoria;
        $idGrupo = $this->idGrupo;
        
        return PlataformaMapper::whereHas('plataforma', function($q) use ($plataformaNombre) {
                $q->where('nombrePlataforma', 'like', "%{$plataformaNombre}%");
            })
            ->where(function($query) use ($idCategoria, $idGrupo) {
                if ($idCategoria) {
                    $query->orWhere('idCategoria', $idCategoria);
                }
                if ($idGrupo) {
                    $query->orWhere('idGrupoProducto', $idGrupo);
                }
            })->exists();
    }

    public function hasTemplateCompleto(string $plataformaNombre): bool
    {
        $idCategoria = optional($this->GrupoProducto)->idCategoria;
        $idGrupo = $this->idGrupo;
        
        return PlataformaMapper::whereHas('plataforma', function($q) use ($plataformaNombre) {
                $q->where('nombrePlataforma', 'like', "%{$plataformaNombre}%");
            })
            ->where('tipo_template', 'completo')
            ->where(function($query) use ($idCategoria, $idGrupo) {
                if ($idCategoria) {
                    $query->orWhere('idCategoria', $idCategoria);
                }
                if ($idGrupo) {
                    $query->orWhere('idGrupoProducto', $idGrupo);
                }
            })->exists();
    }
}