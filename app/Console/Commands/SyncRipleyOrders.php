<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RipleyOrderSyncService;

class SyncRipleyOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ripley:sync-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza pedidos pendientes desde Ripley Mirakl';

    /**
     * Execute the console command.
     */
    public function handle(RipleyOrderSyncService $syncService)
    {
        $this->info('Iniciando sincronización de Ripley...');
        try {
            $count = $syncService->syncPendingOrders();
            $this->info("Sincronización completada. $count órdenes actualizadas.");
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'cURL error 6') === false && strpos($msg, 'cURL error 28') === false) {
                $this->error("Error en sincronización Ripley: " . $msg);
                \Illuminate\Support\Facades\Log::error("Ripley Sync Error: " . $msg);
            }
        }
    }
}
