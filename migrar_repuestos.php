<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Producto;
use App\Models\GrupoProducto;
use Illuminate\Support\Facades\DB;

$mapeo = [
    46 => 120, // Almohadillas -> Almohadillas de Impresora
    47 => 126, // Caja de mantenimiento -> Caja de Mantenimiento de Impresora
    45 => 127, // Cartucho de mantenimiento -> Cartucho de Mantenimiento de Impresora
    48 => 129, // Chip de mantenimiento -> Chip de Caja de Mantenimiento
    88 => 152  // Otros suministros -> OTHERS
];

$totalActualizados = 0;

DB::beginTransaction();
try {
    foreach ($mapeo as $oldId => $newId) {
        // Obtenemos los grupos para los logs
        $oldGroup = GrupoProducto::find($oldId);
        $newGroup = GrupoProducto::find($newId);
        
        $oldName = $oldGroup ? $oldGroup->nombreGrupo : "Grupo $oldId";
        $newName = $newGroup ? $newGroup->nombreGrupo : "Grupo $newId";
        
        // Actualizamos todos los productos con el idGrupo antiguo
        $afectados = Producto::where('idGrupo', $oldId)->update(['idGrupo' => $newId]);
        
        echo "De '$oldName' a '$newName' -> $afectados productos actualizados.\n";
        $totalActualizados += $afectados;
    }
    
    DB::commit();
    echo "\nMigración completada exitosamente. Total de productos movidos: $totalActualizados\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Ocurrió un error en la migración: " . $e->getMessage() . "\n";
}
