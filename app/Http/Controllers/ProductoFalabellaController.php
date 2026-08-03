<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Catalogo\Producto;
use App\Models\Usuarios\Usuario;
use App\Services\FalabellaTemplateService;

class ProductoFalabellaController extends Controller
{
    /**
     * Descarga el template de Falabella pre-llenado con los datos del producto.
     */
    public function descargarTemplateFalabella($idProducto)
    {
        try {
            $producto = Producto::with(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas'])
                ->findOrFail($idProducto);

            $service  = new FalabellaTemplateService();
            $usuario  = Usuario::select('idUser', 'user')->find(session('idUser'));
            $filePath = $service->generarTemplate($producto, $usuario);

            $fileName = basename($filePath);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo generar el template: ' . $e->getMessage());
        }
    }

    /**
     * Descarga el template Express de Falabella pre-llenado con los datos del producto.
     */
    public function descargarTemplateFalabellaExpress($idProducto)
    {
        try {
            $producto = Producto::with(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas'])
                ->findOrFail($idProducto);

            $service  = new FalabellaTemplateService();
            $usuario  = Usuario::select('idUser', 'user')->find(session('idUser'));
            $filePath = $service->generarTemplateExpress($producto, $usuario);

            $fileName = basename($filePath);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo generar el template Express: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: devuelve títulos sugeridos para el modal de personalización.
     * GET /producto/{idProducto}/falabella-titulos-sugeridos
     */
    public function sugerirTitulosFalabella($idProducto)
    {
        try {
            $producto = Producto::with(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas'])
                ->findOrFail($idProducto);

            $service  = new FalabellaTemplateService();
            $titulos  = $service->sugerirTitulos($producto);

            return response()->json(['success' => true, 'titulos' => $titulos]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Descarga template Express con títulos personalizados enviados via POST.
     * POST /producto/{idProducto}/falabella-template-express
     * Body JSON: { "titulo1": "...", "titulo2": "...", "titulo3": "..." }
     */
    public function descargarTemplateFalabellaExpressPost(Request $request, $idProducto)
    {
        try {
            $producto = Producto::with(['MarcaProducto', 'GrupoProducto', 'Caracteristicas_Producto.Caracteristicas'])
                ->findOrFail($idProducto);

            $titulos = [
                'titulo1' => trim($request->input('titulo1', '')),
                'titulo2' => trim($request->input('titulo2', '')),
                'titulo3' => trim($request->input('titulo3', '')),
            ];

            $service  = new FalabellaTemplateService();
            $usuario  = Usuario::select('idUser', 'user')->find(session('idUser'));
            $filePath = $service->generarTemplateExpress($producto, $usuario, $titulos);

            $fileName = basename($filePath);

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar template: ' . $e->getMessage()], 500);
        }
    }
}
