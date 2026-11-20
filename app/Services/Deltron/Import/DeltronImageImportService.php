<?php

namespace App\Services\Deltron\Import;

use App\Models\Catalogo\Producto;
use App\Models\Inventario\Inventario_Proveedor;
use App\Models\Inventario\InventarioProveedorSku;
use App\Services\Deltron\DeltronApiService;
use App\Services\Deltron\DeltronTextHelper;
use App\Services\ImageService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class DeltronImageImportService
{
    protected ?DeltronApiService $apiService;

    public function __construct(?DeltronApiService $apiService = null)
    {
        $this->apiService = $apiService;
    }

    public function setApiService(DeltronApiService $apiService): self
    {
        $this->apiService = $apiService;
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
     * Resuelve la URL de Foto 1 desde los metadatos de imágenes de un ítem/ficha Deltron,
     * priorizando siempre la versión en alta resolución (800x800 del carrusel) sobre la miniatura (320x320 de listados).
     */
    public function resolverUrlFoto1DesdeItem(?array $images, string $sku, bool $conFallbackCdn = true): ?string
    {
        $skuClean = strtoupper(trim($sku));
        $parsed = $this->separarYOrdenarUrls($images, $skuClean);

        if (!empty($parsed['portada_carrusel'])) {
            return $parsed['portada_carrusel'];
        }
        if (!empty($parsed['secundarias'][0])) {
            return $parsed['secundarias'][0];
        }
        if (!empty($parsed['listado'])) {
            return $parsed['listado'];
        }

        return $conFallbackCdn ? DeltronTextHelper::obtenerUrlCdnDeltron($skuClean) : null;
    }

    /**
     * Descarga y convierte la Foto 1 a WebP 800x800, con respaldo hacia API individual y Web pública de Deltron
     * priorizando las versiones de carrusel (800x800) sobre listado (320x320).
     */
    public function descargarFotoPrincipal(
        string $sku,
        int $idProducto,
        ?string $foto1Url,
        bool $esReciclaje,
        ImageService $imgService,
        string $destinationPath
    ): string {
        if (empty($foto1Url) || str_contains($foto1Url, 'blanco.jpg')) {
            $foto1Url = DeltronTextHelper::obtenerUrlCdnDeltron($sku);
        }

        $foto1Final = 'noimagen.webp';
        $timestamp = time();

        if (!empty($foto1Url) && is_string($foto1Url) && !str_contains($foto1Url, 'blanco.jpg')) {
            try {
                $saved = $imgService->createImage($foto1Url, $idProducto . '_1', $destinationPath);
                if ($saved) {
                    $foto1Final = 'productos/IMGPRO' . $idProducto . '_1.webp' . ($esReciclaje ? '?v=' . $timestamp : '');
                }
            } catch (Exception $imgEx) {
                Log::warning("No se pudo procesar Foto 1 inicial de Deltron para {$sku}: " . $imgEx->getMessage());
                $foto1Final = 'noimagen.webp';
            }
        }

        // Fallback si la URL inicial o del CDN dio 404 (productos nuevos con sufijo timestamp en Deltron)
        if ($foto1Final === 'noimagen.webp') {
            $fallbackUrls = [];
            try {
                $rawSku = $this->getApiService()->getProductoPorSku($sku);
                if (is_array($rawSku) && !empty($rawSku['images'])) {
                    $parsedApi = $this->separarYOrdenarUrls($rawSku['images'], strtoupper(trim($sku)));
                    if (!empty($parsedApi['portada_carrusel'])) {
                        $fallbackUrls[] = $parsedApi['portada_carrusel'];
                    }
                    foreach ($parsedApi['secundarias'] as $secUrl) {
                        if (!in_array($secUrl, $fallbackUrls, true)) {
                            $fallbackUrls[] = $secUrl;
                        }
                    }
                    if (!empty($parsedApi['listado']) && !in_array($parsedApi['listado'], $fallbackUrls, true)) {
                        $fallbackUrls[] = $parsedApi['listado'];
                    }
                }
            } catch (\Throwable $e) {
                // Continúa al respaldo web
            }

            if (empty($fallbackUrls)) {
                $webImgs = DeltronTextHelper::extraerImagenesDesdeWebDeltron($sku);
                foreach (($webImgs['carrusel'] ?? []) as $wUrl) {
                    if (is_string($wUrl) && !str_contains($wUrl, 'blanco.jpg') && !in_array($wUrl, $fallbackUrls, true)) {
                        $fallbackUrls[] = $wUrl;
                    }
                }
                if (!empty($webImgs['listado']) && !str_contains((string) $webImgs['listado'], 'blanco.jpg') && !in_array($webImgs['listado'], $fallbackUrls, true)) {
                    $fallbackUrls[] = $webImgs['listado'];
                }
            }

            foreach ($fallbackUrls as $candidateUrl) {
                if ($candidateUrl === $foto1Url) {
                    continue;
                }
                try {
                    $saved = $imgService->createImage($candidateUrl, $idProducto . '_1', $destinationPath);
                    if ($saved) {
                        $foto1Final = 'productos/IMGPRO' . $idProducto . '_1.webp' . ($esReciclaje ? '?v=' . $timestamp : '');
                        break;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $foto1Final;
    }

    /**
     * Elimina archivos físicos de fotos secundarias (slots 2, 3 y 4) al reciclar un producto fantasma.
     */
    public function limpiarFotosSecundariasDisco(int $idProducto, string $destinationPath): void
    {
        $localPath = public_path('storage/productos');
        $storageAppPath = storage_path('app/public/productos');

        for ($slotNum = 2; $slotNum <= 4; $slotNum++) {
            $fName = 'IMGPRO' . $idProducto . '_' . $slotNum . '.webp';
            foreach ([$destinationPath . '/' . $fName, $localPath . '/' . $fName, $storageAppPath . '/' . $fName] as $oldImg) {
                if (is_file($oldImg)) {
                    @unlink($oldImg);
                }
            }
        }
    }

    /**
     * Completa las fotos secundarias (2, 3 y 4) de un producto (evitando duplicar Foto 1 gracias al dHash 128-bit
     * con auto-recorte). Además, si Foto 1 está en baja resolución (<600px) y Deltron dispone de la misma
     * portada en 800x800, la actualiza automáticamente.
     *
     * @return array{success:bool,message:string,agregadas:int,slots:array,sku:?string}
     */
    public function completarFotosProducto(Producto $producto): array
    {
        // 1. Resolver SKU Deltron del producto
        $sku = $this->resolverSkuProducto($producto);
        if (!$sku) {
            return [
                'success' => false,
                'message' => 'Este producto no tiene un SKU de Deltron vinculado, no se pueden buscar fotos.',
                'agregadas' => 0,
                'slots' => [],
                'sku' => null,
            ];
        }
        $skuClean = strtoupper(trim($sku));

        // 2. Determinar qué slots llenar y cargar huellas visuales existentes:
        // Se salta siempre la Foto 1 (a menos que esté vacía con noimagen.webp) y se completan los slots 2, 3 y 4.
        // Si algún slot secundario está vacío o contiene un duplicado visual previo (ej. Foto 1 repetida), se marca para reemplazo.
        $default = asset('storage/noimagen.webp');
        $actuales = $producto->publicImages();
        $faltaFoto1 = ($actuales[0] ?? $default) === $default;

        $destinationPath = DeltronTextHelper::getDestinationPathProductos();
        if (!is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        $hashesVistos = [];
        $huellasVisuales = [];
        $huellaFoto1 = null;
        $maxDimFoto1 = 0;

        if (!$faltaFoto1) {
            $bytesFoto1 = $this->leerBytesSlot($producto, 1, $destinationPath);
            if ($bytesFoto1 !== null) {
                $dim1 = @getimagesizefromstring($bytesFoto1);
                if (is_array($dim1)) {
                    $maxDimFoto1 = max((int) ($dim1[0] ?? 0), (int) ($dim1[1] ?? 0));
                }
                $hashesVistos[md5($bytesFoto1)] = true;
                $huella1 = $this->calcularHuellaVisual($bytesFoto1);
                if ($huella1 !== null) {
                    $huellasVisuales[] = $huella1;
                    $huellaFoto1 = $huella1;
                }
                unset($bytesFoto1);
            }
        }

        $slotsARemplazar = [];
        $keptHashes = [];
        $keptHuellas = [];

        for ($s = 2; $s <= 4; $s++) {
            if (($actuales[$s - 1] ?? $default) === $default) {
                $slotsARemplazar[] = $s;
                continue;
            }

            $bytesS = $this->leerBytesSlot($producto, $s, $destinationPath);
            if ($bytesS === null) {
                $slotsARemplazar[] = $s;
                continue;
            }

            $hashS = md5($bytesS);
            $huellaS = $this->calcularHuellaVisual($bytesS);
            unset($bytesS);

            $esDup = isset($hashesVistos[$hashS]) || isset($keptHashes[$hashS]);
            if (!$esDup && $huellaS !== null) {
                foreach (array_merge($huellasVisuales, array_values($keptHuellas)) as $hExistente) {
                    if ($this->sonImagenesSimilares($huellaS, $hExistente, 12)) {
                        $esDup = true;
                        break;
                    }
                }
            }

            if ($esDup) {
                $slotsARemplazar[] = $s;
            } else {
                $keptHashes[$hashS] = true;
                if ($huellaS !== null) {
                    $keptHuellas[$s] = $huellaS;
                }
            }
        }

        if (!empty($slotsARemplazar)) {
            $slotsSecundarios = $slotsARemplazar;
            foreach ($keptHashes as $kHash => $_) {
                $hashesVistos[$kHash] = true;
            }
            foreach ($keptHuellas as $hKept) {
                $huellasVisuales[] = $hKept;
            }
        } else {
            // Si todos los secundarios están ocupados y son únicos, al pulsar el botón refresca los slots 2, 3 y 4
            $slotsSecundarios = [2, 3, 4];
        }

        // 3. Obtener metadatos de imágenes verificando que realmente existan URLs válidas en el carrusel
        $imagesMeta = $this->obtenerMetadatosImagenes($skuClean);

        // 4. Construir lista ordenada de portada y URLs secundarias
        $parsed = $this->separarYOrdenarUrls($imagesMeta, $skuClean);
        $portadaCarrusel = $parsed['portada_carrusel'];
        $urlsSecundarias = $parsed['secundarias'];
        $carruselVacio = ($portadaCarrusel === null && empty($urlsSecundarias));

        // Agregar fallback del CDN de Deltron: solo _1.jpg.._4.jpg cuando falten URLs secundarias
        $cdnBase = DeltronTextHelper::obtenerUrlCdnDeltron($skuClean);
        if ($cdnBase && count($urlsSecundarias) < count($slotsSecundarios)) {
            for ($n = 1; $n <= 4; $n++) {
                $uCdn = preg_replace('/\.jpg$/i', "_{$n}.jpg", $cdnBase);
                if (!in_array($uCdn, $urlsSecundarias, true)) {
                    $urlsSecundarias[] = $uCdn;
                }
            }
        }

        // 5. Descargar y convertir en memoria (1 imagen a la vez para consumo mínimo de RAM)
        $manager = new ImageManager(Driver::class);
        $timestamp = time();
        $updates = [];
        $slotsLlenados = [];

        // 5.1 Auto-upgrade seguro de Foto 1 a 800x800 si en disco mide <600px y coincide con la portada del carrusel
        if (!$faltaFoto1 && $maxDimFoto1 > 0 && $maxDimFoto1 < 600 && $huellaFoto1 !== null) {
            $this->intentarAutoUpgradeFoto1(
                $producto,
                $portadaCarrusel,
                $urlsSecundarias,
                $huellaFoto1,
                $maxDimFoto1,
                $manager,
                $destinationPath,
                $timestamp,
                $updates,
                $slotsLlenados,
                $hashesVistos,
                $huellasVisuales
            );
        }

        $procesarSlot = function (int $slot, array &$poolUrls) use (
            $producto,
            $manager,
            $destinationPath,
            $timestamp,
            &$updates,
            &$slotsLlenados,
            &$hashesVistos,
            &$huellasVisuales
        ): bool {
            while (!empty($poolUrls)) {
                $url = array_shift($poolUrls);
                try {
                    $resp = Http::withoutVerifying()
                        ->connectTimeout(4)
                        ->timeout(8)
                        ->get($url);

                    if (!$resp->successful()) {
                        continue;
                    }
                    $body = $resp->body();
                    unset($resp);

                    if (strlen($body) < 1500) {
                        unset($body);
                        continue;
                    }

                    $hash = md5($body);
                    if (isset($hashesVistos[$hash])) {
                        unset($body);
                        continue;
                    }

                    $huellaNueva = $this->calcularHuellaVisual($body);
                    if ($huellaNueva !== null) {
                        $esDuplicadaVisual = false;
                        foreach ($huellasVisuales as $hExistente) {
                            if ($this->sonImagenesSimilares($huellaNueva, $hExistente, 12)) {
                                $esDuplicadaVisual = true;
                                break;
                            }
                        }
                        if ($esDuplicadaVisual) {
                            unset($body);
                            continue;
                        }
                    }

                    $fileName = 'IMGPRO' . $producto->idProducto . '_' . $slot . '.webp';
                    $fullDest = $destinationPath . '/' . $fileName;

                    $image = $manager->read($body);
                    unset($body);
                    $encoded = (string) $image->toWebp(80);
                    unset($image);

                    $encodedHash = md5($encoded);
                    if (isset($hashesVistos[$encodedHash])) {
                        unset($encoded);
                        continue;
                    }

                    if (@file_put_contents($fullDest, $encoded) === false) {
                        unset($encoded);
                        continue;
                    }
                    unset($encoded);

                    $hashesVistos[$encodedHash] = true;
                    if ($huellaNueva !== null) {
                        $huellasVisuales[] = $huellaNueva;
                    }

                    $this->sincronizarCopiaStorage($fullDest, $fileName, $destinationPath);

                    $hashesVistos[$hash] = true;
                    $updates['imagenProducto' . $slot] = 'productos/' . $fileName . '?v=' . $timestamp;
                    $slotsLlenados[] = $slot;
                    return true;
                } catch (\Throwable $e) {
                    continue;
                }
            }
            return false;
        };

        // Si la Foto 1 estaba vacía, llenarla prefiriendo la portada 800x800 del carrusel antes que listado/CDN
        if ($faltaFoto1) {
            $llenoSlot1 = false;
            if ($portadaCarrusel !== null) {
                $poolPortada = [$portadaCarrusel];
                $llenoSlot1 = $procesarSlot(1, $poolPortada);
            }
            if (!$llenoSlot1 && !$carruselVacio && !empty($urlsSecundarias)) {
                $llenoSlot1 = $procesarSlot(1, $urlsSecundarias);
            }
            if (!$llenoSlot1) {
                $fallbackSlot1 = [];
                if (!empty($parsed['listado'])) {
                    $fallbackSlot1[] = $parsed['listado'];
                }
                if ($cdnBase && $cdnBase !== ($parsed['listado'] ?? null)) {
                    $fallbackSlot1[] = $cdnBase;
                }
                if (!empty($fallbackSlot1)) {
                    $llenoSlot1 = $procesarSlot(1, $fallbackSlot1);
                }
            }
            if (!$llenoSlot1 && !empty($urlsSecundarias)) {
                $procesarSlot(1, $urlsSecundarias);
            }
        }

        // Llenar los slots secundarios (2, 3 y 4)
        foreach ($slotsSecundarios as $slot) {
            if (empty($urlsSecundarias)) {
                break;
            }
            $procesarSlot($slot, $urlsSecundarias);
        }

        unset($manager);
        gc_collect_cycles();

        if (empty($updates)) {
            return [
                'success' => false,
                'message' => "Deltron no tiene fotos adicionales (2, 3, 4) disponibles para el SKU {$skuClean}.",
                'agregadas' => 0,
                'slots' => [],
                'sku' => $skuClean,
            ];
        }

        foreach ($updates as $campo => $valor) {
            $producto->{$campo} = $valor;
        }
        $producto->save();
        if (method_exists($producto, 'invalidarCacheWeb')) {
            $producto->invalidarCacheWeb();
        }

        $n = count($slotsLlenados);
        return [
            'success' => true,
            'message' => "Se completaron {$n} foto(s) en los espacios: " . implode(', ', $slotsLlenados) . '.',
            'agregadas' => $n,
            'slots' => $slotsLlenados,
            'sku' => $skuClean,
        ];
    }

    /**
     * Calcula una huella visual perceptual (dHash 128-bit horizontal + vertical con auto-recorte de fondo blanco)
     * para detectar fotos visualmente idénticas aunque varíe resolución/margen (320px vs 800px) o formato (JPG vs WebP),
     * sin marcar erróneamente como duplicadas distintas vistas del mismo producto.
     */
    public function calcularHuellaVisual(string $bytes): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            return null;
        }
        if (function_exists('imageistruecolor') && !imageistruecolor($src) && function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($src);
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= 0 || $h <= 0) {
            imagedestroy($src);
            return null;
        }

        // Auto-recortar márgenes blancos/transparentes para que distintas resoluciones de la misma foto
        // coincidan incluso cuando cambia el relleno exterior (ej. 320x320 vs 800x800).
        $step = max(1, (int) floor(min($w, $h) / 160));
        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;
                if ($alpha >= 100) {
                    continue;
                }
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < 242 || $g < 242 || $b < 242) {
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        $cw = $maxX - $minX + 1;
        $ch = $maxY - $minY + 1;
        if ($maxX <= $minX || $maxY <= $minY || $cw < ($w * 0.05) || $ch < ($h * 0.05)) {
            $minX = 0;
            $minY = 0;
            $cw = $w;
            $ch = $h;
        }

        $small = imagecreatetruecolor(9, 9);
        imagealphablending($small, true);
        $white = imagecolorallocate($small, 255, 255, 255);
        imagefill($small, 0, 0, $white);
        imagecopyresampled($small, $src, 0, 0, $minX, $minY, 9, 9, $cw, $ch);
        imagedestroy($src);

        $gray = [];
        for ($y = 0; $y < 9; $y++) {
            for ($x = 0; $x < 9; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray[$y][$x] = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }
        imagedestroy($small);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $bits .= ($gray[$y][$x] > $gray[$y][$x + 1]) ? '1' : '0';
                $bits .= ($gray[$y][$x] > $gray[$y + 1][$x]) ? '1' : '0';
            }
        }
        return $bits;
    }

    public function sonImagenesSimilares(string $h1, string $h2, int $maxDistancia = 12): bool
    {
        $len = min(strlen($h1), strlen($h2));
        if ($len === 0) {
            return false;
        }
        $dist = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($h1[$i] !== $h2[$i]) {
                $dist++;
                if ($dist > $maxDistancia) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Separa la portada explícita del carrusel, el listado fallback y las vistas secundarias ordenadas por índice.
     * Soporta tanto esquema API (`image_listado`/`images_carrusel`) como esquema Ficha (`listado`/`carrusel`).
     *
     * @return array{portada_carrusel:?string, listado:?string, secundarias:array<int, string>}
     */
    protected function separarYOrdenarUrls(?array $imagesMeta, string $skuClean): array
    {
        if (empty($imagesMeta) || !is_array($imagesMeta)) {
            return [
                'portada_carrusel' => null,
                'listado' => null,
                'secundarias' => [],
            ];
        }

        $listadoRaw = $imagesMeta['image_listado'] ?? ($imagesMeta['listado'] ?? null);
        $listadoValido = (is_string($listadoRaw) && str_starts_with($listadoRaw, 'http') && !str_contains($listadoRaw, 'blanco.jpg'))
            ? $listadoRaw
            : null;

        $carruselRaw = $imagesMeta['images_carrusel'] ?? ($imagesMeta['carrusel'] ?? []);
        if (!is_array($carruselRaw)) {
            $carruselRaw = [];
        }

        $portadaCarrusel = null;
        $portadaTs = -1;
        $portadaPriority = -1;
        $secundariasOrdenadas = [];
        $secundariasTimestamp = [];
        $secundariasSinIndice = [];

        foreach ($carruselRaw as $c) {
            $cUrl = is_string($c) ? $c : ($c['url'] ?? ($c['image'] ?? null));
            if (!is_string($cUrl) || !str_starts_with($cUrl, 'http') || str_contains($cUrl, 'blanco.jpg')) {
                continue;
            }
            $base = basename(parse_url($cUrl, PHP_URL_PATH) ?: $cUrl);

            $idx = null;
            $ts = 0;
            if (preg_match('/_(\d{1,3})_(\d{6,})\.[a-z0-9]+$/i', $base, $m)) {
                $idx = (int) $m[1];
                $ts = (int) $m[2];
            } elseif (preg_match('/^0*([1-9]\d{0,2})_/', $base, $m)) {
                $idx = (int) $m[1];
                if (preg_match('/_(\d{6,})\.[a-z0-9]+$/i', $base, $tm)) {
                    $ts = (int) $tm[1];
                }
            } elseif (preg_match('/_(\d{1,3})\.[a-z0-9]+$/i', $base, $m)) {
                $idx = (int) $m[1];
            }

            $esIsMain = is_array($c) && !empty($c['is_main']);
            $es00Portada = (bool) preg_match('/^00_portada/i', $base);
            $esArchivoBaseSinIndice = ($idx === null) && (bool) preg_match('/^[A-Za-z0-9-]+(?:_\d{6,})?\.[a-z0-9]+$/i', $base);
            $esPortada = $esIsMain || $es00Portada || $esArchivoBaseSinIndice;

            if ($esPortada) {
                $prio = ($esIsMain || $es00Portada) ? 2 : 1;
                $tsPortada = 0;
                if (preg_match('/_(\d{6,})\.[a-z0-9]+$/i', $base, $pm)) {
                    $tsPortada = (int) $pm[1];
                }
                if (
                    $portadaCarrusel === null
                    || $prio > $portadaPriority
                    || ($prio === $portadaPriority && $tsPortada >= $portadaTs)
                ) {
                    $portadaCarrusel = $cUrl;
                    $portadaPriority = $prio;
                    $portadaTs = $tsPortada;
                }
                continue;
            }

            if ($idx !== null) {
                if (!isset($secundariasOrdenadas[$idx]) || $ts >= ($secundariasTimestamp[$idx] ?? 0)) {
                    $secundariasOrdenadas[$idx] = $cUrl;
                    $secundariasTimestamp[$idx] = $ts;
                }
            } else {
                $secundariasSinIndice[] = $cUrl;
            }
        }

        ksort($secundariasOrdenadas, SORT_NUMERIC);
        $secundarias = array_values($secundariasOrdenadas);
        foreach ($secundariasSinIndice as $u) {
            if (!in_array($u, $secundarias, true)) {
                $secundarias[] = $u;
            }
        }

        return [
            'portada_carrusel' => $portadaCarrusel,
            'listado' => $listadoValido,
            'secundarias' => $secundarias,
        ];
    }

    protected function resolverSkuProducto(Producto $producto): ?string
    {
        $inv = Inventario_Proveedor::where('idProducto', $producto->idProducto)->first();
        if (!$inv) {
            return null;
        }

        return InventarioProveedorSku::where('idInventarioProveedor', $inv->idInventarioProveedor)
            ->whereNotNull('sku_proveedor')
            ->where('sku_proveedor', '!=', '')
            ->value('sku_proveedor');
    }

    protected function leerBytesSlot(Producto $producto, int $slot, string $destinationPath): ?string
    {
        $val = (string) ($producto->{'imagenProducto' . $slot} ?? '');
        if ($val === '' || $val === 'noimagen.webp') {
            return null;
        }

        $pathLimpio = parse_url($val, PHP_URL_PATH) ?: $val;
        $base = basename($pathLimpio);
        if ($base === '' || $base === 'noimagen.webp') {
            return null;
        }

        foreach ([
            $destinationPath . '/' . $base,
            public_path('storage/productos/' . $base),
            storage_path('app/public/productos/' . $base),
        ] as $ruta) {
            if (is_file($ruta)) {
                $bytes = @file_get_contents($ruta);
                if ($bytes !== false && $bytes !== '') {
                    return $bytes;
                }
            }
        }

        return null;
    }

    protected function tieneCarruselValido(?array $meta): bool
    {
        if (empty($meta) || !is_array($meta)) {
            return false;
        }
        $carrusel = $meta['carrusel'] ?? ($meta['images_carrusel'] ?? []);
        if (!is_array($carrusel)) {
            return false;
        }
        foreach ($carrusel as $c) {
            $u = is_string($c) ? $c : ($c['url'] ?? ($c['image'] ?? null));
            if (is_string($u) && str_starts_with($u, 'http') && !str_contains($u, 'blanco.jpg')) {
                return true;
            }
        }
        return false;
    }

    protected function obtenerMetadatosImagenes(string $skuClean): ?array
    {
        $imagesMeta = null;
        $savedFicha = null;
        $snapshotPath = storage_path('app/deltron_fichas/' . $skuClean . '.json');

        if (is_file($snapshotPath)) {
            $savedFicha = @json_decode(@file_get_contents($snapshotPath), true);
            if (is_array($savedFicha) && $this->tieneCarruselValido($savedFicha['images'] ?? null)) {
                $imagesMeta = $savedFicha['images'];
            }
        }

        // Si el JSON local proviene del snapshot masivo (con carrusel vacío), consultar la API individual del SKU
        if (!$this->tieneCarruselValido($imagesMeta)) {
            try {
                $raw = $this->getApiService()->getProductoPorSku($skuClean);
                if (is_array($raw) && !empty($raw['images'])) {
                    $apiMeta = [
                        'listado' => $raw['images']['image_listado'] ?? null,
                        'carrusel' => $raw['images']['images_carrusel'] ?? [],
                    ];
                    if ($this->tieneCarruselValido($apiMeta) || (!empty($apiMeta['listado']) && !str_contains((string) $apiMeta['listado'], 'blanco.jpg'))) {
                        $imagesMeta = $apiMeta;
                        if (is_array($savedFicha)) {
                            $savedFicha['images'] = $imagesMeta;
                            @file_put_contents($snapshotPath, json_encode($savedFicha, JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning("Completar fotos {$skuClean}: aviso al consultar API Deltron: " . $e->getMessage());
            }
        }

        // Respaldo Web (producto_v2.php): cuando el API no reporta las fotos con timestamp pero la web sí las muestra
        if (!$this->tieneCarruselValido($imagesMeta)) {
            $webMeta = DeltronTextHelper::extraerImagenesDesdeWebDeltron($skuClean);
            if ($this->tieneCarruselValido($webMeta) || !empty($webMeta['listado'])) {
                $imagesMeta = $webMeta;
                if (is_array($savedFicha)) {
                    $savedFicha['images'] = $imagesMeta;
                    @file_put_contents($snapshotPath, json_encode($savedFicha, JSON_UNESCAPED_UNICODE));
                }
            }
        }

        return $imagesMeta;
    }

    protected function intentarAutoUpgradeFoto1(
        Producto $producto,
        ?string $portadaCarrusel,
        array &$urlsSecundarias,
        string $huellaFoto1,
        int $maxDimFoto1,
        ImageManager $manager,
        string $destinationPath,
        int $timestamp,
        array &$updates,
        array &$slotsLlenados,
        array &$hashesVistos,
        array &$huellasVisuales
    ): void {
        $candidataUpgrade1 = $portadaCarrusel ?? ($urlsSecundarias[0] ?? null);
        if (
            !is_string($candidataUpgrade1)
            || !str_starts_with($candidataUpgrade1, 'http')
            || str_contains($candidataUpgrade1, '/listados/')
            || str_contains($candidataUpgrade1, 'blanco.jpg')
        ) {
            return;
        }

        try {
            $resp = Http::withoutVerifying()
                ->connectTimeout(4)
                ->timeout(8)
                ->get($candidataUpgrade1);

            if (!$resp->successful()) {
                unset($resp);
                return;
            }
            $body = $resp->body();
            unset($resp);

            if (strlen($body) < 1500) {
                unset($body);
                return;
            }

            $dimNew = @getimagesizefromstring($body);
            $maxDimNew = is_array($dimNew) ? max((int) ($dimNew[0] ?? 0), (int) ($dimNew[1] ?? 0)) : 0;
            $huellaNew = $this->calcularHuellaVisual($body);

            if ($maxDimNew <= $maxDimFoto1 || $huellaNew === null || !$this->sonImagenesSimilares($huellaNew, $huellaFoto1, 12)) {
                unset($body);
                return;
            }

            $fileName = 'IMGPRO' . $producto->idProducto . '_1.webp';
            $fullDest = $destinationPath . '/' . $fileName;

            $image = $manager->read($body);
            $encoded = (string) $image->toWebp(80);
            unset($image);

            if (@file_put_contents($fullDest, $encoded) !== false) {
                $this->sincronizarCopiaStorage($fullDest, $fileName, $destinationPath);
                $hashesVistos[md5($body)] = true;
                $hashesVistos[md5($encoded)] = true;
                $huellasVisuales[] = $huellaNew;
                $updates['imagenProducto1'] = 'productos/' . $fileName . '?v=' . $timestamp;
                $slotsLlenados[] = 1;

                if ($portadaCarrusel === null && ($urlsSecundarias[0] ?? null) === $candidataUpgrade1) {
                    array_shift($urlsSecundarias);
                }
            }

            unset($body, $encoded);
        } catch (\Throwable $e) {
            // Continuar con los slots secundarios si falla la mejora de Foto 1
        }
    }

    protected function sincronizarCopiaStorage(string $fullDest, string $fileName, string $destinationPath): void
    {
        foreach ([public_path('storage/productos'), storage_path('app/public/productos')] as $extra) {
            if (is_dir($extra) && realpath($destinationPath) !== realpath($extra)) {
                @copy($fullDest, $extra . '/' . $fileName);
            }
        }
    }
}
