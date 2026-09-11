<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MercadoLibreScraperV2Service;
use App\Models\Ecommerce\MercadoLibreCredential;

class MercadoLibreScraperV2ApiController extends Controller
{
    /**
     * V2: Busca precios reales de competencia usando la API pública de búsqueda.
     */
    public function verificarPrecio(Request $request, MercadoLibreScraperV2Service $scraperService)
    {
        $search = $request->input('modelo', '');

        if (empty(trim($search))) {
            return response()->json([
                'success' => false,
                'message' => 'Debes ingresar un término de búsqueda.'
            ]);
        }

        $credential = MercadoLibreCredential::first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cuentas de Mercado Libre configuradas.'
            ]);
        }

        $result = $scraperService->searchAndCompare(
            $credential->seller_id,
            $search
        );

        return response()->json($result);
    }
}
