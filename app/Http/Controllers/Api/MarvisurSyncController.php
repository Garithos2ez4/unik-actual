<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MarvisurSyncController extends Controller
{
    public function sync()
    {
        try {
            Artisan::call('sync:marvisur');
            $output = Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
}
