<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ShalomSyncController extends Controller
{
    public function sync(Request $request)
    {
        try {
            // Ejecutamos el comando en background
            Artisan::call('sync:shalom');
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronización con Shalom completada correctamente.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar con Shalom: ' . $e->getMessage()
            ], 500);
        }
    }
}
