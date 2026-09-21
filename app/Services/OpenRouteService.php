<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouteService
{
    protected $apiKey;
    protected $baseUrl;

    // Coordenadas fijas para Av Bolivia 180, Centro de Lima (Aproximadas)
    // Es importante para ORS que el formato sea [longitud, latitud]
    protected $origenLon = -77.037805;
    protected $origenLat = -12.054366;

    public function __construct()
    {
        // En un entorno de producción, esto debería ir en el .env (OPENROUTESERVICE_API_KEY)
        $this->apiKey = env('OPENROUTESERVICE_API_KEY');
        $this->baseUrl = 'https://api.openrouteservice.org/v2/directions/driving-car';
    }

    /**
     * Calcula la distancia en kilómetros desde Av Bolivia 180 hasta las coordenadas de destino.
     * 
     * @param float $destinoLat
     * @param float $destinoLon
     * @return float|null Retorna la distancia en KM o null si falla
     */
    public function calcularDistancia(float $destinoLat, float $destinoLon): ?float
    {
        try {
            // Redondear a 5 decimales (aprox 1 metro de precisión) para agrupar coordenadas casi idénticas
            $latKey = str_replace('.', '_', (string) round($destinoLat, 5));
            $lonKey = str_replace('.', '_', (string) round($destinoLon, 5));
            $cacheKey = "gmaps_distancia_{$latKey}_{$lonKey}";

            // Verificar si ya tenemos almacenada la distancia en nuestros archivos locales (Caché persistente)
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                return \Illuminate\Support\Facades\Cache::get($cacheKey);
            }

            $apiKey = env('GOOGLE_MAPS_API_KEY');
            if (!$apiKey) {
                return null;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => "{$this->origenLat},{$this->origenLon}",
                'destinations' => "{$destinoLat},{$destinoLon}",
                'key' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0]['distance']['value'])) {
                    $metros = $data['rows'][0]['elements'][0]['distance']['value'];
                    $kilometros = round($metros / 1000, 2);
                    
                    // Si todo fue exitoso, ALMACENAR el dato localmente de forma permanente (ej. 365 días)
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $kilometros, now()->addDays(365));

                    return $kilometros;
                } else {
                    Log::error('Google Maps API Error: ' . json_encode($data));
                }
            } else {
                Log::error('Google Maps HTTP Error: ' . $response->body());
            }
        } catch (\Throwable $th) {
            Log::error('Google Maps API Exception: ' . $th->getMessage());
        }

        return null;
    }

    /**
     * Calcula el costo basado en la distancia.
     * Si la distancia es <= 2km, el costo es 10.
     * Si es mayor, se cobra 3 soles por KM (redondeado), asegurando un mínimo de 10.
     */
    public function calcularCosto(?float $distanciaKm): float
    {
        if ($distanciaKm === null) {
            return 10.00;
        }

        $distanceKmRounded = round($distanciaKm);

        if ($distanceKmRounded <= 2) {
            $costoEnvio = 10;
        } else {
            $costoEnvio = $distanceKmRounded * 2;
            if ($costoEnvio < 10) {
                $costoEnvio = 10;
            }
        }

        return (float) $costoEnvio;
    }
}
