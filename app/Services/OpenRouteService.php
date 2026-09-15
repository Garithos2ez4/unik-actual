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
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type'  => 'application/json; charset=utf-8',
                'Accept'        => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8'
            ])->post($this->baseUrl, [
                'coordinates' => [
                    [$this->origenLon, $this->origenLat],
                    [$destinoLon, $destinoLat]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // La distancia viene en metros en rutas[0]['summary']['distance']
                if (isset($data['routes'][0]['summary']['distance'])) {
                    $metros = $data['routes'][0]['summary']['distance'];
                    $kilometros = $metros / 1000;
                    return round($kilometros, 2);
                }
            } else {
                Log::error('OpenRouteService Error: ' . $response->body());
            }

        } catch (\Throwable $th) {
            Log::error('OpenRouteService Exception: ' . $th->getMessage());
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
            $costoEnvio = $distanceKmRounded * 3;
            if ($costoEnvio < 10) {
                $costoEnvio = 10;
            }
        }
        
        return (float) $costoEnvio;
    }
}
