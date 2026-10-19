<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Deltron\DeltronApiService;
use App\Models\Inventario\InventarioProveedorDetalle;
use Exception;

class SyncDeltronApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deltron:sync {--tipo=inicio-dia : Tipo de sincronización (inicio-dia o catalogo)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza stock y precios en tiempo real desde la API B2B oficial de Deltron hacia Inventario_Proveedor';

    /**
     * Execute the console command.
     */
    public function handle(DeltronApiService $deltronService)
    {
        set_time_limit(0);
        $tipo = $this->option('tipo') ?: 'inicio-dia';

        $this->info("==================================================");
        $this->info(" Iniciando Sincronización oficial Deltron B2B API ");
        $this->info(" Modo: " . strtoupper($tipo));
        $this->info("==================================================");

        try {
            $this->info("Obteniendo datos desde Deltron...");

            $items = [];
            $modoEjecutado = $tipo;

            if ($tipo === 'catalogo') {
                $items = $deltronService->getCatalogo(300);
            } else {
                // Modo inicio-dia: corte matutino con precio, precio de promoción y stock
                // Se configura un timeout amplio (240s) y se evitan reintentos inmediatos si falla
                $items = $deltronService->getInicioDia(240);
            }

            $totalEncontrados = count($items);
            $this->info("Total productos recibidos desde Deltron: {$totalEncontrados}");

            if ($totalEncontrados === 0) {
                $this->warn("No se recibieron productos de Deltron. Verifica el estado del servicio.");
                return 0;
            }

            $this->info("Procesando y sincronizando con base de datos local por part_number...");
            $syncResult = $deltronService->syncInventario($items);

            $updatedCount = $syncResult['count'];
            $detalles = $syncResult['detalles'];
            $recomendados = $syncResult['recomendados'] ?? [];

            // Registrar log de auditoría
            InventarioProveedorDetalle::create([
                'keyword' => 'API_' . strtoupper($modoEjecutado),
                'productos_encontrados' => $totalEncontrados,
                'productos_actualizados' => $updatedCount,
                'fecha_ejecucion' => now(),
                'detalles_json' => $detalles,
                'recomendados_json' => $recomendados
            ]);

            // Sincronizar también la lista de promociones vigentes de Deltron hacia la base de datos local
            $this->info("Actualizando promociones vigentes de Deltron en base de datos local...");
            $promosSync = $deltronService->syncPromociones();

            $this->info("--------------------------------------------------");
            $this->info("[OK] Sincronización completada exitosamente.");
            $this->info("[OK] Productos actualizados en Inventario_Proveedor: {$updatedCount}");
            $this->info("[OK] Productos recomendados con alto stock: " . count($recomendados));
            $this->info("[OK] Promociones vigentes sincronizadas en BD: " . count($promosSync));
            $this->info("--------------------------------------------------");

            return 0;

        } catch (Exception $e) {
            $this->error("Error durante la sincronización con Deltron: " . $e->getMessage());
            \Log::error("Deltron Sync Command Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
