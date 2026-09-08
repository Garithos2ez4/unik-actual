<?php

namespace App\Console\Commands;

use App\Models\Ecommerce\MercadoLibreCredential;
use App\Models\Ventas\Venta;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Services\MercadoLibreApiService;
use App\Services\MercadoLibreOrderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillMercadoLibreOrders extends Command
{
    protected $signature = 'ml:backfill-missing';
    protected $description = 'Llena la tabla ml_orders con órdenes históricas que están en Venta pero no en ml_orders';

    public function handle(MercadoLibreApiService $api, MercadoLibreOrderSyncService $sync)
    {
        $this->info('Buscando credenciales de Mercado Libre...');
        $credentials = MercadoLibreCredential::all();
        if ($credentials->isEmpty()) {
            $this->error('No hay cuentas de Mercado Libre configuradas.');
            return;
        }

        $this->info("Buscando órdenes históricas faltantes para todas las cuentas...");
        
        $numerosEnVenta = Venta::whereRaw("(UPPER(canal) = 'MERCADO LIBRE' OR UPPER(canal) = 'MERCADOLIBRE')")
            ->pluck('numeroOrden')
            ->filter()
            ->unique()
            ->toArray();
            
        $numerosEnMlOrders = MercadoLibreOrder::pluck('ml_order_id')->toArray();
        
        $faltantes = array_diff($numerosEnVenta, $numerosEnMlOrders);
        
        if (empty($faltantes)) {
            $this->info('No hay órdenes históricas faltantes por sincronizar.');
            return;
        }
        
        $this->info("Se encontraron " . count($faltantes) . " órdenes faltantes. Iniciando sincronización...");
        
        $bar = $this->output->createProgressBar(count($faltantes));
        $bar->start();
        
        $success = 0;
        $failed = 0;
        
        foreach ($faltantes as $orderId) {
            $sincronizado = false;
            foreach ($credentials as $credential) {
                try {
                    $sellerId = $credential->seller_id;
                    $orderPayload = $api->getOrder($sellerId, $orderId);
                    
                    if (!isset($orderPayload['error']) && !empty($orderPayload['id'])) {
                        $sync->syncSingleOrder($sellerId, $orderPayload);
                        $success++;
                        $sincronizado = true;
                        break;
                    }
                } catch (\Exception $e) {
                    // Ignoramos el error, probamos la siguiente credencial
                }
            }
            
            if (!$sincronizado) {
                $failed++;
                Log::warning("No se pudo backfillear la orden {$orderId} con ninguna de las cuentas.");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Backfill completado. Exitosos: {$success}, Fallidos: {$failed}");
    }
}
