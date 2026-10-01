<?php

namespace App\Http\Controllers\Productos;
use App\Http\Controllers\Controller;

use App\Models\Catalogo\Producto;
use App\Models\Usuarios\Usuario;
use App\Services\MercadoLibreTemplateService;
use Illuminate\Http\Request;

class ProductoMercadoLibreController extends Controller
{
    /**
     * Descarga el template de MercadoLibre pre-llenado con los datos del producto.
     */
    public function descargarTemplateMercadoLibre($idProducto)
    {
        try {
            $producto = Producto::with(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas'])
                ->findOrFail($idProducto);

            $service  = new MercadoLibreTemplateService();
            $usuario  = Usuario::select('idUser', 'user')->find(session('idUser'));
            $filePath = $service->generarTemplate($producto, $usuario);

            $fileName = basename($filePath);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo generar el template de ML: ' . $e->getMessage());
        }
    }
}

