<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigAlertasController extends Controller
{
    protected $headerService;

    public function __construct(
        HeaderServiceInterface $headerService
    ) {
        $this->headerService = $headerService;
    }

    public function pedidosWeb()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $pedidos = \App\Models\Web\PedidoWeb::with(['cliente', 'detalles.producto'])->orderBy('created_at', 'desc')->get();

                return view('configuracion.configpedidosweb', [
                    'user' => $userModel,
                    'pagina' => 'pedidosweb',
                    'pedidos' => $pedidos
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updatePedidoWebEstado(Request $request, $id)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) { // Configuracion
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No autorizado']);
        }

        $request->validate([
            'estado' => 'required|string'
        ]);

        $pedido = \App\Models\Web\PedidoWeb::find($id);
        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado']);
        }

        $pedido->estado = $request->estado;
        $pedido->save();

        return response()->json(['success' => true, 'message' => 'Estado actualizado a '.$request->estado]);
    }

    public function updateAlertaEstado(Request $request, $id)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) { // Configuracion
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No autorizado']);
        }

        $request->validate([
            'estado' => 'required|in:pendiente,resuelto,ignorado,procesada'
        ]);

        \Illuminate\Support\Facades\DB::table('alerta_precios')
            ->where('id', $id)
            ->update([
                'estado' => $request->estado,
                'updated_at' => now()
            ]);

        return response()->json(['success' => true, 'message' => 'Estado actualizado']);
    }

    public function crearAlertaManual(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) { // Configuracion
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No autorizado']);
        }

        $request->validate([
            'modelo' => 'required|string|max:255',
            'mi_precio' => 'required|numeric',
            'competidor' => 'required|string|max:255',
            'precio_competidor' => 'required|numeric'
        ]);

        $diferencia_porcentaje = 0;
        if ($request->mi_precio > 0) {
            $diferencia = $request->mi_precio - $request->precio_competidor;
            $diferencia_porcentaje = round(($diferencia / $request->mi_precio) * 100, 2);
        }

        \Illuminate\Support\Facades\DB::table('alerta_precios')->insert([
            'modelo' => $request->modelo,
            'mi_precio' => $request->mi_precio,
            'competidor' => $request->competidor,
            'precio_competidor' => $request->precio_competidor,
            'diferencia_porcentaje' => $diferencia_porcentaje,
            'sugerencia' => $request->sugerencia ?? '',
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Alerta manual registrada correctamente']);
    }

    public function addVigilado(Request $request)
    {
        $request->validate(['modelo' => 'required|string']);
        \App\Models\Ecommerce\ProductoVigiladoFalabella::updateOrCreate(
            ['modelo' => $request->modelo],
            ['activo' => true]
        );
        return back();
    }

    public function deleteVigilado($id)
    {
        \App\Models\Ecommerce\ProductoVigiladoFalabella::where('id', $id)->delete();
        return back();
    }

    public function ejecutarBotPrecios()
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) { // Configuracion
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No autorizado']);
        }

        try {
            $basePath = base_path();
            $logPath = storage_path('logs/bot_falabella.log');
            $artisanPath = base_path('artisan');
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B php \"$artisanPath\" bot:falabella-prices > \"$logPath\" 2>&1", "r"));
            } else {
                pclose(popen("php \"$artisanPath\" bot:falabella-prices > \"$logPath\" 2>&1 &", "r"));
            }
            
            return response()->json(['success' => true, 'message' => 'El bot se ha iniciado en segundo plano. Las alertas aparecerán en unos minutos.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al iniciar el bot: ' . $e->getMessage()]);
        }
    }

    public function verLogBot()
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'No autorizado']);
        }

        $logPath = storage_path('logs/bot_falabella.log');
        if (!file_exists($logPath)) {
            return response()->json(['success' => true, 'log' => 'Aún no hay reportes del bot disponibles.']);
        }

        $log = file_get_contents($logPath);
        return response()->json(['success' => true, 'log' => $log]);
    }
}