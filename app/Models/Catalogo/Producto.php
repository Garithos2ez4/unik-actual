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
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Producto extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $table = 'Producto';

    protected $primaryKey = 'idProducto';

    public $incrementing = false;

    protected $guarded = [];

    protected $fillable = [
        'idProducto',
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
        'tc_fijo',
        // Sistema de variantes (Padre-Hijo). Atributos descriptivos en DetalleProducto.
        'idProductoPadre',
        'es_padre'
    ];


    protected $hidden = [];


    protected $casts = [
        'idProducto' => 'int',
        'idMarca' => 'int',
        'idGrupo' => 'int',
        'stockMin' => 'int',
        'precioDolar' => 'float',
        'gananciaExtra' => 'float',
        'usar_tc_fijo' => 'boolean',
        'tc_fijo' => 'float',
        'idProductoPadre' => 'int',
        'es_padre' => 'boolean'
    ];

    protected $attributes = [
        'usar_tc_fijo' => true, // Por defecto usa el TC de la API de SUNAT
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn ($model) => $model->nombreProducto ?: 'producto')
            ->saveSlugsTo('slugProducto');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($producto) {
            if (empty($producto->idProducto)) {
                $producto->idProducto = ((int) static::max('idProducto')) + 1;
            }
            if (!isset($producto->UPC) || $producto->UPC === null || $producto->UPC === '') {
                $producto->UPC = '0';
            }
        });

        static::saved(function ($producto) {
            $producto->invalidarCacheWeb();
        });

        static::deleted(function ($producto) {
            $producto->invalidarCacheWeb();
        });
    }

    public function invalidarCacheWeb()
    {
        try {
            $slug = $this->slugProducto;
            $originalSlug = $this->getOriginal('slugProducto');
            $id = $this->idProducto;
            $codigo = $this->codigoProducto;
            $originalCodigo = $this->getOriginal('codigoProducto');

            $keysToForget = [];
            if (!empty($slug)) {
                $keysToForget[] = "producto_getOne_slugProducto_{$slug}";
            }
            if (!empty($originalSlug) && $originalSlug !== $slug) {
                $keysToForget[] = "producto_getOne_slugProducto_{$originalSlug}";
            }
            if (!empty($id)) {
                $keysToForget[] = "producto_getOne_idProducto_{$id}";
            }
            if (!empty($codigo)) {
                $keysToForget[] = "producto_getOne_codigoProducto_{$codigo}";
            }
            if (!empty($originalCodigo) && $originalCodigo !== $codigo) {
                $keysToForget[] = "producto_getOne_codigoProducto_{$originalCodigo}";
            }

            foreach ($keysToForget as $k) {
                Cache::forget($k);
            }

            // Invalidation direct in cache table for wildcards and partial matches
            if (!empty($slug)) {
                \Illuminate\Support\Facades\DB::table('cache')
                    ->where('key', 'LIKE', "%{$slug}%")
                    ->delete();
            }
            if (!empty($originalSlug) && $originalSlug !== $slug) {
                \Illuminate\Support\Facades\DB::table('cache')
                    ->where('key', 'LIKE', "%{$originalSlug}%")
                    ->delete();
            }

            // Invalidate category cache in WebUnik (current and previous group if changed)
            $gruposAInvalidar = array_unique(array_filter([$this->idGrupo, $this->getOriginal('idGrupo')]));
            foreach ($gruposAInvalidar as $gId) {
                $idCategoria = \App\Models\Catalogo\GrupoProducto::where('idGrupoProducto', $gId)->value('idCategoria');
                if ($idCategoria) {
                    Cache::forget("productos_categoria_{$idCategoria}");
                    \Illuminate\Support\Facades\DB::table('cache')
                        ->where('key', 'LIKE', "productos_categoria_{$idCategoria}%")
                        ->delete();
                }
            }

            // If it is a child variant, clear parent cache too
            if (!empty($this->idProductoPadre)) {
                $padreSlug = static::where('idProducto', $this->idProductoPadre)->value('slugProducto');
                if ($padreSlug) {
                    Cache::forget("producto_getOne_slugProducto_{$padreSlug}");
                    \Illuminate\Support\Facades\DB::table('cache')
                        ->where('key', 'LIKE', "%{$padreSlug}%")
                        ->delete();
                }
            }

            // If it is a parent, clear variants caches too
            if (!empty($this->es_padre)) {
                $hijosSlugs = static::where('idProductoPadre', $this->idProducto)->pluck('slugProducto');
                foreach ($hijosSlugs as $hSlug) {
                    if ($hSlug) {
                        Cache::forget("producto_getOne_slugProducto_{$hSlug}");
                        \Illuminate\Support\Facades\DB::table('cache')
                            ->where('key', 'LIKE', "%{$hSlug}%")
                            ->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Error invalidando cache web: " . $e->getMessage());
        }
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
        return $this->belongsTo(MarcaProducto::class, 'idMarca', 'idMarca');
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

    public function publicImages()
    {
        $default = asset('storage/noimagen.webp');
        $baseDir = dirname(base_path());
        $prodImagesPath = $baseDir . '/public_html/images/';

        $formatImage = function ($img) use ($default, $prodImagesPath) {
            if (!$img || $img === 'noimagen.webp') return $default;

            $parts = explode('?', $img, 2);
            $cleanPath = ltrim($parts[0], '/');
            $existingQuery = $parts[1] ?? null;

            $localPath1 = public_path('storage/' . $cleanPath);
            $localPath2 = storage_path('app/public/' . $cleanPath);
            $prodPath   = $prodImagesPath . $cleanPath;

            $version = null;
            $exists = false;

            if (file_exists($localPath1)) {
                $version = filemtime($localPath1);
                $exists = true;
            } elseif (file_exists($localPath2)) {
                $version = filemtime($localPath2);
                $exists = true;
            } elseif (!app()->environment('local') && file_exists($prodPath)) {
                $version = filemtime($prodPath);
                $exists = true;
            }

            if (!$exists) {
                if ($existingQuery && !app()->environment('local')) {
                    return asset('storage/' . $cleanPath) . '?' . $existingQuery;
                }
                return $default;
            }

            if (!$version && $existingQuery) {
                return asset('storage/' . $cleanPath) . '?' . $existingQuery;
            }

            if (!$version) {
                if (!empty($this->updated_at)) {
                    $version = strtotime((string) $this->updated_at) ?: null;
                }
                if (!$version) {
                    $version = time();
                }
            }

            return asset('storage/' . $cleanPath) . '?v=' . $version;
        };

        return [
            $formatImage($this->imagenProducto1),
            $formatImage($this->imagenProducto2),
            $formatImage($this->imagenProducto3),
            $formatImage($this->imagenProducto4),
        ];
    }
    public function registros()
    {
        return $this->hasManyThrough(
            RegistroProducto::class,
            DetalleComprobante::class,
            'idProducto',
            'idDetalleComprobante',
            'idProducto',
            'idDetalleComprobante'
        );
    }

    /**
     * Scope para filtrar productos fantasma (reemplazables):
     * Aquellos que nunca tuvieron ningún ingreso en IngresoProducto (y no son contenedores padre).
     */
    public function scopeReemplazables($query)
    {
        return $query->where('Producto.es_padre', 0)
            ->whereNotExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('DetalleComprobante as dc')
                    ->join('RegistroProducto as rp', 'rp.idDetalleComprobante', '=', 'dc.idDetalleComprobante')
                    ->join('IngresoProducto as ip', 'ip.idRegistro', '=', 'rp.idRegistro')
                    ->whereColumn('dc.idProducto', 'Producto.idProducto');
            });
    }

    /**
     * Scope para reciclaje automático por lotes desde Deltron:
     * Toma productos sin ingresos (reemplazables) que además estén AGOTADOS/DESCONTINUADOS,
     * tengan stock proveedor en 0, o sean productos antiguos sin SKU Deltron vinculado.
     * Nunca pisa productos activos recién importados de Deltron con stock.
     */
    public function scopeFantasmasAutoReciclables($query)
    {
        return $query->reemplazables()
            ->whereNull('Producto.idProductoPadre')
            ->where(function ($q) {
                $q->whereIn('Producto.estadoProductoWeb', ['AGOTADO', 'DESCONTINUADO'])
                    ->orWhereDoesntHave('Inventario_Proveedor', fn ($ip) => $ip->conStock())
                    ->orWhereDoesntHave('Inventario_Proveedor', fn ($ip) => $ip->conSkuVinculado());
            });
    }

    /**
     * Candado de seguridad: Verifica si este producto nunca tuvo ningún ingreso en IngresoProducto.
     */
    public function esReemplazable(): bool
    {
        if ($this->es_padre) {
            return false;
        }

        return !\Illuminate\Support\Facades\DB::table('DetalleComprobante as dc')
            ->join('RegistroProducto as rp', 'rp.idDetalleComprobante', '=', 'dc.idDetalleComprobante')
            ->join('IngresoProducto as ip', 'ip.idRegistro', '=', 'rp.idRegistro')
            ->where('dc.idProducto', $this->idProducto)
            ->exists();
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

    // ==============================================================
    // SISTEMA DE VARIANTES (PADRE-HIJO)
    // ==============================================================

    /**
     * Producto padre (contenedor) al que pertenece esta variante.
     */
    public function padre()
    {
        return $this->belongsTo(Producto::class, 'idProductoPadre', 'idProducto');
    }

    /**
     * Variantes hijas (colores, capacidades, etc.) de este producto padre.
     */
    public function variantes()
    {
        return $this->hasMany(Producto::class, 'idProductoPadre', 'idProducto');
    }

    public function tieneVariantes(): bool
    {
        return (bool) $this->es_padre || $this->variantes()->exists();
    }

    public function esVariante(): bool
    {
        return !is_null($this->idProductoPadre);
    }

    /**
     * Catalogo web (webunk): solo padres y productos simples, nunca variantes sueltas.
     */
    public function scopeCatalogoWeb($query)
    {
        return $query->whereNull('idProductoPadre');
    }

    /**
     * Productos vendibles (POS, facturacion, guias de remision): excluye contenedores padre.
     */
    public function scopeVendibles($query)
    {
        return $query->where('es_padre', 0);
    }

    /**
     * Stock fisico total en almacenes.
     * Si es padre, retorna la suma del stock de todas sus variantes para que el
     * catalogo web no lo muestre agotado mientras al menos un color tenga stock.
     */
    public function stockTotal(): int
    {
        if ($this->es_padre) {
            $idsVariantes = $this->relationLoaded('variantes')
                ? $this->variantes->pluck('idProducto')
                : $this->variantes()->pluck('idProducto');

            if ($idsVariantes->isEmpty()) {
                return 0;
            }

            return (int) Inventario::whereIn('idProducto', $idsVariantes)->sum('stock');
        }

        if ($this->relationLoaded('Inventario')) {
            return (int) $this->Inventario->sum('stock');
        }

        return (int) Inventario::where('idProducto', $this->idProducto)->sum('stock');
    }


    public function estadoColor()
    {
        if ($this->estadoProductoWeb == 'DISPONIBLE') {
            return 'text-success';
        } elseif ($this->estadoProductoWeb == 'OFERTA') {
            return 'text-danger';
        } else {
            return 'text-danger text-decoration-line-through';
        }
    }

    public function displayImg($img)
    {
        if (empty($img) || str_contains($img, 'noimagen.webp')) {
            return "d-none";
        }
        return "";
    }

    public function publicVideos()
    {
        return array_filter([
            $this->videoUrl1 ?? null,
            $this->videoUrl2 ?? null
        ]);
    }

    public function getYoutubeThumbnail($videoId)
    {
        if (empty($videoId)) return null;

        // Si viene una URL completa de YouTube, extraer el ID de 11 caracteres
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $videoId, $m)) {
            $videoId = $m[1];
        }

        // Validar que sea un ID de 11 caracteres
        if (!preg_match('/^[\w-]{11}$/', $videoId)) return null;

        return "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
    }

    public function getYoutubeEmbed($videoId)
    {
        if (empty($videoId)) return null;

        // Si viene una URL completa de YouTube, extraer el ID de 11 caracteres
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $videoId, $m)) {
            $videoId = $m[1];
        }

        // Validar que sea un ID válido (11 caracteres alfanum + guiones)
        if (!preg_match('/^[\w-]{11}$/', $videoId)) return null;

        // URL para iframe embed de YouTube con autoplay desactivado por defecto
        return "https://www.youtube.com/embed/{$videoId}";
    }

    public function liquidacion()
    {
        return $this->hasOne(Liquidacion::class, 'idProducto', 'idProducto');
    }

    public function hasMapper(string $plataformaNombre): bool
    {
        $idCategoria = optional($this->GrupoProducto)->idCategoria;
        $idGrupo = $this->idGrupo;

        return PlataformaMapper::whereHas('plataforma', function ($q) use ($plataformaNombre) {
            $q->where('nombrePlataforma', 'like', "%{$plataformaNombre}%");
        })
            ->where(function ($query) use ($idCategoria, $idGrupo) {
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

        return PlataformaMapper::whereHas('plataforma', function ($q) use ($plataformaNombre) {
            $q->where('nombrePlataforma', 'like', "%{$plataformaNombre}%");
        })
            ->where('tipo_template', 'completo')
            ->where(function ($query) use ($idCategoria, $idGrupo) {
                if ($idCategoria) {
                    $query->orWhere('idCategoria', $idCategoria);
                }
                if ($idGrupo) {
                    $query->orWhere('idGrupoProducto', $idGrupo);
                }
            })->exists();
    }
}
