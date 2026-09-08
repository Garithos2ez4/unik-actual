<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MercadoLibreScraperService;
use App\Models\Ecommerce\MercadoLibreCredential;

class MercadoLibreScraperApiController extends Controller
{
    /**
     * Obtiene referencias de precios de competencia para los productos publicados.
     */
    public function verificarPrecio(Request $request, MercadoLibreScraperService $scraperService)
    {
        $search = $request->input('modelo', '');

        // Obtener la primera cuenta de ML (o la que corresponda)
        $credential = MercadoLibreCredential::first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cuentas de Mercado Libre configuradas.'
            ]);
        }

        $result = $scraperService->getItemsWithPriceReferences(
            $credential->seller_id,
            $search ?: null
        );

        return response()->json($result);
    }
}
