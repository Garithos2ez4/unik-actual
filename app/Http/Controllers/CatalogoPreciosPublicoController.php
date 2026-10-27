<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Catalogo\CategoriaProducto;
use App\Models\Catalogo\MarcaProducto;
use App\Models\Catalogo\GrupoProducto;
use App\Models\Catalogo\Producto;
use App\Services\CalculadoraServiceInterface;
use App\Services\PreciosService;

class CatalogoPreciosPublicoController extends Controller
{
    protected $calculadoraService;

    public function __construct(CalculadoraServiceInterface $calculadoraService)
    {
        $this->calculadoraService = $calculadoraService;
    }

    /**
     * Genera el token cifrado con encrypt() conteniendo idCategoria, idGrupo, idMarca y expiracion
     */
    public function generarToken(Request $request)
    {
        $request->validate([
            'idCategoria' => 'required|integer',
            'idGrupo' => 'nullable|integer',
            'idMarca' => 'nullable|integer'
        ]);

        $payload = [
            'idCategoria' => (int) $request->input('idCategoria'),
            'idGrupo' => $request->filled('idGrupo') ? (int) $request->input('idGrupo') : null,
            'idMarca' => $request->filled('idMarca') ? (int) $request->input('idMarca') : null,
            'exp' => now()->addHours(24)->timestamp
        ];

        $token = encrypt($payload);

        return response()->json([
            'success' => true,
            'token' => $token,
            'verUrl' => route('catalogo.precios.pdf.ver', ['token' => $token]),
            'descargarUrl' => route('catalogo.precios.pdf.descargar', ['token' => $token])
        ]);
    }

    /**
     * Visualizacion del PDF en linea (stream)
     */
    public function verPdf($token)
    {
        $data = $this->prepararDataPdf($token);

        $pdf = Pdf::loadView('pdf.catalogo_precios_pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        $this->aplicarSeguridadPdf($pdf);

        $filename = 'Catalogo_' . Str::slug($data['categoria']) . (!empty($data['grupo']) ? '_' . Str::slug($data['grupo']) : '') . ($data['marca'] !== 'Todas las marcas' ? '_' . Str::slug($data['marca']) : '') . '_' . date('Ymd_His') . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Descarga forzada del PDF
     */
    public function descargarPdf($token)
    {
        $data = $this->prepararDataPdf($token);

        $pdf = Pdf::loadView('pdf.catalogo_precios_pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->render();

        $this->aplicarSeguridadPdf($pdf);

        $filename = 'Catalogo_' . Str::slug($data['categoria']) . (!empty($data['grupo']) ? '_' . Str::slug($data['grupo']) : '') . ($data['marca'] !== 'Todas las marcas' ? '_' . Str::slug($data['marca']) : '') . '_' . date('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Desencripta el token y valida que no este alterado ni expirado. Devuelve 403 si falla.
     */
    private function prepararDataPdf($token): array
    {
        try {
            $payload = decrypt($token);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            abort(403, 'Acceso denegado: Token de seguridad no valido o alterado.');
        } catch (\Exception $e) {
            abort(403, 'Acceso denegado: Token no valido.');
        }

        if (!is_array($payload) || empty($payload['exp']) || $payload['exp'] < time()) {
            abort(403, 'Acceso denegado: El enlace ha expirado. Por favor genere uno nuevo desde el sistema.');
        }

        $idCategoria = (int) ($payload['idCategoria'] ?? 0);
        $idGrupo = !empty($payload['idGrupo']) ? (int) $payload['idGrupo'] : null;
        $idMarca = !empty($payload['idMarca']) ? (int) $payload['idMarca'] : null;

        $categoria = CategoriaProducto::find($idCategoria);
        if (!$categoria) {
            abort(404, 'Categoria no encontrada.');
        }

        $grupo = $idGrupo ? GrupoProducto::find($idGrupo) : null;
        $marca = $idMarca ? MarcaProducto::find($idMarca) : null;

        $items = $this->obtenerProductosFiltrados($idCategoria, $idGrupo, $idMarca);

        return [
            'categoria' => $categoria->nombreCategoria,
            'grupo' => $grupo ? $grupo->nombreGrupo : null,
            'marca' => $marca ? $marca->nombreMarca : 'Todas las marcas',
            'productos' => $items,
            'totalProductos' => count($items),
            'fechaEmision' => now()->format('d/m/Y H:i')
        ];
    }

    /**
     * Aplica la encriptacion al PDF bloqueando copia y edicion de texto, con lectura libre
     */
    private function aplicarSeguridadPdf($pdf): void
    {
        try {
            $canvas = $pdf->getDomPDF()->getCanvas();
            if (method_exists($canvas, 'get_cpdf') && $canvas->get_cpdf()) {
                $canvas->get_cpdf()->setEncryption('', config('app.key'), ['print']);
            }
        } catch (\Exception $e) {
            \Log::warning('No se pudo aplicar encriptacion al PDF: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene y procesa los productos aplicando:
     * 1. Excluye estados web AGOTADO y DESCONTINUADO
     * 2. Logica exacta de stock de lista_producto.blade.php:
     *    - Serializados: RegistroProducto sin estados excluidos ['ENTREGADO', 'INVALIDO', 'ELIMINADO', 'DIVIDIDO', 'REUNIDO', 'GARANTIA', 'DEFECTUOSO', 'ABIERTO']
     *    - No serializados: Inventario sum(stock)
     *    - Proveedor Deltron (idProveedor == 1)
     *    - Stock total > 0
     * 3. Mascara de stock (> 10 si stock > 10)
     * 4. Calculo estricto de precio en Soles (Backend) via PreciosService
     */
    private function obtenerProductosFiltrados($idCategoria, $idGrupo = null, $idMarca = null): array
    {
        $query = Producto::with([
            'Inventario',
            'Inventario_Proveedor',
            'liquidacion',
            'MarcaProducto',
            'GrupoProducto'
        ]);

        if ($idGrupo) {
            $query->where('idGrupo', $idGrupo);
        } else {
            $gruposIds = GrupoProducto::where('idCategoria', $idCategoria)->pluck('idGrupoProducto');
            $query->whereIn('idGrupo', $gruposIds);
        }

        if ($idMarca) {
            $query->where('idMarca', $idMarca);
        }

        // Excluir productos con estado web inactivo segun requerimiento
        $query->whereNotIn('estadoProductoWeb', ['AGOTADO', 'DESCONTINUADO']);

        $productos = $query->get();
        if ($productos->isEmpty()) {
            return [];
        }

        $productoIds = $productos->pluck('idProducto')->toArray();

        // Obtener series activas por producto en una sola consulta optimizada
        $seriesPorProducto = \Illuminate\Support\Facades\DB::table('RegistroProducto as rp')
            ->join('DetalleComprobante as dc', 'rp.idDetalleComprobante', '=', 'dc.idDetalleComprobante')
            ->whereIn('dc.idProducto', $productoIds)
            ->select(
                'dc.idProducto',
                \Illuminate\Support\Facades\DB::raw("COUNT(*) as total_series"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN rp.estado NOT IN ('ENTREGADO', 'INVALIDO', 'ELIMINADO', 'DIVIDIDO', 'REUNIDO', 'GARANTIA', 'DEFECTUOSO', 'ABIERTO') THEN 1 ELSE 0 END) as series_validas")
            )
            ->groupBy('dc.idProducto')
            ->get()
            ->keyBy('idProducto');

        $tcSunat = (float) $this->calculadoraService->getTasaCambio();
        $tcFijoGlobal = (float) $this->calculadoraService->getTasaFija()->tasaCambio;
        $preciosService = new PreciosService();

        $gruposParaPreload = $idGrupo ? [$idGrupo] : (isset($gruposIds) ? $gruposIds->toArray() : $productos->pluck('idGrupo')->unique()->toArray());
        PreciosService::preloadGrupos($gruposParaPreload);

        $items = [];

        foreach ($productos as $p) {
            $seriesInfo = $seriesPorProducto->get($p->idProducto);
            $esSerializado = $seriesInfo && $seriesInfo->total_series > 0;

            if ($esSerializado) {
                $stockFisico = (int) $seriesInfo->series_validas;
            } else {
                $stockFisico = (int) ($p->Inventario ? $p->Inventario->sum('stock') : 0);
            }

            // Proveedor solo Deltron (idProveedor == 1)
            $stockProv = ($p->Inventario_Proveedor && $p->Inventario_Proveedor->idProveedor == 1)
                ? (int) $p->Inventario_Proveedor->stock
                : 0;

            $stockTotal = $stockFisico + $stockProv;

            // Filtro obligatorio: Solo productos con Stock total > 0
            if ($stockTotal <= 0) {
                continue;
            }

            // Mascara de seguridad para stock: si supera 10 unidades, muestra "> 10"
            $stockMascara = ($stockTotal > 10) ? '> 10' : (string) $stockTotal;

            // Calculo 100% backend con PreciosService en Soles
            $precioCalculado = $preciosService->getPrecioCalculado(
                (float) $p->precioDolar,
                $p->idGrupo,
                'DOLAR',
                $p->estadoProductoWeb
            );

            if (!$precioCalculado || $precioCalculado <= 0) {
                $precioCalculado = $preciosService->getIgv((float) $p->precioDolar);
            }

            $usarTcFijo = $p->usar_tc_fijo ?? true;
            if ($usarTcFijo) {
                $tcAUsar = $tcSunat;
            } else {
                if (!empty($p->tc_fijo) && $p->tc_fijo > 0) {
                    $tcAUsar = (float) $p->tc_fijo;
                } else {
                    $tcAUsar = $tcFijoGlobal;
                }
            }

            $precioVentaUsd = $precioCalculado + (float) ($p->gananciaExtra ?? 0);
            $precioVentaSoles = round($precioVentaUsd * $tcAUsar, 2);

            // Si esta en liquidacion, el precio web final es el fijado en liquidacion
            if ($p->estadoProductoWeb === 'LIQUIDACION' && $p->liquidacion) {
                $precioVentaSoles = (float) $p->liquidacion->precio_liquidacion;
            }

            // Al frontend y PDF solo viaja el dato final, sin costos ni formulas
            $items[] = [
                'nombre' => $p->nombreProducto,
                'modelo' => $p->modelo ?: '-',
                'stock' => $stockMascara,
                'precio' => 'S/. ' . number_format($precioVentaSoles, 2, '.', ',')
            ];
        }

        // Ordenar alfabeticamente por nombre del producto
        usort($items, function ($a, $b) {
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        return $items;
    }
}
