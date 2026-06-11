<?php

namespace App\Jobs;

use App\Services\FalabellaOrderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncFalabellaOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    protected $date;
    protected $status;

    /**
     * Create a new job instance.
     */
    public function __construct(string $date, string $status = '')
    {
        $this->date = $date;
        $this->status = $status;
    }

    /**
     * Execute the job.
     */
    public function handle(FalabellaOrderSyncService $falabellaOrderSyncService): void
    {
        try {
            \Illuminate\Support\Facades\Cache::put('falabella_sync_orders_status', 'processing', 300);
            Log::info("Iniciando sincronizacion de ordenes de Falabella en background para la fecha: {$this->date}");
            $result = $falabellaOrderSyncService->syncDispatchQueue($this->date, $this->status ?: null);
            Log::info("Sincronizacion completada en background: " . json_encode($result));
        } catch (Throwable $e) {
            Log::error("Error en SyncFalabellaOrdersJob: " . $e->getMessage());
            throw $e;
        } finally {
            \Illuminate\Support\Facades\Cache::put('falabella_sync_orders_status', 'completed', 300);
        }
    }
}
