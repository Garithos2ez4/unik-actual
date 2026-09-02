<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeltronScraperService;
use App\Services\HeaderServiceInterface;

class DeltronController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    /**
     * Muestra la vista de ofertas/stock de Deltron.
     */
    public function indexStock(Request $request, DeltronScraperService $scraperService)
    {
        $userModel = $this->headerService->getModelUser();

        $keyword = $request->input('q', 'LAPTOP');
        $page = (int) $request->input('page', 1);

        // Scraping en tiempo real ordenado por stock descendente
        $productos = $scraperService->scrapeByKeyword($keyword, 'stock_desc', $page);

        // Se toman los últimos 11 registros porque el cron sincroniza 11 categorías por ejecución
        $historial = \App\Models\Inventario\InventarioProveedorDetalle::orderBy('fecha_ejecucion', 'desc')->take(11)->get();

        return view('compras.deltron_stock', [
            'user' => $userModel,
            'productos' => $productos,
            'keyword' => $keyword,
            'page' => $page,
            'historial' => $historial
        ]);
    }

    public function syncManual()
    {
        // Aumentar el tiempo límite a ilimitado ya que sincronizar 10 categorías toma más de 30 segundos
        set_time_limit(0);

        try {
            // Llama al comando artisan internamente
            \Illuminate\Support\Facades\Artisan::call('scrape:deltron');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return redirect()->route('compras.deltron')->with('success', 'Sincronización manual finalizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('compras.deltron')->with('error', 'Error al sincronizar: ' . $e->getMessage());
        }
    }
}
