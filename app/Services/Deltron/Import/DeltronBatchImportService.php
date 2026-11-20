<?php

namespace App\Services\Deltron\Import;

use App\Models\Catalogo\Caracteristicas_Producto;
use App\Models\Catalogo\DetalleProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Catalogo\Producto;
use App\Models\Catalogo\Publicacion;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Inventario;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use App\Models\Inventario\Liquidacion;
use App\Models\Precios\HistorialPrecioTienda;
use App\Models\Precios\PrecioTienda;
use App\Repositories\ProductoRepositoryInterface;
use App\Services\Deltron\DeltronApiService;
use App\Services\Deltron\DeltronClassifierService;
use App\Services\Deltron\DeltronSpecMapperService;
use App\Services\Deltron\DeltronTextHelper;
use App\Services\ImageService;
use App\Services\ProductoServiceInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeltronBatchImportService
{
    protected ?DeltronApiService $apiService;
    protected DeltronClassifierService $classifierService;
    protected DeltronImageImportService $imageService;

    public function __construct(
        ?DeltronApiService $apiService = null,
        ?DeltronClassifierService $classifierService = null,
        ?DeltronImageImportService $imageService = null
    ) {
        $this->apiService = $apiService;
        $this->classifierService = $classifierService ?: new DeltronClassifierService();
        $this->imageService = $imageService ?: new DeltronImageImportService($apiService);
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
        $this->imageService->setApiService($apiService);
        return $this;
    }

    protected function getApiService(): DeltronApiService
    {
        if (!$this->apiService) {
            $this->apiService = app(DeltronApiService::class);
        }
        return $this->apiService;
    }

    /**
     * Importa un micro-lote de productos seleccionados descargando la Foto 1 en WebP 800x800.
     * Soporta la creación automática de productos contenedores Padre y sus variantes Hijas (Colores),
     * así como el reciclaje opcional de IDs de productos fantasma (sin ingresos en IngresoProducto).
     */
    public function importarLoteOfertas(array $itemsAImportar, bool $reciclarFantasmas = false): array
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);

        $imgService = new ImageService();
        $destinationPath = DeltronTextHelper::getDestinationPathProductos();

        $grupos = GrupoProducto::all()->keyBy('idGrupoProducto');
        $almacenes = Almacen::all();

        $importados = [];
        $errores = [];

        foreach ($itemsAImportar as $itemData) {
            $sku = strtoupper(trim($itemData['sku'] ?? ''));
            if (empty($sku)) {
                continue;
            }

            $replaceTargetId = !empty($itemData['replace_id']) ? (int) $itemData['replace_id'] : null;
            if ($this->existeDuplicadoEnCaliente($sku, $itemData, $replaceTargetId)) {
                continue;
            }

            try {
                DB::transaction(function () use (
                    $itemData,
                    $sku,
                    $replaceTargetId,
                    $imgService,
                    $destinationPath,
                    $grupos,
                    $almacenes,
                    $itemsAImportar,
                    $reciclarFantasmas,
                    &$importados
                ) {
                    $idGrupo = !empty($itemData['idGrupo']) ? (int) $itemData['idGrupo'] : null;
                    if (!$idGrupo) {
                        throw new Exception("El producto SKU {$sku} no tiene categoría/grupo asignado. Debe asignarle una categoría antes de importar.");
                    }
                    $grupoObj = $grupos->get($idGrupo);
                    $nombreGrupo = $grupoObj ? $grupoObj->nombreGrupo : 'General';

                    $idMarca = (int) ($itemData['idMarca'] ?? 1);
                    $pn = substr(DeltronTextHelper::cleanDeltronText($itemData['part_number'] ?? $sku), 0, 50);

                    // Validar unicidad de modelo igual que en ProductoController / ProductoService
                    $modeloBase = trim((string) ($itemData['modelo_real'] ?? '')) ?: ($pn ?: $sku);
                    $modeloFinal = DeltronTextHelper::formatearModeloSiLaptop($modeloBase, $itemData, $idGrupo) ?? $modeloBase;
                    $modeloFinal = mb_substr(trim($modeloFinal), 0, 70);

                    if ($modeloFinal !== '' && app(ProductoServiceInterface::class)->existsByModelo($modeloFinal, $replaceTargetId)) {
                        throw new Exception("El modelo '{$modeloFinal}' ya se encuentra registrado en otro producto.");
                    }

                    // Asegurar título comercial limpio
                    $nombre = !empty($itemData['name']) ? trim(DeltronTextHelper::cleanDeltronText($itemData['name'])) : '';
                    if (empty($nombre) || (mb_strlen($nombre) <= 30 && strtoupper($nombre) === $nombre)) {
                        $nombre = DeltronTextHelper::obtenerTituloComercialDeltron($itemData);
                    }
                    $nombre = substr($nombre, 0, 200);

                    // Regla de negocio: Usar siempre el precio más alto (precio lista B2B base, NO el de promo)
                    $precioLista = (float) ($itemData['price'] ?? 0);
                    $precioPromo = (float) ($itemData['price_promo'] ?? 0);
                    $precioDolar = max($precioLista, $precioPromo);
                    if ($precioDolar <= 0) {
                        $precioDolar = $precioLista > 0 ? $precioLista : $precioPromo;
                    }

                    // Lógica de variantes: comprobar si este ítem debe enlazarse a un Contenedor Padre
                    $padre = $this->resolverOCrearPadre(
                        $itemData,
                        $idMarca,
                        $idGrupo,
                        $nombreGrupo,
                        $pn,
                        $nombre,
                        $precioDolar,
                        $itemsAImportar,
                        $almacenes
                    );

                    // Resolver si reciclamos un Producto Fantasma o creamos un ID nuevo
                    $ghostProd = $this->resolverProductoFantasma($itemData, $reciclarFantasmas, $padre);
                    $codigoAnterior = null;

                    if ($ghostProd) {
                        $idProducto = $ghostProd->idProducto;
                        $codigoAnterior = $ghostProd->codigoProducto;
                        if ((int) $ghostProd->idGrupo === $idGrupo && !empty($ghostProd->codigoProducto)) {
                            $codigo = $ghostProd->codigoProducto;
                        } else {
                            $codigo = $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);
                        }
                    } else {
                        $lastId = Producto::max('idProducto') ?? 0;
                        $idProducto = $lastId + 1;
                        $codigo = $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);
                    }

                    // Descargar Foto 1 (Principal) en WebP 800x800
                    $foto1Final = $this->imageService->descargarFotoPrincipal(
                        $sku,
                        $idProducto,
                        $itemData['foto1_url'] ?? null,
                        $ghostProd !== null,
                        $imgService,
                        $destinationPath
                    );

                    // Si es reciclaje, limpiar archivos de imágenes antiguas de slots 2, 3 y 4 y tablas satélite
                    if ($ghostProd) {
                        $this->limpiarSatelitesFantasma($idProducto, $destinationPath);
                    }

                    // Título y relaciones según si es variante de un padre o producto simple
                    if ($padre) {
                        $nombreFinal = substr($padre->nombreProducto . ' - ' . ($itemData['valor_variante'] ?? 'Color'), 0, 200);
                        $esPadreVal = 0;
                        $idPadreVal = $padre->idProducto;

                        if ($padre->imagenProducto1 === 'noimagen.webp' && $foto1Final !== 'noimagen.webp') {
                            $padre->imagenProducto1 = $foto1Final;
                            $padre->save();
                        }
                    } else {
                        $nombreFinal = $nombre;
                        $esPadreVal = 0;
                        $idPadreVal = null;
                    }

                    $desc = !empty($itemData['description']) ? DeltronTextHelper::cleanDeltronText($itemData['description']) : $nombreFinal;
                    $desc = DeltronTextHelper::recortarTextoEnLinea($desc, 5000);

                    $atributosProducto = [
                        'nombreProducto' => $nombreFinal,
                        'codigoProducto' => $codigo,
                        'UPC' => '0',
                        'partNumber' => $pn,
                        'idMarca' => $idMarca,
                        'idGrupo' => $idGrupo,
                        'modelo' => $modeloFinal,
                        'precioDolar' => $precioDolar,
                        'gananciaExtra' => 0.00,
                        'garantia' => DeltronTextHelper::normalizarGarantiaDeltron($itemData['warranty'] ?? null),
                        'descripcionProducto' => $desc,
                        'imagenProducto1' => $foto1Final,
                        'imagenProducto2' => null,
                        'imagenProducto3' => null,
                        'imagenProducto4' => null,
                        'videoUrl1' => null,
                        'videoUrl2' => null,
                        'estadoProductoWeb' => 'DISPONIBLE',
                        'stockMin' => 1,
                        'usar_tc_fijo' => 1,
                        'tc_fijo' => null,
                        'es_padre' => $esPadreVal,
                        'idProductoPadre' => $idPadreVal,
                    ];

                    // 1. Crear o Actualizar Producto (si se recicló un ID fantasma)
                    if ($ghostProd) {
                        $ghostProd->fill($atributosProducto);
                        $ghostProd->save();
                        $prod = $ghostProd;
                    } else {
                        $atributosProducto['idProducto'] = $idProducto;
                        $prod = Producto::create($atributosProducto);
                        $idProducto = $prod->idProducto;
                    }

                    // 2. Crear/Actualizar DetalleProducto
                    $detalleData = [
                        'mostrarPrecioWeb' => true,
                        'precio_pase' => null,
                        'tipo_variante' => null,
                        'valor_variante' => null,
                        'codigo_color_hex' => null,
                    ];
                    if ($padre && !empty($itemData['valor_variante'])) {
                        $detalleData['tipo_variante'] = $itemData['tipo_variante'] ?? 'COLOR';
                        $detalleData['valor_variante'] = $itemData['valor_variante'];
                        $detalleData['codigo_color_hex'] = $itemData['codigo_color_hex'] ?? null;
                    }

                    DetalleProducto::updateOrCreate(
                        ['idProducto' => $idProducto],
                        $detalleData
                    );

                    // 3-5. Persistir inventario físico local, Inventario_Proveedor y SKU proveedor
                    $this->persistirInventarioYProveedor($idProducto, $sku, $pn, $itemData, $ghostProd, $almacenes);

                    // 6. Autollenar especificaciones desde caché local
                    $this->autollenarEspecificacionesSoloCache($prod, $sku);

                    $importados[] = [
                        'idProducto' => $idProducto,
                        'sku' => $sku,
                        'codigoProducto' => $codigo,
                        'codigoAnterior' => $codigoAnterior,
                        'reciclado' => ($ghostProd !== null),
                        'nombreProducto' => $nombreFinal,
                        'precioDolar' => $precioDolar,
                        'foto1' => $foto1Final,
                        'es_variante' => ($padre !== null),
                        'idProductoPadre' => $padre ? $padre->idProducto : null,
                        'codigoPadre' => $padre ? $padre->codigoProducto : null,
                    ];
                });
            } catch (Exception $e) {
                Log::error("Error importando oferta SKU {$sku}: " . $e->getMessage());
                $errores[] = [
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ];
            }

            gc_collect_cycles();
        }

        return [
            'success' => true,
            'importados_count' => count($importados),
            'importados' => $importados,
            'errores' => $errores,
        ];
    }

    protected function existeDuplicadoEnCaliente(string $sku, array $itemData, ?int $replaceTargetId): bool
    {
        $pnItem = trim($itemData['part_number'] ?? '');
        $cleanPnItem = preg_replace('/[^A-Z0-9]/', '', strtoupper($pnItem));
        $basePnItem = DeltronTextHelper::limpiarSufijoPartNumber($pnItem);
        $cleanBasePnItem = preg_replace('/[^A-Z0-9]/', '', strtoupper($basePnItem));

        $duplicado = Producto::where('modelo', $sku)
            ->when($replaceTargetId, fn($q) => $q->where('idProducto', '!=', $replaceTargetId))
            ->exists()
            || InventarioProveedorSku::where('sku_proveedor', $sku)
                ->when($replaceTargetId, function ($q) use ($replaceTargetId) {
                    $q->whereHas('inventarioProveedor', fn($sq) => $sq->where('idProducto', '!=', $replaceTargetId));
                })
                ->exists();

        if (!$duplicado && !empty($cleanPnItem)) {
            $duplicado = Producto::where(function ($q) use ($pnItem, $cleanPnItem, $cleanBasePnItem) {
                $q->where('partNumber', $pnItem);
                if (!empty($cleanPnItem)) {
                    $q->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanPnItem]);
                }
                if (!empty($cleanBasePnItem)) {
                    $q->orWhereRaw("UPPER(REPLACE(REPLACE(REPLACE(REPLACE(partNumber, '-', ''), '_', ''), ' ', ''), '/', '')) = ?", [$cleanBasePnItem]);
                }
            })
                ->when($replaceTargetId, fn($q) => $q->where('idProducto', '!=', $replaceTargetId))
                ->exists();
        }

        return $duplicado;
    }

    protected function resolverOCrearPadre(
        array $itemData,
        int $idMarca,
        int $idGrupo,
        string $nombreGrupo,
        string $pn,
        string $nombre,
        float $precioDolar,
        array $itemsAImportar,
        $almacenes
    ): ?Producto {
        $esVariante = !empty($itemData['es_variante']) && !empty($itemData['valor_variante']);
        if (!$esVariante) {
            return null;
        }

        $basePn = !empty($itemData['part_number_base']) ? trim($itemData['part_number_base']) : $pn;
        $nombrePadre = !empty($itemData['nombre_padre']) ? trim($itemData['nombre_padre']) : $nombre;
        $modeloBase = !empty($itemData['modelo_base']) ? trim($itemData['modelo_base']) : $basePn;

        $padre = Producto::where('es_padre', 1)
            ->where('partNumber', $basePn)
            ->where('idMarca', $idMarca)
            ->first();

        if ($padre) {
            return $padre;
        }

        $familiaKey = $itemData['familia_key'] ?? ($idMarca . '|' . $basePn);
        $coincidentesEnLote = array_filter($itemsAImportar, function ($it) use ($familiaKey, $idMarca, $basePn) {
            $fk = $it['familia_key'] ?? ($idMarca . '|' . ($it['part_number_base'] ?? ($it['part_number'] ?? '')));
            return $fk === $familiaKey;
        });

        if (count($coincidentesEnLote) < 2 && empty($itemData['forzar_padre'])) {
            return null;
        }

        $lastIdPadre = Producto::max('idProducto') ?? 0;
        $idPadre = $lastIdPadre + 1;
        $codigoPadre = $this->classifierService->generarSiguienteCodigoProducto($idGrupo, $nombreGrupo);

        $descPadre = !empty($itemData['description']) ? DeltronTextHelper::cleanDeltronText($itemData['description']) : $nombrePadre;
        $descPadre = DeltronTextHelper::recortarTextoEnLinea($descPadre, 5000);

        $padre = Producto::create([
            'idProducto' => $idPadre,
            'nombreProducto' => substr($nombrePadre, 0, 200),
            'codigoProducto' => $codigoPadre,
            'UPC' => '0',
            'partNumber' => substr($basePn, 0, 50),
            'idMarca' => $idMarca,
            'idGrupo' => $idGrupo,
            'modelo' => substr($modeloBase, 0, 70),
            'precioDolar' => $precioDolar,
            'gananciaExtra' => 0.00,
            'garantia' => DeltronTextHelper::normalizarGarantiaDeltron($itemData['warranty'] ?? null),
            'descripcionProducto' => $descPadre,
            'imagenProducto1' => 'noimagen.webp',
            'imagenProducto2' => null,
            'imagenProducto3' => null,
            'imagenProducto4' => null,
            'estadoProductoWeb' => 'DISPONIBLE',
            'stockMin' => 1,
            'usar_tc_fijo' => 1,
            'es_padre' => 1,
            'idProductoPadre' => null,
        ]);
        $idPadre = $padre->idProducto;

        DetalleProducto::updateOrCreate(
            ['idProducto' => $idPadre],
            [
                'mostrarPrecioWeb' => true,
                'tipo_variante' => $itemData['tipo_variante'] ?? 'COLOR',
            ]
        );

        foreach ($almacenes as $alm) {
            Inventario::create([
                'idProducto' => $idPadre,
                'idAlmacen' => $alm->idAlmacen,
                'stock' => 0,
            ]);
        }

        return $padre;
    }

    protected function resolverProductoFantasma(array $itemData, bool $reciclarFantasmas, ?Producto $padre): ?Producto
    {
        if (!empty($itemData['replace_id'])) {
            $candGhost = Producto::find((int) $itemData['replace_id']);
            if ($candGhost && $candGhost->esReemplazable()) {
                return $candGhost;
            }
            return null;
        }

        if ($reciclarFantasmas && !$padre) {
            return app(ProductoRepositoryInterface::class)->obtenerSiguienteFantasmaAutoReciclable(12);
        }

        return null;
    }

    protected function limpiarSatelitesFantasma(int $idProducto, string $destinationPath): void
    {
        $this->imageService->limpiarFotosSecundariasDisco($idProducto, $destinationPath);

        Caracteristicas_Producto::where('idProducto', $idProducto)->delete();
        Liquidacion::where('idProducto', $idProducto)->delete();
        PrecioTienda::where('idProducto', $idProducto)->delete();
        HistorialPrecioTienda::where('idProducto', $idProducto)->delete();

        // Eliminar publicaciones que no tengan ventas asociadas para evitar violar la Foreign Key de EgresoProducto/DetalleVenta
        Publicacion::where('idProducto', $idProducto)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('EgresoProducto as ep')
                    ->whereColumn('ep.idPublicacion', 'Publicacion.idPublicacion');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('DetalleVenta as dv')
                    ->whereColumn('dv.idPublicacion', 'Publicacion.idPublicacion');
            })
            ->delete();

        // Desactivar cualquier publicación residual con ventas históricas
        Publicacion::where('idProducto', $idProducto)->update(['estado' => 0]);
    }

    protected function persistirInventarioYProveedor(
        int $idProducto,
        string $sku,
        string $pn,
        array $itemData,
        ?Producto $ghostProd,
        $almacenes
    ): void {
        // Asegurar registros de inventario físico en cero para cada almacén local
        if ($ghostProd && Inventario::where('idProducto', $idProducto)->exists()) {
            Inventario::where('idProducto', $idProducto)->update(['stock' => 0, 'idUbicacionExacta' => null]);
        } else {
            foreach ($almacenes as $alm) {
                Inventario::create([
                    'idProducto' => $idProducto,
                    'idAlmacen' => $alm->idAlmacen,
                    'stock' => 0,
                ]);
            }
        }

        // Crear o Actualizar Inventario_Proveedor (Deltron)
        $stockProv = (int) ($itemData['lima_stock'] ?? ($itemData['total_stock'] ?? 1));
        $invProvExistente = Inventario_Proveedor::where('idProducto', $idProducto)->first();

        if ($invProvExistente) {
            $idInvProv = $invProvExistente->idInventarioProveedor;
            InventarioProveedorSku::where('idInventarioProveedor', $idInvProv)->delete();
            $invProvExistente->idProveedor = 1;
            $invProvExistente->stock = max(1, $stockProv);
            $invProvExistente->estado = 'VIGENTE';
            $invProvExistente->save();
        } else {
            $lastInvProv = Inventario_Proveedor::max('idInventarioProveedor') ?? 0;
            $idInvProv = $lastInvProv + 1;

            Inventario_Proveedor::create([
                'idInventarioProveedor' => $idInvProv,
                'idProducto' => $idProducto,
                'idProveedor' => 1,
                'stock' => max(1, $stockProv),
                'estado' => 'VIGENTE',
            ]);
        }

        // Vincular SKU proveedor
        InventarioProveedorSku::updateOrCreate(
            [
                'idInventarioProveedor' => $idInvProv,
                'idProveedor' => 1,
            ],
            [
                'sku_proveedor' => $sku,
                'part_number' => $pn,
                'mini_codigo' => $itemData['mini_code'] ?? null,
                'precio_b2b' => (float) ($itemData['price'] ?? 0),
                'precio_promo' => !empty($itemData['price_promo']) ? (float) $itemData['price_promo'] : null,
                'hay_mas' => !empty($itemData['lima_hay_mas']),
            ]
        );
    }

    protected function autollenarEspecificacionesSoloCache(Producto $prod, string $sku): void
    {
        try {
            $specMapper = new DeltronSpecMapperService($this->getApiService(), $this->classifierService);
            $specMapper->llenarEspecificacionesProducto($prod, $sku, true);
        } catch (\Throwable $e) {
            Log::warning("No se pudieron autollenar specs para {$sku}: " . $e->getMessage());
        }
    }
}
