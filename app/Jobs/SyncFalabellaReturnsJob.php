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

class SyncFalabellaReturnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    protected $dateFrom;
    protected $dateTo;

    /**
     * Create a new job instance.
     */
    public function __construct(string $dateFrom, string $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    /**
     * Execute the job.
     */
    public function handle(FalabellaOrderSyncService $falabellaOrderSyncService): void
    {
        try {
            \Illuminate\Support\Facades\Cache::put('falabella_sync_returns_status', 'processing', 300);
            Log::info("Iniciando sincronizacion de devoluciones de Falabella en background ({$this->dateFrom} a {$this->dateTo})");
            $result = $falabellaOrderSyncService->syncReturnsByDateRange($this->dateFrom, $this->dateTo);
            Log::info("Sincronizacion de devoluciones completada en background: " . json_encode($result));
        } catch (Throwable $e) {
            Log::error("Error en SyncFalabellaReturnsJob: " . $e->getMessage());
            throw $e;
        } finally {
            \Illuminate\Support\Facades\Cache::put('falabella_sync_returns_status', 'completed', 300);
        }
    }
}
