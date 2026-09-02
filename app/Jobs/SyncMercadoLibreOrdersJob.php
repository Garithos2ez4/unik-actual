<?php

namespace App\Jobs;

use App\Services\MercadoLibreOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncMercadoLibreOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        protected ?string $sellerId = null,
        protected ?string $fromDate = null
    ) {}

    public function handle(MercadoLibreOrderSyncService $syncService): void
    {
        try {
            Cache::put('ml_sync_orders_status', 'processing', 300);
            Log::info('Iniciando sync ML' . ($this->sellerId ? " seller={$this->sellerId}" : ' (todos los sellers)'));

            $count = $this->sellerId
                ? $syncService->syncBySeller($this->sellerId, $this->fromDate)
                : $syncService->syncAll($this->fromDate);

            Log::info("ML Sync completado: {$count} órdenes sincronizadas.");
        } catch (Throwable $e) {
            Log::error('Error en SyncMercadoLibreOrdersJob: ' . $e->getMessage());
            throw $e;
        } finally {
            Cache::put('ml_sync_orders_status', 'completed', 300);
        }
    }
}
