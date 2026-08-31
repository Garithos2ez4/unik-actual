<?php

namespace App\Http\Controllers;

use App\Services\PdfServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use App\Models\Inventario\UbicacionAlmacen;
use App\Models\Inventario\UbicacionEstante;
use App\Models\Inventario\RegistroProducto;

class PdfController extends Controller
{
    protected $pdfService;

    public function __construct(PdfServiceInterface $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function generateSerialPdf($idDocumento)
    {
        $series = $this->pdfService->getSerialsPrint($idDocumento);
        $data = ['title' => 'Números de series',
                'series' => $series];
        $pdf = Pdf::loadView('pdf.series_pdf', $data);

        return $pdf->stream('series.pdf');
    }

    public function reportStockPdf($idAlmacen) {
        $almacen = $this->pdfService->getAlmacenById($idAlmacen);
        $productos = $this->pdfService->getProductsWithStock($idAlmacen);

        // Calcular el total de stock
        $totalStock = $productos->sum(function ($producto) use ($almacen) {
            return $producto->Inventario
                ->firstWhere('idAlmacen', $almacen->idAlmacen)
                ->stock ?? 0;
        });

        $fechaActual = Carbon::now()->format('d-m-Y');
        $nombreAlmacen = $almacen->descripcion;

        $data = [
            'title' => 'Reporte de stock - ' . $nombreAlmacen . ' - ' . $fechaActual,
            'productos' => $productos,
            'almacen' => $almacen,
            'totalStock' => $totalStock
        ];

        $pdf = Pdf::loadView('pdf.stock_pdf', $data);
        return $pdf->stream('reporte_stock_' . $nombreAlmacen . '_' . $fechaActual . '.pdf');
    }

    public function reportEstantePdf($idUbicacion)
    {
        $estante = UbicacionAlmacen::with('Almacen')->findOrFail($idUbicacion);
        $fechaActual = Carbon::now()->format('d-m-Y');

        // Buscar todas las filas de este estante
        $filas = UbicacionEstante::where('nombre_rack', $estante->nombre)
            ->where('idAlmacen', $estante->idAlmacen)
            ->orderBy('fila_estante')
            ->get();

        foreach ($filas as $fila) {
            // Buscar los registros de productos en esta fila, ignorando los que ya no están
            $registros = RegistroProducto::with('DetalleComprobante.Producto')
                ->whereHas('DetalleComprobante.Producto')
                ->where('ubicacion_especifica', $fila->idUbicacionExacta)
                ->whereNotIn('estado', ['INVALIDO', 'ENTREGADO', 'VENDIDO', 'SALIDA', 'DIVIDIDO', 'REUNIDO'])
                ->get();
            
            // Agrupar por idProducto y estado, para contar por separado
            $agrupados = $registros->groupBy(function ($item) {
                return $item->DetalleComprobante->idProducto . '-' . $item->estado;
            })->map(function ($items) {
                return [
                    'producto' => $items->first()->DetalleComprobante->Producto,
                    'estado' => $items->first()->estado,
                    'cantidad' => $items->count()
                ];
            });

            $fila->productos_agrupados = $agrupados;
        }

        $data = [
            'title' => 'Reporte de Estante - ' . $estante->nombre,
            'estante' => $estante,
            'filas' => $filas,
            'fecha' => $fechaActual
        ];

        $pdf = Pdf::loadView('pdf.estante_pdf', $data);
        return $pdf->stream('reporte_estante_' . $estante->nombre . '_' . $fechaActual . '.pdf');
    }
    public function seriesByProductPdf($idProducto, $idAlmacen = null)
    {
        $producto = $this->pdfService->getOneProduct($idProducto);
        $registros = $this->pdfService->getSerialsByProduct($idProducto, $idAlmacen);


        $almacen = $idAlmacen ? $this->pdfService->getAlmacenById($idAlmacen) : null;
        $nombreAlmacen = $almacen->descripcion;
        $data = [
            'title' => 'Series en existencia - ' . $nombreAlmacen,
            'producto' => $producto,
            'registros' => $registros,
            'almacen' => $almacen
        ];

        $pdf = Pdf::loadView('pdf.series_by_products', $data);
        $filename = "Series_disponibles_{$producto->modelo}" . ($almacen ? "_{$almacen->descripcion}" : '') . ".pdf";
        $filename = str_replace(['/', '\\'], '-', $filename);
        return $pdf->stream($filename);
    }

    public function garantiaPdf($idGarantia)
    {
        $garantia = $this->pdfService->getOneGarantia($idGarantia);

        $cabRel = 'storage/cabecera_garantia.jpg';
        $cabUrl = $this->getBase64Image($cabRel,'jpg');

        $firmaRel = 'storage/firmaflor.png';
        $firmaUrl = $this->getBase64Image($firmaRel,'png');

        $data = ['title' => 'Registro Producto Garantía',
                'garantia' => $garantia,
                'cabecera' => $cabUrl,
                'firma' => $firmaUrl];
        $pdf = Pdf::loadView('pdf.garantia_pdf', $data);
        return $pdf->stream('Garantia_Registro_'.(1000000 + $garantia->idGarantia).'.pdf');
    }

    private function getBase64Image($pathRel,$type){
        $pathCab = public_path($pathRel);
        $imgCab = base64_encode(file_get_contents($pathCab));
        return 'data:image/'.$type.';base64,' . $imgCab;
    }
    public function productoSeriesPdf($idProducto, $idAlmacen)
    {
        $producto = $this->pdfService->getOneProduct($idProducto);
        $almacen  = $this->pdfService->getAlmacenById($idAlmacen);

        $series = $this->pdfService
            ->getSerialsByProductWithBarcode($idProducto, $idAlmacen);

        return Pdf::loadView('pdf.producto_series_barcode', [
            'producto' => $producto,
            'almacen'  => $almacen,
            'series'   => $series,
            'title'    => 'Series con código de barras'
        ])->stream(
            "Series_{$producto->modelo}_{$almacen->descripcion}.pdf"
        );
    }

}
