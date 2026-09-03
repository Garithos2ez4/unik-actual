<?php

namespace App\Http\Controllers\Envios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HeaderServiceInterface;
use App\Models\Envios\EnvioProvincia;

class EnvioProvinciaExportController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function pdf(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $ids = $request->query('ids');

                $query = EnvioProvincia::with(['Usuario', 'Cliente', 'Plataforma', 'CuentaPlataforma', 'Agencia', 'Destino.Provincia', 'Productos.Producto.GrupoProducto', 'Productos.Producto.MarcaProducto', 'Detalle']);

                if (!empty($ids)) {
                    $idArray = explode(',', $ids);
                    $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
                    $fecha = null;
                } else {
                    $fecha = $request->query('fecha', date('Y-m-d'));
                    $envios = $query->whereDate('fecha_envio', $fecha)
                        ->where(function ($q) {
                            $q->whereDoesntHave('Detalle')
                              ->orWhereHas('Detalle', function ($q2) {
                                  $q2->where('despachado', 0);
                              });
                        })->get();
                }

                return view('envios.pdf', [
                    'envios' => $envios,
                    'fecha' => $fecha
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function listaProductos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $ids = $request->query('ids');

                $query = EnvioProvincia::with(['Usuario', 'Cliente', 'Plataforma', 'Agencia', 'Productos.Producto']);

                if (!empty($ids)) {
                    $idArray = explode(',', $ids);
                    $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
                    $fecha = null;
                } else {
                    $fecha = $request->query('fecha', date('Y-m-d'));
                    $envios = $query->whereDate('fecha_envio', $fecha)
                        ->where(function ($q) {
                            $q->whereDoesntHave('Detalle')
                              ->orWhereHas('Detalle', function ($q2) {
                                  $q2->where('despachado', 0);
                              });
                        })->get();
                }

                return view('envios.lista_productos', [
                    'envios' => $envios,
                    'fecha' => $fecha
                ]);
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
        return redirect()->route('dashboard');
    }

    public function excel(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $ids = $request->query('ids');
        $query = EnvioProvincia::with(['Cliente', 'Agencia', 'Destino', 'SubAgencia', 'Productos.Producto', 'Detalle.Receptor', 'Dimension.tipoPaquete'])
            ->whereHas('Agencia', function ($q) {
                $q->where('nombre', 'LIKE', '%SHALOM%');
            });

        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
        } else {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $envios = $query->whereDate('fecha_envio', $fecha)
                ->where(function ($q) {
                    $q->whereDoesntHave('Detalle')
                      ->orWhereHas('Detalle', function ($q2) {
                          $q2->where('despachado', 0);
                      });
                })->get();
        }

        // Cargar la plantilla oficial de Shalom (preserva metadata, versión, hojas ocultas, validaciones)
        $templatePath = storage_path('Formato-Pro-Masivo-2026_06_12_13.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Plantilla de Shalom no encontrada. Coloque el archivo en storage/');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet(0); // Hoja1

        // Limpiar fila de ejemplo (fila 2) que trae la plantilla
        for ($col = 1; $col <= 13; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '2', '');
        }

        // Cargar nombres de subagencias de Shalom desde la Hoja2 del Excel cargado para mapeo exacto
        $shalomNamesMap = [];
        $normalize = function ($str) {
            $str = mb_strtoupper($str, 'UTF-8');
            $unwanted_array = [
                'Š' => 'S',
                'š' => 's',
                'Ž' => 'Z',
                'ž' => 'z',
                'À' => 'A',
                'Á' => 'A',
                'Â' => 'A',
                'Ã' => 'A',
                'Ä' => 'A',
                'Å' => 'A',
                'Æ' => 'A',
                'Ç' => 'C',
                'È' => 'E',
                'É' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Ì' => 'I',
                'Í' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ñ' => 'N',
                'Ò' => 'O',
                'Ó' => 'O',
                'Ô' => 'O',
                'Õ' => 'O',
                'Ö' => 'O',
                'Ø' => 'O',
                'Ù' => 'U',
                'Ú' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'Ý' => 'Y',
                'Þ' => 'B',
                'ß' => 'Ss',
                'à' => 'a',
                'á' => 'a',
                'â' => 'a',
                'ã' => 'a',
                'ä' => 'a',
                'å' => 'a',
                'æ' => 'a',
                'ç' => 'c',
                'è' => 'e',
                'é' => 'e',
                'â' => 'e',
                'ë' => 'e',
                'ì' => 'i',
                'í' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ð' => 'o',
                'ñ' => 'n',
                'ò' => 'o',
                'ó' => 'o',
                'ô' => 'o',
                'õ' => 'o',
                'ö' => 'o',
                'ø' => 'o',
                'ù' => 'u',
                'ú' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'þ' => 'b',
                'ÿ' => 'y'
            ];
            $str = strtr($str, $unwanted_array);
            $str = preg_replace('/\s+/', ' ', $str);
            return trim($str);
        };

        if ($spreadsheet->getSheetCount() > 1) {
            $sheet2 = $spreadsheet->getSheet(1); // Hoja2
            $highestRow = $sheet2->getHighestRow();
            for ($r = 1; $r <= $highestRow; $r++) {
                $nameVal = $sheet2->getCell('B' . $r)->getValue();
                if ($nameVal) {
                    $trimmed = trim($nameVal);
                    if ($trimmed !== '') {
                        $normalized = $normalize($trimmed);
                        $shalomNamesMap[$normalized] = $trimmed;
                    }
                }
            }
        }

        // Escribir datos de envíos
        $row = 2;
        foreach ($envios as $envio) {
            // Shalom exige que MERCADERIA sea un valor de su lista desplegable, no el nombre del producto
            // Valores válidos: SOBRE, PAQUETE XXS, PAQUETE XS, PAQUETE S, PAQUETE M, PAQUETE L
            $mercaderia = 'PAQUETE L'; // Default fallback

            if (optional(optional($envio->Dimension)->tipoPaquete)->nombre) {
                $nombreTipo = strtoupper(trim($envio->Dimension->tipoPaquete->nombre));
                // Valid Shalom options, sorted from most specific to least specific to avoid partial matches
                $validos = ['SOBRE', 'PAQUETE XXS', 'PAQUETE XS', 'PAQUETE S', 'PAQUETE M', 'PAQUETE L'];
                foreach ($validos as $valido) {
                    if (strpos($nombreTipo, $valido) !== false) {
                        $mercaderia = $valido;
                        break;
                    }
                }
            }

            // Por solicitud del usuario, la cantidad en el Excel masivo SIEMPRE debe ser 1
            $cantidad = 1;

            $destinoRaw = optional($envio->SubAgencia)->nombre_oficina ?? optional($envio->Destino)->nombre ?? '';
            $partes = explode(' / ', $destinoRaw);
            $destino = trim(end($partes));
            $zona = trim(optional($envio->Destino)->nombre ?? '');

            // Shalom a veces añade " - NOMBRE_ZONA" al final del nombre de la sucursal en su API, pero en su Excel lo omiten.
            $suffix = " - " . $zona;
            if (!empty($zona) && substr($destino, -strlen($suffix)) === $suffix) {
                $destino = trim(substr($destino, 0, -strlen($suffix)));
            }

            // Intentar buscar match exacto o parcial en la lista de shalom_excel.txt
            if (!empty($shalomNamesMap)) {
                $destinoNorm = $normalize($destino);
                if (isset($shalomNamesMap[$destinoNorm])) {
                    $destino = $shalomNamesMap[$destinoNorm];
                } else {
                    foreach ($shalomNamesMap as $normKey => $exactValue) {
                        if (strpos($normKey, $destinoNorm) !== false || strpos($destinoNorm, $normKey) !== false) {
                            $destino = $exactValue;
                            break;
                        }
                    }
                }
            }
            
            $receptor = optional(optional($envio->Detalle)->Receptor);
            $dniDestino = $receptor->dni ?? optional($envio->Cliente)->numeroDocumento ?? '';
            $telefonoDestino = $receptor->telefono ?? optional($envio->Cliente)->telefono ?? '';
            $nombreDestino = $receptor->nombre ?? optional($envio->Cliente)->nombres ?? '';

            $sheet->setCellValueExplicit('A' . $row, $dniDestino, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $telefonoDestino, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, ''); // CONTACTO (DOC)
            $sheet->setCellValue('D' . $row, ''); // TELF. CONTACTO
            $sheet->setCellValue('E' . $row, $envio->numero_guia ?? ''); // NRO GRR
            $sheet->setCellValue('F' . $row, 'JR. RAYMONDI'); // ORIGEN
            $sheet->setCellValue('G' . $row, $destino); // DESTINO
            $sheet->setCellValue('H' . $row, $mercaderia); // MERCADERIA
            $sheet->setCellValue('I' . $row, floatval(optional($envio->Dimension)->alto_final ?? optional($envio->Detalle)->alto ?? 0.1));
            $sheet->setCellValue('J' . $row, floatval(optional($envio->Dimension)->ancho_final ?? optional($envio->Detalle)->ancho ?? 0.1));
            $sheet->setCellValue('K' . $row, floatval(optional($envio->Dimension)->largo_final ?? optional($envio->Detalle)->largo ?? 0.1));
            $sheet->setCellValue('L' . $row, floatval(optional($envio->Dimension)->peso_final ?? optional($envio->Detalle)->peso ?? 7));
            $sheet->setCellValue('M' . $row, intval($cantidad));

            $row++;
        }

        // Generar archivo .xlsx (mismo formato que la plantilla original)
        $filename = 'Formato-Pro-Masivo-' . date('Y_m_d_H') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'shalom_excel_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function etiquetas(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $hasAccess = false;
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 12) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ver esta sección', 'warning', 'btn-danger');
            return redirect()->route('dashboard');
        }

        $ids = $request->query('ids');
        $fecha = $request->query('fecha', date('Y-m-d'));
        $query = EnvioProvincia::with(['Cliente', 'Agencia', 'Destino.Provincia.Departamento', 'SubAgencia', 'Productos.Producto', 'Detalle']);

        if (!empty($ids)) {
            $idArray = explode(',', $ids);
            $envios = $query->whereIn('idEnvioProvincia', $idArray)->get();
        } else {
            $envios = $query->whereDate('fecha_envio', $fecha)
                ->where(function ($q) {
                    $q->whereDoesntHave('Detalle')
                      ->orWhereHas('Detalle', function ($q2) {
                          $q2->where('despachado', 0);
                      });
                })->get();
        }

        return view('envios.etiquetas2', compact('envios', 'fecha'));
    }

}
