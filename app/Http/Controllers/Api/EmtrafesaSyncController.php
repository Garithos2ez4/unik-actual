<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class EmtrafesaSyncController extends Controller
{
    /**
     * Ejecuta el comando de sincronización de Emtrafesa y retorna JSON
     */
    public function sync()
    {
        try {
            // Ejecutamos el comando artisan
            $exitCode = Artisan::call('sync:emtrafesa');
            
            // Obtenemos la salida del comando para poder enviarla si es necesario
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización con Emtrafesa completada con éxito.',
                    'details' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Hubo problemas al ejecutar la sincronización de Emtrafesa.',
                    'details' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
        }
    }
}
