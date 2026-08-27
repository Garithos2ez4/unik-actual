<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BotFalabellaPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:falabella-prices';
    protected $description = 'Verifica precios en Falabella y genera alertas';

    public function handle(\App\Services\FalabellaScraperService $scraperService)
    {
        $this->info("Iniciando Bot de Precios Falabella...");

        // 1. Obtener productos manuales
        $manuales = \App\Models\Ecommerce\ProductoVigiladoFalabella::where('activo', true)->pluck('modelo')->toArray();
        // Convertimos $manuales a formato detallado
        $modelosAEvaluar = [];
        foreach($manuales as $m) {
            $prod = \App\Models\Catalogo\Producto::with(['GrupoProducto', 'MarcaProducto'])
                ->where('modelo', $m)
                ->orWhere('modelo', 'LIKE', '%' . $m . '%')
                ->first();
            $modelosAEvaluar[$m] = [
                'nombre' => $prod ? $prod->nombreProducto : '',
                'categoria' => ($prod && $prod->GrupoProducto) ? $prod->GrupoProducto->nombreGrupo : '',
                'marca' => ($prod && $prod->MarcaProducto) ? $prod->MarcaProducto->nombreMarca : ''
            ];
        }

        // 2. Obtener top productos más vendidos de los últimos 30 días (con modelo)
        $topVendidos = \App\Models\Catalogo\Producto::query()
            ->with(['GrupoProducto', 'MarcaProducto'])
            ->join('DetalleComprobante', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
            ->join('RegistroProducto', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
            ->join('EgresoProducto', 'EgresoProducto.idRegistro', '=', 'RegistroProducto.idRegistro')
            ->select('Producto.idProducto', 'Producto.idGrupo', 'Producto.idMarca', 'Producto.modelo', 'Producto.nombreProducto', \DB::raw('COUNT(EgresoProducto.idEgreso) as total_ventas'))
            ->whereNotNull('Producto.modelo')
            ->where('Producto.modelo', '!=', '')
            ->where('EgresoProducto.fechaCompra', '>=', now()->subDays(30))
            ->groupBy('Producto.idProducto', 'Producto.idGrupo', 'Producto.idMarca', 'Producto.modelo', 'Producto.nombreProducto')
            ->orderBy('total_ventas', 'desc')
            ->take(20) // Tomamos los 20 más vendidos
            ->get();

        foreach($topVendidos as $prod) {
            if (!isset($modelosAEvaluar[$prod->modelo])) {
                $modelosAEvaluar[$prod->modelo] = [
                    'nombre' => $prod->nombreProducto,
                    'categoria' => $prod->GrupoProducto ? $prod->GrupoProducto->nombreGrupo : '',
                    'marca' => $prod->MarcaProducto ? $prod->MarcaProducto->nombreMarca : ''
                ];
            }
        }

        $total = count($modelosAEvaluar);
        $this->info("Modelos a evaluar: {$total}");

        $miTienda = 'GAMING POWER PERU';
        $umbralPorcentaje = 0.05; // 5%
        $index = 0;

        foreach ($modelosAEvaluar as $modelo => $datos) {
            $nombreProducto = $datos['nombre'];
            $categoriaLocal = $datos['categoria'];
            $marcaLocal = $datos['marca'];
            $this->info(" Evaluando [".($index+1)."/$total]: $modelo - $nombreProducto ($categoriaLocal) ($marcaLocal)");

            $result = $scraperService->scrapePrices($modelo, $miTienda, $nombreProducto, $categoriaLocal, $marcaLocal);

            if ($result['success'] && !empty($result['productos'])) {
                $miPrecio = null;
                $precioCompetidor = null;
                $competidor = null;

                // Extraer mi precio y el precio del competidor más barato
                foreach ($result['productos'] as $prod) {
                    if (stripos($prod['vendedor'], $miTienda) !== false) {
                        if ($miPrecio === null || $prod['precio_mas_bajo'] < $miPrecio) {
                            $miPrecio = $prod['precio_mas_bajo'];
                        }
                    } else {
                        if ($precioCompetidor === null || $prod['precio_mas_bajo'] < $precioCompetidor) {
                            $precioCompetidor = $prod['precio_mas_bajo'];
                            $competidor = $prod['vendedor'];
                        }
                    }
                }

                if ($miPrecio !== null && $precioCompetidor !== null) {
                    $diferencia = $miPrecio - $precioCompetidor;
                    $diferenciaPorcentaje = $diferencia / $precioCompetidor;

                    // Si la diferencia es mayor al 50%, seguramente estamos comparando la impresora contra un accesorio (tinta, caja de mantenimiento)
                    if (abs($diferenciaPorcentaje) > 0.50) {
                        $this->line("   -> DIFERENCIA MAYOR AL 50% (" . round(abs($diferenciaPorcentaje) * 100, 2) . "%). Se ignora por posible confusión con accesorio.");
                        // Limpiar alertas previas si existían
                        \App\Models\Ecommerce\AlertaPrecio::where('modelo', $modelo)->where('estado', 'pendiente')->update(['estado' => 'procesada']);
                    }
                    // Si somos más caros por 1 sol o más
                    elseif ($diferencia >= 1) {
                        $this->warn("   -> MUY CARO: Mi precio: $miPrecio, Competidor: $precioCompetidor ($competidor)");
                        $this->guardarAlerta($modelo, $miPrecio, $precioCompetidor, $competidor, $diferenciaPorcentaje * 100, 'BAJAR');
                    } 
                    // Si somos un 5% más baratos (estamos perdiendo margen)
                    elseif ($diferenciaPorcentaje <= -$umbralPorcentaje) {
                        $this->warn("   -> MUY BARATO: Mi precio: $miPrecio, Competidor: $precioCompetidor ($competidor)");
                        $this->guardarAlerta($modelo, $miPrecio, $precioCompetidor, $competidor, abs($diferenciaPorcentaje * 100), 'SUBIR');
                    } else {
                        $this->line("   -> PRECIO OK.");
                        // Limpiar alertas previas si ahora está OK
                        \App\Models\Ecommerce\AlertaPrecio::where('modelo', $modelo)->where('estado', 'pendiente')->update(['estado' => 'procesada']);
                    }
                } else {
                    $this->line("   -> No se encontró 'Mi Precio' o 'Precio Competidor' en Falabella.");
                }
            } else {
                $this->error("   -> Error o sin resultados.");
            }

            // Pausa aleatoria para evitar baneos (simula comportamiento humano)
            if ($index < $total - 1) {
                $sleepTime = rand(10, 30);
                $this->line("   [Zzz] Esperando $sleepTime segundos...");
                sleep($sleepTime);
            }
            $index++;
        }

        $this->info("Bot finalizado.");
    }

    private function guardarAlerta($modelo, $miPrecio, $precioCompetidor, $competidor, $porcentaje, $sugerencia)
    {
        // Buscar si ya hay una alerta pendiente para no duplicar
        $alerta = \App\Models\Ecommerce\AlertaPrecio::where('modelo', $modelo)
            ->where('estado', 'pendiente')
            ->first();

        if ($alerta) {
            $alerta->update([
                'mi_precio' => $miPrecio,
                'precio_competidor' => $precioCompetidor,
                'competidor' => $competidor,
                'diferencia_porcentaje' => $porcentaje,
                'sugerencia' => $sugerencia,
            ]);
        } else {
            \App\Models\Ecommerce\AlertaPrecio::create([
                'modelo' => $modelo,
                'mi_precio' => $miPrecio,
                'precio_competidor' => $precioCompetidor,
                'competidor' => $competidor,
                'diferencia_porcentaje' => $porcentaje,
                'sugerencia' => $sugerencia,
                'estado' => 'pendiente',
            ]);
        }
    }
}
