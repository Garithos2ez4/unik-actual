<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalculadoraServiceInterface;

class UpdateDailyExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:update-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el tipo de cambio diario y lo guarda en el historial.';

    /**
     * Execute the console command.
     */
    public function handle(CalculadoraServiceInterface $calculadoraService)
    {
        $this->info('Actualizando tipo de cambio...');
        $calculadoraService->obtenerCambioDolar();
        $this->info('Tipo de cambio actualizado y guardado en el historial.');
    }
}
