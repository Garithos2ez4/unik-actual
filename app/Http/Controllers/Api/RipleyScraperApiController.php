<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RipleyScraperService;
use App\Models\Catalogo\Producto;

class RipleyScraperApiController extends Controller
{
    protected $scraperService;

    public function __construct(RipleyScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    public function verificarPrecio(Request $request)
    {
        $modelo = $request->query('modelo');
        
        if (empty($modelo)) {
            return response()->json(['success' => false, 'message' => 'Modelo no proporcionado'], 400);
        }

        // Obtener el precio local
        $producto = Producto::where('modelo', $modelo)->first();
        $precioLocal = 0;
        $costoLocal = 0;
        $precioSugerido = 0;
        $precioBajo = 0;

        if ($producto) {
            // Asumiendo que CalculadoraServiceInterface o similar calcula esto
            // Por simplicidad en la API, tomamos el precio actual del producto.
            // Si tienen campos en su DB como $producto->precioVenta:
            $precioLocal = $producto->precioVenta ?? 0;
            $costoLocal = $producto->costo ?? 0;
            
            // Lógica simple de sugerido y bajo (puedes ajustar según tu calculadora)
            $precioSugerido = $precioLocal * 1.1; 
            $precioBajo = $precioLocal * 0.9;
        }

        // Llamar al scraper
        $resultado = $this->scraperService->scrapePrices($modelo);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false, 
                'message' => $resultado['message'],
                'debug' => $resultado['debug'] ?? null
            ], 500);
        }

        // Calcular estado competitivo si hay producto local
        $estadoCompetitivo = 'Sin datos';
        $colorCompetitivo = 'secondary';
        
        if ($precioLocal > 0 && !empty($resultado['productos'])) {
            $precioMasBaratoCompetencia = null;
            
            foreach ($resultado['productos'] as $prod) {
                if (!$prod['is_my_store']) {
                    $precioMasBaratoCompetencia = $prod['price'];
                    break;
                }
            }

            if ($precioMasBaratoCompetencia !== null) {
                if ($precioLocal < $precioMasBaratoCompetencia) {
                    $estadoCompetitivo = 'Más Barato';
                    $colorCompetitivo = 'success';
                } elseif ($precioLocal == $precioMasBaratoCompetencia) {
                    $estadoCompetitivo = 'Igualado';
                    $colorCompetitivo = 'warning';
                } else {
                    $estadoCompetitivo = 'Más Caro';
                    $colorCompetitivo = 'danger';
                }
            } else {
                $estadoCompetitivo = 'Único Vendedor';
                $colorCompetitivo = 'info';
            }
        }

        // En Ripley, la comisión aprox (puedes ajustarla)
        $comisionRipley = $precioLocal > 0 ? ($precioLocal * 0.12) : 0; // 12% aprox

        return response()->json([
            'success' => true,
            'total' => $resultado['total'],
            'mostrando' => $resultado['mostrando'],
            'productos' => array_slice($resultado['productos'], 0, 10), // Máximo 10
            'precio_actual' => $precioLocal,
            'precio_sugerido' => $precioSugerido,
            'precio_mas_bajo' => $precioBajo,
            'status' => 'success',
            'status_label' => $estadoCompetitivo,
            'status_color' => $colorCompetitivo,
            'comision_venta' => round($comisionRipley, 2),
            'costo_envio' => 0, // Ajustar según lógica de Ripley
        ]);
    }
}
