<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FalabellaScraperController;
use App\Http\Controllers\Api\MercadoLibreScraperApiController;

// Rutas Públicas de Búsqueda (Protegidas con Rate Limiting para evitar scraping abusivo)
Route::middleware('throttle:scrapers')->group(function () {
    Route::get('/verificar-precio-falabella', [FalabellaScraperController::class, 'verificarPrecio']);
    Route::get('/verificar-precio-mercadolibre', [MercadoLibreScraperApiController::class, 'verificarPrecio']);
    Route::get('/verificar-precio-mercadolibre-v2', [\App\Http\Controllers\Api\MercadoLibreScraperV2ApiController::class, 'verificarPrecio']);
    Route::get('/verificar-precio-ripley', [\App\Http\Controllers\Api\RipleyScraperApiController::class, 'verificarPrecio']);
});

// Rutas Públicas de Autenticación (Protegida contra fuerza bruta)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Rutas Protegidas (Requieren token Sanctum y Rate Limiting)
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Ruta por defecto de Laravel
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
