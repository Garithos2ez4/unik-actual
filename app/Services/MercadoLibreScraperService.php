<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MercadoLibreScraperService
{
    protected MercadoLibreApiService $apiService;

    public function __construct(MercadoLibreApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Obtiene ítems del seller con referencias de precios.
     * Procesamos en lotes pequeños para no explotar el timeout.
     */
    public function getItemsWithPriceReferences(string $sellerId, ?string $search = null): array
    {
        // Aumentar límite de ejecución para esta operación
        set_time_limit(120);

        try {
            // 1. Obtener lista de item_ids con sugerencias
            $suggestionsData = $this->apiService->getSuggestionItems($sellerId);
            $itemIds = $suggestionsData['items'] ?? [];
            $total = $suggestionsData['total'] ?? 0;

            if ($total === 0 || empty($itemIds)) {
                return [
                    'success' => true,
                    'total' => 0,
                    'mostrando' => 0,
                    'productos' => [],
                    'message' => 'No se encontraron referencias de precios para esta cuenta.'
                ];
            }

            $hasSearch = ($search && trim($search) !== '');
            $itemsToProcess = [];

            // Si hay filtro de búsqueda, buscar esos IDs en ML primero
            if ($hasSearch) {
                try {
                    $searchData = $this->apiService->searchSellerItems($sellerId, trim($search));
                    $searchItemIds = $searchData['results'] ?? [];
                    
                    // Si buscamos algo, procesamos SOLO esos ítems que devolvió la búsqueda (tengan referencia o no)
                    $itemsToProcess = $searchItemIds;
                } catch (\Exception $e) {
                    Log::error("Error buscando items en ML: " . $e->getMessage());
                }
            } else {
                // Si no hay búsqueda, procesamos los que tienen referencia de precio (limitamos luego)
                $itemsToProcess = $itemIds;
            }

            if (empty($itemsToProcess)) {
                return [
                    'success' => true,
                    'total' => $total, // Mantenemos el total original para contexto
                    'mostrando' => 0,
                    'productos' => [],
                    'message' => $hasSearch 
                        ? 'No se encontraron productos que coincidan con tu búsqueda.' 
                        : 'No se encontraron referencias de precios para esta cuenta.'
                ];
            }

            // 2. Procesar en lotes — queremos máximo 20 resultados VÁLIDOS
            $maxResultados = 20;
            $maxIntentos = 80; // No intentar más de 80 ítems para no pasarnos del timeout
            $productos = [];
            $intentos = 0;

            foreach ($itemsToProcess as $itemId) {
                if (count($productos) >= $maxResultados || $intentos >= $maxIntentos) {
                    break;
                }

                $intentos++;

                // Llamar al detalle (devuelve null si 404 — no tira excepción)
                $detail = $this->apiService->getSuggestionDetail($sellerId, $itemId);

                if ($detail === null) {
                    // Si no tiene referencia, pero fue buscado específicamente, mostramos "Sin datos"
                    if ($hasSearch) {
                        try {
                            $basicInfo = $this->apiService->getItem($sellerId, $itemId);
                            $productos[] = [
                                'item_id' => $itemId,
                                'titulo' => $basicInfo['title'] ?? 'Producto sin título',
                                'precio_actual' => $basicInfo['price'] ?? 0,
                                'precio_sugerido' => 0,
                                'precio_mas_bajo' => 0,
                                'status' => 'no_data',
                                'status_label' => 'Sin Referencia de ML',
                                'status_color' => 'secondary',
                                'diferencia_porcentaje' => 0,
                                'comision_venta' => 0,
                                'costo_envio' => 0,
                                'competidores' => 0,
                                'graph' => [],
                            ];
                        } catch (\Exception $e) {
                            // Si falla, ignoramos
                        }
                    }
                    continue; 
                }

                // Extraer datos
                $currentPrice = $detail['current_price']['amount'] ?? 0;
                $suggestedPrice = $detail['suggested_price']['amount'] ?? 0;
                $lowestPrice = $detail['lowest_price']['amount'] ?? 0;
                $status = $detail['status'] ?? 'unknown';
                $percentDiff = $detail['percent_difference'] ?? 0;
                $graph = $detail['metadata']['graph'] ?? [];
                $comparedValues = $detail['metadata']['compared_values'] ?? 0;
                $sellingFees = $detail['costs']['selling_fees'] ?? 0;
                $shippingFees = $detail['costs']['shipping_fees'] ?? 0;

                // Obtener título del primer competidor del graph
                $titulo = '';
                if (!empty($graph)) {
                    $titulo = $graph[0]['info']['title'] ?? '';
                }

                $productos[] = [
                    'item_id' => $itemId,
                    'titulo' => $titulo,
                    'precio_actual' => $currentPrice,
                    'precio_sugerido' => $suggestedPrice,
                    'precio_mas_bajo' => $lowestPrice,
                    'status' => $status,
                    'status_label' => $this->getStatusLabel($status),
                    'status_color' => $this->getStatusColor($status),
                    'diferencia_porcentaje' => $percentDiff,
                    'comision_venta' => $sellingFees,
                    'costo_envio' => $shippingFees,
                    'competidores' => $comparedValues,
                    'graph' => $graph,
                ];
            }

            // Ordenar: los que tienen peor precio arriba
            usort($productos, function ($a, $b) {
                return $b['diferencia_porcentaje'] <=> $a['diferencia_porcentaje'];
            });

            return [
                'success' => true,
                'total' => $total,
                'mostrando' => count($productos),
                'productos' => $productos,
            ];
        } catch (\Exception $e) {
            Log::error("Error en suggestions ML: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al consultar las referencias de precios: ' . $e->getMessage()
            ];
        }
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'with_benchmark_highest' => 'Precio MUY ALTO',
            'with_benchmark_high' => 'Precio ALTO',
            'no_benchmark_ok' => 'Precio COMPETITIVO',
            'no_benchmark_lowest' => 'Eres el más BARATO',
            'not_optin_applied' => 'Promoción no aplicada',
            'promotion_scheduled' => 'Promoción programada',
            'promotion_active' => 'Promoción activa',
            default => $status,
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'with_benchmark_highest' => 'danger',
            'with_benchmark_high' => 'warning',
            'no_benchmark_ok' => 'success',
            'no_benchmark_lowest' => 'info',
            'promotion_active' => 'primary',
            default => 'secondary',
        };
    }
}
