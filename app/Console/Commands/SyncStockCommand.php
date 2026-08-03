<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\RegistroProducto;
use App\Models\Inventario\Inventario;
use App\Models\Inventario\Almacen;
use App\Models\Ventas\DetalleComprobante;
use Illuminate\Support\Facades\DB;

class SyncStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:stock {--producto= : El ID del producto específico a sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza y recalcula el stock en la tabla Inventario basado en los registros de series físicos activos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronización de stock de inventario...");

        $idProducto = $this->option('producto');

        $query = Producto::query()
            ->where(function ($q) {
                $q->where('estadoProductoWeb', '!=', 'DESCONTINUADO')
                    ->orWhereNull('estadoProductoWeb');
            });

        if ($idProducto) {
            $query->where('idProducto', $idProducto);
        }

        $productos = $query->get();
        $almacenes = Almacen::all();
        $totalActualizados = 0;

        $this->output->progressStart(count($productos));

        foreach ($productos as $p) {
            $detalles = DetalleComprobante::where('idProducto', $p->idProducto)->pluck('idDetalleComprobante');

            foreach ($almacenes as $alm) {
                // Contar las series en estado NUEVO, ABIERTO o DEVOLUCION
                $count = RegistroProducto::whereIn('idDetalleComprobante', $detalles)
                    ->where('idAlmacen', $alm->idAlmacen)
                    ->whereIn('estado', ['NUEVO', 'ABIERTO', 'DEVOLUCION'])
                    ->count();

                $inv = Inventario::where('idProducto', $p->idProducto)->where('idAlmacen', $alm->idAlmacen)->first();

                if ($inv) {
                    if ($inv->stock != $count) {
                        $this->line("Corrigiendo stock de Producto: {$p->modelo} (Almacén {$alm->idAlmacen}) | De {$inv->stock} a {$count}");
                        $inv->stock = $count;
                        $inv->save();
                        $totalActualizados++;
                    }
                } else {
                    if ($count > 0) {
                        $this->line("Creando stock de Producto: {$p->modelo} (Almacén {$alm->idAlmacen}) | Stock: {$count}");
                        Inventario::create([
                            'idProducto' => $p->idProducto,
                            'idAlmacen' => $alm->idAlmacen,
                            'stock' => $count
                        ]);
                        $totalActualizados++;
                    }
                }
            }
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("Sincronización completada. Total de registros de inventario actualizados/creados: {$totalActualizados}");
    }
}
