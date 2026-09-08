<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FalabellaScraperController;
use App\Http\Controllers\Api\MercadoLibreScraperApiController;

// Rutas Públicas de Búsqueda
Route::get('/verificar-precio-falabella', [FalabellaScraperController::class, 'verificarPrecio']);
Route::get('/verificar-precio-mercadolibre', [MercadoLibreScraperApiController::class, 'verificarPrecio']);

// Rutas Públicas de Autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas Protegidas (Requieren token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Ruta por defecto de Laravel
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
