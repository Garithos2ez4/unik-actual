<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Catalogo\Producto;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Inventario;
use App\Models\Inventario\RegistroProducto;

class SincronizarStockSeries extends Command
{
    protected $signature = 'inventario:sincronizar-series {modelo? : Modelo o SKU específico a revisar} {--apply : Aplicar los cambios en la tabla Inventario}';

    protected $description = 'Detecta y sincroniza desfases entre Inventario.stock y las series físicas activas en RegistroProducto';

    public function handle()
    {
        $modelo = $this->argument('modelo');
        $apply = (bool) $this->option('apply');

        $query = Producto::where('es_padre', false);
        if ($modelo) {
            $query->where(function ($q) use ($modelo) {
                $q->where('modelo', $modelo)
                  ->orWhere('codigoProducto', $modelo);
            });
        }

        $productos = $query->get();
        $almacenes = Almacen::all()->keyBy('idAlmacen');
        $estadosExcluidos = ['ENTREGADO', 'INVALIDO', 'ELIMINADO', 'GARANTIA', 'DEFECTUOSO', 'ABIERTO', 'DIVIDIDO', 'REUNIDO'];

        $desfases = 0;

        foreach ($productos as $prod) {
            $tieneSeries = RegistroProducto::whereHas('DetalleComprobante', function ($q) use ($prod) {
                $q->where('idProducto', $prod->idProducto);
            })->exists();

            if (!$tieneSeries) {
                continue;
            }

            $conteoPorAlmacen = RegistroProducto::whereHas('DetalleComprobante', function ($q) use ($prod) {
                $q->where('idProducto', $prod->idProducto);
            })
                ->whereNotIn('estado', $estadosExcluidos)
                ->selectRaw('idAlmacen, COUNT(*) as total')
                ->groupBy('idAlmacen')
                ->pluck('total', 'idAlmacen');

            $inventarios = Inventario::where('idProducto', $prod->idProducto)->get()->keyBy('idAlmacen');

            foreach ($almacenes as $idAlmacen => $alm) {
                $stockTabla = (int) ($inventarios->get($idAlmacen)?->stock ?? 0);
                $stockSeries = (int) ($conteoPorAlmacen->get($idAlmacen) ?? 0);

                if ($stockTabla !== $stockSeries) {
                    $desfases++;
                    $this->warn("[{$prod->modelo}] Almacén {$alm->descripcion} (ID {$idAlmacen}): Inventario.stock = {$stockTabla} | Series reales = {$stockSeries}");

                    if ($apply) {
                        $inv = $inventarios->get($idAlmacen);
                        if ($inv) {
                            $inv->stock = $stockSeries;
                            $inv->save();
                        } else {
                            Inventario::create([
                                'idProducto' => $prod->idProducto,
                                'idAlmacen' => $idAlmacen,
                                'stock' => $stockSeries,
                            ]);
                        }
                    }
                }
            }
        }

        if ($desfases === 0) {
            $this->info('No se encontraron desfases entre Inventario y RegistroProducto.');
        } elseif (!$apply) {
            $this->line('');
            $this->info("Se encontraron {$desfases} desfase(s). Ejecuta con --apply para corregir la tabla Inventario.");
        } else {
            $this->info("Se sincronizaron {$desfases} registro(s) correctamente.");
        }

        return 0;
    }
}
