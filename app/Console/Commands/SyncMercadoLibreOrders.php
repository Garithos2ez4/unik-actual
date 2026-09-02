<?php

namespace App\Console\Commands;

use App\Services\MercadoLibreOrderSyncService;
use Illuminate\Console\Command;

class SyncMercadoLibreOrders extends Command
{
    protected $signature = 'ml:sync-orders
                            {--seller= : ID del seller específico (opcional)}
                            {--from= : Fecha desde la que sincronizar (YYYY-MM-DD)}';

    protected $description = 'Sincroniza órdenes de Mercado Libre (shipment, logística, devoluciones)';

    public function handle(MercadoLibreOrderSyncService $syncService)
    {
        $sellerId = $this->option('seller');
        $from     = $this->option('from');

        if ($from) {
            $from = $from . 'T00:00:00.000-05:00';
        }

        $this->info('Iniciando sincronización de Mercado Libre...');

        try {
            $count = $sellerId
                ? $syncService->syncBySeller($sellerId, $from)
                : $syncService->syncAll($from);

            $this->info("Sincronización completada: {$count} órdenes actualizadas.");
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('ML Sync Command Error: ' . $e->getMessage());
        }
    }
}
