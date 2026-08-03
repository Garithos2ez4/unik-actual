<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FalabellaScraperController extends Controller
{
    /**
     * Busca un modelo en Falabella y extrae el precio.
     * 
     * Nota Arquitectónica: A diferencia de Shalom que usa un token XSRF y una API REST, 
     * Falabella utiliza SSR con Next.js y está protegido por Cloudflare/Datadome. 
     * No hay un "inicio de sesión" simple para búsqueda, sino que extraemos los datos 
     * directamente del HTML inicial generado por el servidor (SSR).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarPrecio(Request $request, \App\Services\FalabellaScraperService $scraperService)
    {
        $modelo = $request->input('modelo');
        $miTienda = $request->input('mi_tienda', '');

        if (!$modelo) {
            return response()->json(['success' => false, 'message' => 'El parámetro modelo es requerido']);
        }

        $result = $scraperService->scrapePrices($modelo, $miTienda);

        if (!$result['success']) {
            return response()->json($result); // Devuelve error HTTP 200 con el flag success=false
        }

        return response()->json($result);
    }
}
