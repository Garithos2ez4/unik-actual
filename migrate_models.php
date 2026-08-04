<?php

/**
 * ============================================================================
 *  MIGRATE MODELS — Script de Reestructuración de Modelos Laravel
 * ============================================================================
 *  Transforma la carpeta app/Models de una estructura plana a una organización
 *  por dominios de negocio. Seguro para ejecutar en producción.
 *
 *  Fases:
 *    1. Backup automático del estado actual
 *    2. Mover archivos a subcarpetas y actualizar su namespace
 *    3. Corregir todos los `use App\Models\X` en el proyecto
 *    4. Añadir imports cruzados entre dominios en los propios modelos
 *    5. Limpiar caché de Laravel
 *
 *  Uso:
 *    php migrate_models.php              — Modo real (ejecuta cambios)
 *    php migrate_models.php --dry-run   — Simula sin modificar archivos
 *    php migrate_models.php --rollback  — Restaura el backup
 *
 *  Seguridad:
 *    - Es idempotente: se puede re-ejecutar sin causar daño doble.
 *    - Crea un backup ZIP antes de cualquier cambio.
 *    - Verifica cada archivo antes de moverlo.
 * ============================================================================
 */

declare(strict_types=1);

// ─── Configuración ───────────────────────────────────────────────────────────

const BASE_DIR   = __DIR__;
const MODELS_DIR = BASE_DIR . '/app/Models';
const BACKUP_DIR = BASE_DIR . '/storage/model_migration_backup';

const SCAN_DIRS = [
    BASE_DIR . '/app',
    BASE_DIR . '/routes',
    BASE_DIR . '/resources/views',
    BASE_DIR . '/database',
    BASE_DIR . '/config',
];

// Mapa: nombre de clase original => subcarpeta de destino
// registroUpdate se renombra a RegistroUpdate (corrección PSR-4)
const MIGRATIONS = [
    // Falabella
    'FalabellaOrder'              => 'Falabella',
    'FalabellaOrderItem'          => 'Falabella',
    'AlertaPrecio'                => 'Falabella',
    'ProductoVigiladoFalabella'   => 'Falabella',
    // Reclamos
    'ReclamoPlataforma'           => 'Reclamos',
    'TipoReclamoPlataforma'       => 'Reclamos',
    'SeguimientoReclamo'          => 'Reclamos',
    'DiagnosticoReclamo'          => 'Reclamos',
    // Licencias
    'Licencia'                    => 'Licencias',
    'LicenciaUsada'               => 'Licencias',
    'LicenciaRecuperada'          => 'Licencias',
    'LicenciaDefectuosa'          => 'Licencias',
    'CategoriaLicencia'           => 'Licencias',
    'TipoLicencia'                => 'Licencias',
    // Envios
    'EnvioProvincia'              => 'Envios',
    'EnvioProvinciaDetalle'       => 'Envios',
    'EnvioProvinciaProducto'      => 'Envios',
    'EnvioProvinciaReceptor'      => 'Envios',
    'EnvioDimension'              => 'Envios',
    'SolicitudEnvio'              => 'Envios',
    'EvidenciaSeguimiento'        => 'Envios',
    'Agencia'                     => 'Envios',
    'SubAgencia'                  => 'Envios',
    'Destino'                     => 'Envios',
    'TipoPaqueteEnvio'            => 'Envios',
    'Departamento'                => 'Envios',
    'Provincia'                   => 'Envios',
    // Precios
    'Calculadora'                 => 'Precios',
    'Comision'                    => 'Precios',
    'ComisionPlataforma'          => 'Precios',
    'PrecioTienda'                => 'Precios',
    'HistorialPrecioTienda'       => 'Precios',
    'HistorialTipoCambio'         => 'Precios',
    'RangoPrecio'                 => 'Precios',
    'registroUpdate'              => 'Precios',  // ← se renombra a RegistroUpdate
    // Empresa
    'Empresa'                     => 'Empresa',
    'EmpresaRedSocial'            => 'Empresa',
    'RedSocial'                   => 'Empresa',
    'Plataforma'                  => 'Empresa',
    'CuentasPlataforma'           => 'Empresa',
    'CuentasTransferencia'        => 'Empresa',
    'Banco'                       => 'Empresa',
    'Preveedor'                   => 'Empresa',  // typo intencional (así se llama la tabla)
    'Publicidad'                  => 'Empresa',
    // Usuarios
    'User'                        => 'Usuarios',
    'Usuario'                     => 'Usuarios',
    'Accesos'                     => 'Usuarios',
    'Vista'                       => 'Usuarios',
    'Cliente'                     => 'Usuarios',
    'TipoDocumento'               => 'Usuarios',
    // Ventas
    'Venta'                       => 'Ventas',
    'DetalleVenta'                => 'Ventas',
    'Comprobante'                 => 'Ventas',
    'DetalleComprobante'          => 'Ventas',
    'TipoComprobante'             => 'Ventas',
    'PagoVenta'                   => 'Ventas',
    'MetodoPago'                  => 'Ventas',
    'TipoMetodoPago'              => 'Ventas',
    'Devolucion'                  => 'Ventas',
    'Garantia'                    => 'Ventas',
    'Transaccion'                 => 'Ventas',
    // Inventario
    'Inventario'                  => 'Inventario',
    'Inventario_Proveedor'        => 'Inventario',
    'IngresoProducto'             => 'Inventario',
    'EgresoProducto'              => 'Inventario',
    'RegistroProducto'            => 'Inventario',
    'Almacen'                     => 'Inventario',
    'UbicacionAlmacen'            => 'Inventario',
    'UbicacionEstante'            => 'Inventario',
    'DivisionPack'                => 'Inventario',
    'DivisionPackDetalle'         => 'Inventario',
    'Liquidacion'                 => 'Inventario',
    // Catalogo (el más referenciado, va al final)
    'Producto'                    => 'Catalogo',
    'DetalleProducto'             => 'Catalogo',
    'MarcaProducto'               => 'Catalogo',
    'GrupoProducto'               => 'Catalogo',
    'CategoriaProducto'           => 'Catalogo',
    'TipoProducto'                => 'Catalogo',
    'Caracteristicas'             => 'Catalogo',
    'Caracteristicas_Grupo'       => 'Catalogo',
    'Caracteristicas_Producto'    => 'Catalogo',
    'Caracteristicas_Sugerencias' => 'Catalogo',
    'Publicacion'                 => 'Catalogo',
    'ProductoPack'                => 'Catalogo',
    'Review'                      => 'Catalogo',
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

function out(string $msg, string $type = 'info'): void
{
    $colors = ['info' => "\033[0m", 'ok' => "\033[32m", 'warn' => "\033[33m", 'error' => "\033[31m", 'head' => "\033[36m"];
    $prefix = ['info' => '   ', 'ok' => ' ✅ ', 'warn' => ' ⚠️  ', 'error' => ' ❌ ', 'head' => ' ══ '];
    echo ($colors[$type] ?? '') . ($prefix[$type] ?? '   ') . $msg . "\033[0m\n";
}

function getPhpFiles(array $dirs): array
{
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $files[] = $f->getPathname();
            }
        }
    }
    return $files;
}

/** Devuelve el nombre de clase canónico (ej. registroUpdate → RegistroUpdate) */
function canonicalClass(string $originalName): string
{
    return $originalName === 'registroUpdate' ? 'RegistroUpdate' : $originalName;
}

/** Construye el mapa clase → FQCN completo */
function buildFqcnMap(): array
{
    $map = [];
    foreach (MIGRATIONS as $original => $folder) {
        $canonical = canonicalClass($original);
        $map[$canonical] = "App\\Models\\{$folder}\\{$canonical}";
        // también indexar por nombre original por si queda alguna referencia vieja
        if ($original !== $canonical) {
            $map[$original] = "App\\Models\\{$folder}\\{$canonical}";
        }
    }
    return $map;
}

// ─── Backup ──────────────────────────────────────────────────────────────────

function createBackup(bool $dryRun): ?string
{
    $timestamp = date('Ymd_His');
    $backupPath = BACKUP_DIR . "/models_backup_{$timestamp}";

    if ($dryRun) {
        out("(dry-run) Se crearía backup en: {$backupPath}", 'warn');
        return null;
    }

    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }

    // Copiar recursivamente app/Models y todos los archivos a escanear
    $zipFile = $backupPath . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        out("No se pudo crear el backup ZIP. Abortando.", 'error');
        exit(1);
    }

    $filesToBackup = getPhpFiles(array_merge([MODELS_DIR], SCAN_DIRS));
    foreach ($filesToBackup as $file) {
        $zip->addFile($file, str_replace(BASE_DIR . DIRECTORY_SEPARATOR, '', $file));
    }
    $zip->close();

    out("Backup creado: {$zipFile}", 'ok');
    return $zipFile;
}

// ─── Rollback ────────────────────────────────────────────────────────────────

function rollback(): void
{
    out("Buscando backup más reciente...", 'head');
    if (!is_dir(BACKUP_DIR)) {
        out("No existe directorio de backups.", 'error');
        exit(1);
    }

    $backups = glob(BACKUP_DIR . '/*.zip');
    if (empty($backups)) {
        out("No se encontraron backups.", 'error');
        exit(1);
    }

    // Ordenar por fecha (más reciente primero)
    usort($backups, fn($a, $b) => filemtime($b) <=> filemtime($a));
    $latest = $backups[0];

    out("Restaurando desde: " . basename($latest), 'warn');

    $zip = new ZipArchive();
    if ($zip->open($latest) !== true) {
        out("No se pudo abrir el backup.", 'error');
        exit(1);
    }

    $zip->extractTo(BASE_DIR);
    $zip->close();

    out("Rollback completado. Ejecuta: php artisan optimize:clear", 'ok');
}

// ─── Fase 1: Mover modelos y actualizar namespace interno ─────────────────────

function phase1_moveModels(array &$allFiles, bool $dryRun): void
{
    out("FASE 1: Moviendo modelos a subcarpetas", 'head');
    $moved = 0;
    $skipped = 0;

    foreach (MIGRATIONS as $original => $folder) {
        $canonical  = canonicalClass($original);
        $oldPath    = MODELS_DIR . "/{$original}.php";
        $newDir     = MODELS_DIR . "/{$folder}";
        $newPath    = "{$newDir}/{$canonical}.php";
        $newNs      = "App\\Models\\{$folder}";

        // Si ya fue movido previamente, saltar (idempotente)
        if (!file_exists($oldPath)) {
            if (file_exists($newPath)) {
                $skipped++;
                continue; // ya estaba en su lugar
            }
            out("No encontrado: {$original}.php (saltando)", 'warn');
            continue;
        }

        $content = file_get_contents($oldPath);

        // 1a. Actualizar namespace del archivo
        $content = preg_replace(
            '/^namespace\s+App\\\\Models\s*;/m',
            "namespace {$newNs};",
            $content
        );

        // 1b. Si el nombre de clase cambió (ej. registroUpdate → RegistroUpdate)
        if ($original !== $canonical) {
            $content = preg_replace(
                "/\bclass\s+{$original}\b/",
                "class {$canonical}",
                $content
            );
        }

        if (!$dryRun) {
            if (!is_dir($newDir)) {
                mkdir($newDir, 0755, true);
            }
            file_put_contents($newPath, $content);
            unlink($oldPath);
        }

        out("{$original} → Models/{$folder}/{$canonical}.php", 'ok');
        $moved++;
    }

    // Actualizar la lista de archivos para las fases siguientes
    if (!$dryRun) {
        $allFiles = getPhpFiles(SCAN_DIRS);
    }

    out("Movidos: {$moved} | Ya migrados: {$skipped}", 'info');
}

// ─── Fase 2: Actualizar referencias `use` y referencias inline ────────────────

function phase2_updateReferences(array $allFiles, bool $dryRun): void
{
    out("FASE 2: Actualizando referencias en todo el proyecto", 'head');
    $updated = 0;

    foreach (MIGRATIONS as $original => $folder) {
        $canonical = canonicalClass($original);
        $newFqcn   = "App\\Models\\{$folder}\\{$canonical}";

        foreach ($allFiles as $file) {
            // Saltar vendor y node_modules
            if (strpos($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
            if (strpos($file, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR) !== false) continue;

            $content  = file_get_contents($file);
            $original_content = $content;

            // ── use App\Models\Clase; ───────────────────────────────────────
            $content = preg_replace(
                '/\buse\s+App\\\\Models\\\\' . preg_quote($original, '/') . '\s*;/',
                "use {$newFqcn};",
                $content
            );
            // Si el nombre fue normalizado (registroUpdate → RegistroUpdate)
            if ($original !== $canonical) {
                $content = preg_replace(
                    '/\buse\s+App\\\\Models\\\\' . preg_quote($canonical, '/') . '\s*;/',
                    "use {$newFqcn};",
                    $content
                );
            }

            // ── \App\Models\Clase (inline) ──────────────────────────────────
            $content = preg_replace(
                '/(?<![A-Za-z])App\\\\Models\\\\' . preg_quote($original, '/') . '(?![A-Za-z\\\\])/',
                str_replace('\\', '\\\\', $newFqcn),
                $content
            );
            if ($original !== $canonical) {
                $content = preg_replace(
                    '/(?<![A-Za-z])App\\\\Models\\\\' . preg_quote($canonical, '/') . '(?![A-Za-z\\\\])/',
                    str_replace('\\', '\\\\', $newFqcn),
                    $content
                );
            }

            // ── 'App\Models\Clase' (strings en relaciones polimorficas) ─────
            foreach ([$original, $canonical] as $name) {
                $escaped = addslashes("App\\Models\\{$name}");
                $replacement = addslashes($newFqcn);
                $content = str_replace("'{$escaped}'", "'{$replacement}'", $content);
                $content = str_replace("\"{$escaped}\"", "\"{$replacement}\"", $content);
            }

            if ($content !== $original_content) {
                if (!$dryRun) {
                    file_put_contents($file, $content);
                }
                $updated++;
            }
        }
    }

    out("Archivos con referencias actualizadas: {$updated}", 'ok');
}

// ─── Fase 3: Añadir imports cruzados faltantes en los modelos ─────────────────

function phase3_addCrossDomainImports(bool $dryRun): void
{
    out("FASE 3: Añadiendo imports cruzados en relaciones de modelos", 'head');
    $fqcnMap = buildFqcnMap();
    $fixed   = 0;

    $modelFiles = getPhpFiles([MODELS_DIR]);

    foreach ($modelFiles as $file) {
        $content = file_get_contents($file);

        // Detectar namespace del archivo
        if (!preg_match('/^namespace\s+(App\\\\Models\\\\[A-Za-z_]+)\s*;/m', $content, $nsMatch)) {
            continue;
        }
        $fileNamespace = $nsMatch[1]; // ej: App\Models\Catalogo

        // Obtener 'use' ya declarados
        preg_match_all('/^use\s+(App\\\\Models\\\\[A-Za-z_\\\\]+)\s*;/m', $content, $useMatches);
        $existingUses = $useMatches[1] ?? [];

        // Detectar clases usadas con ::class en el archivo
        preg_match_all('/\b([A-Z][A-Za-z_]+)::class\b/', $content, $classRefs);
        $referencedClasses = array_unique($classRefs[1]);

        $usesToAdd = [];

        foreach ($referencedClasses as $className) {
            if (!isset($fqcnMap[$className])) continue;

            $fullFqcn        = $fqcnMap[$className];
            $classNamespace  = preg_replace('/\\\\[^\\\\]+$/', '', $fullFqcn);

            // Mismo namespace = no necesita import
            if ($classNamespace === $fileNamespace) continue;

            // Ya tiene el import correcto
            if (in_array($fullFqcn, $existingUses, true)) continue;

            $usesToAdd[$className] = "use {$fullFqcn};";
        }

        if (empty($usesToAdd)) continue;

        // Insertar los imports justo después del namespace declaration
        $useBlock = implode("\n", array_values($usesToAdd));
        $content  = preg_replace(
            '/^(namespace\s+App\\\\Models\\\\[A-Za-z_]+\s*;)/m',
            '$1' . "\n" . $useBlock,
            $content,
            1
        );

        if (!$dryRun) {
            file_put_contents($file, $content);
        }

        out(basename($file) . ' → ' . implode(', ', array_keys($usesToAdd)), 'ok');
        $fixed++;
    }

    out("Modelos con imports cruzados añadidos: {$fixed}", 'ok');
}

// ─── Fase 4: Sanear doble namespace por si el script se ejecutó parcialmente ──

function phase4_fixDoubleNamespace(array $allFiles, bool $dryRun): void
{
    out("FASE 4: Verificando y corrigiendo doble namespace", 'head');
    $dominios  = array_unique(array_values(MIGRATIONS));
    $corrected = 0;

    foreach ($allFiles as $file) {
        if (strpos($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;

        $content = file_get_contents($file);
        $original = $content;

        foreach ($dominios as $dominio) {
            // Patrón: App\Models\X\X\Clase → App\Models\X\Clase
            $pattern     = '/App\\\\Models\\\\' . $dominio . '\\\\' . $dominio . '\\\\([A-Za-z_]+)/';
            $replacement = "App\\Models\\{$dominio}\\\\$1";
            $content     = preg_replace($pattern, $replacement, $content);
        }

        if ($content !== $original) {
            if (!$dryRun) {
                file_put_contents($file, $content);
            }
            $corrected++;
            out("Doble namespace corregido en: " . basename($file), 'warn');
        }
    }

    if ($corrected === 0) {
        out("Sin dobles namespaces detectados. Todo limpio.", 'ok');
    } else {
        out("Archivos corregidos: {$corrected}", 'ok');
    }
}

// ─── Fase 5: Limpiar caché de Laravel ─────────────────────────────────────────

function phase5_clearCache(bool $dryRun): void
{
    out("FASE 5: Limpiando caché de Laravel", 'head');
    if ($dryRun) {
        out("(dry-run) Se ejecutaría: php artisan optimize:clear", 'warn');
        return;
    }
    $output = shell_exec('php ' . BASE_DIR . '/artisan optimize:clear 2>&1');
    out(trim($output ?? 'OK'), 'ok');
}

// ─── Punto de entrada ─────────────────────────────────────────────────────────

$args    = array_slice($argv, 1);
$dryRun  = in_array('--dry-run', $args, true);
$doRollback = in_array('--rollback', $args, true);

echo "\n";
out("════════════════════════════════════════════", 'head');
out("  MIGRATE MODELS — Reestructuración Laravel  ", 'head');
out("════════════════════════════════════════════", 'head');
echo "\n";

if ($doRollback) {
    rollback();
    exit(0);
}

if ($dryRun) {
    out("MODO DRY-RUN activado — No se modificará ningún archivo.", 'warn');
} else {
    out("MODO REAL — Se realizarán cambios en disco.", 'warn');
}
echo "\n";

// Backup
$backupPath = createBackup($dryRun);
echo "\n";

// Cargar todos los archivos a escanear
$allFiles = getPhpFiles(SCAN_DIRS);

// Ejecutar fases
phase1_moveModels($allFiles, $dryRun);
echo "\n";

// Re-escanear después de mover para que las fases 2-4 vean los archivos nuevos
if (!$dryRun) {
    $allFiles = getPhpFiles(SCAN_DIRS);
}

phase2_updateReferences($allFiles, $dryRun);
echo "\n";

phase3_addCrossDomainImports($dryRun);
echo "\n";

phase4_fixDoubleNamespace($allFiles, $dryRun);
echo "\n";

phase5_clearCache($dryRun);
echo "\n";

out("════════════════════════════════════════════", 'head');
if ($dryRun) {
    out("  DRY-RUN completado. Revisa los cambios.", 'head');
    out("  Para aplicar: php migrate_models.php", 'head');
} else {
    out("  ¡Migración completada exitosamente! ✅", 'head');
    if ($backupPath) {
        out("  Backup guardado en: {$backupPath}", 'head');
    }
    out("  Para revertir: php migrate_models.php --rollback", 'head');
}
out("════════════════════════════════════════════", 'head');
echo "\n";
