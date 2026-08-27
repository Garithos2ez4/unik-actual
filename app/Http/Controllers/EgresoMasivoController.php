<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Services\EgresoProductoServiceInterface;
use App\Services\VentaServiceInterface;

class EgresoMasivoController extends Controller
{
    protected $headerService;
    protected $egresoService;
    protected $ventaService;

    public function __construct(
        HeaderServiceInterface $headerService,
        EgresoProductoServiceInterface $egresoService,
        VentaServiceInterface $ventaService
    ) {
        $this->headerService = $headerService;
        $this->egresoService = $egresoService;
        $this->ventaService = $ventaService;
    }


    public function importarExcel(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        // Verificar acceso
        $tieneAcceso = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $tieneAcceso = true;
                break;
            }
        }

        if (!$tieneAcceso) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            set_time_limit(0); // Permitir tiempo ilimitado para archivos masivos
            ini_set('memory_limit', '-1'); // Aumentar límite de memoria para archivos grandes

            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EgresosImport($this->egresoService, $this->ventaService), $request->file('archivo_excel'));
            $this->headerService->sendFlashAlerts('Egresos masivos registrados', 'El archivo Excel se ha procesado exitosamente.', 'success', 'btn-success');
        } catch (\Exception $e) {
            $this->headerService->sendFlashAlerts('Error al importar', 'Ocurrió un error: ' . $e->getMessage(), 'error', 'btn-danger');
        }

        return back();
    }


    public function egresosMasivos()
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $metodosPago = \App\Models\Ventas\MetodoPago::where('estado', 1)->get();
                $cuentasBancarias = \App\Models\Empresa\CuentasTransferencia::with('Banco')->orderBy('idBanco')->get();
                $empresas = \App\Models\Empresa\Empresa::all();
                $tipoDocumentos = \App\Models\Usuarios\TipoDocumento::all();
                $almacenes = \App\Models\Inventario\Almacen::all();
                $tasaCambio = app(\App\Services\CalculadoraServiceInterface::class)->obtenerCambioDolar() ?? 3.42;
                return view('egresos.egresos_masivos', [
                    'user' => $userModel,
                    'metodosPago' => $metodosPago,
                    'cuentasBancarias' => $cuentasBancarias,
                    'empresas' => $empresas,
                    'tasaCambio' => $tasaCambio,
                    'tipoDocumentos' => $tipoDocumentos,
                    'almacenes' => $almacenes
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }


    public function descargarFormato()
    {
        $export = new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function collection()
            {
                return collect([
                    [
                        '11/05/2026',
                        'Leonardo',
                        'DISCO SOLIDO OEM M2 256GB',
                        '1',
                        'Egreso',
                        'De Tienda',
                        'ML - Unik',
                        'UNK-SSDOEM256GB-100036',
                        '2000016365872746',
                        'SKU-EJEMPLO',
                        'FLEX',
                        ''
                    ]
                ]);
            }

            public function headings(): array
            {
                return [
                    'Fecha',
                    'Responsable',
                    'Producto',
                    'Un.',
                    'Movimiento',
                    'Almacén',
                    'Plataforma',
                    'SERIES',
                    'Orden',
                    'SKU',
                    'Envío por',
                    'Observaciones'
                ];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'formato_egresos.xlsx');
    }
}
