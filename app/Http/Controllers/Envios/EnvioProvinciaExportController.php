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
        $templatePath = storage_path('Formato-Pro-Masivo-2026_10_23_14.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'Plantilla de Shalom no encontrada. Coloque el archivo en storage/');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet(0); // Hoja1

        // Limpiar fila de ejemplo (fila 2) que trae la plantilla
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'M'] as $colLetter) {
            $sheet->setCellValue($colLetter . '2', '');
        }
        $sheet->setCellValue('I2', '=IF(H2="SOBRE",0.1,IF(H2="PAQUETE XXS",0.15,IF(H2="PAQUETE XS",0.2,IF(H2="PAQUETE S",0.3,IF(H2="PAQUETE M",0.3,IF(H2="PAQUETE L",0.42,""))))))');
        $sheet->setCellValue('J2', '=IF(H2="SOBRE",0.1,IF(H2="PAQUETE XXS",0.1,IF(H2="PAQUETE XS",0.15,IF(H2="PAQUETE S",0.3,IF(H2="PAQUETE M",0.24,IF(H2="PAQUETE L",0.3,""))))))');
        $sheet->setCellValue('K2', '=IF(H2="SOBRE",0.15,IF(H2="PAQUETE XXS",0.1,IF(H2="PAQUETE XS",0.12,IF(H2="PAQUETE S",0.12,IF(H2="PAQUETE M",0.2,IF(H2="PAQUETE L",0.23,""))))))');
        $sheet->setCellValue('L2', '=IF(H2="SOBRE",0,IF(H2="PAQUETE XXS",0.25,IF(H2="PAQUETE XS",0.5,IF(H2="PAQUETE S",2,IF(H2="PAQUETE M",5,IF(H2="PAQUETE L",10,""))))))');

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
                'ê' => 'e',
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

        $sheet2 = $spreadsheet->getSheetByName('Hoja2') ?? ($spreadsheet->getSheetCount() > 1 ? $spreadsheet->getSheet($spreadsheet->getSheetCount() - 1) : null);
        $maxRowB = 513;
        $maxRowC = 501;
        if ($sheet2) {
            $highestRow = $sheet2->getHighestRow();
            $maxRowB = 0;
            $maxRowC = 0;
            for ($r = 1; $r <= $highestRow; $r++) {
                $valB = $sheet2->getCell('B' . $r)->getValue();
                if ($valB !== null && trim((string)$valB) !== '') {
                    $maxRowB = $r;
                    $trimmedB = trim((string)$valB);
                    $shalomNamesMap[$normalize($trimmedB)] = $trimmedB;
                }
                $valC = $sheet2->getCell('C' . $r)->getValue();
                if ($valC !== null && trim((string)$valC) !== '') {
                    $maxRowC = $r;
                    $trimmedC = trim((string)$valC);
                    $shalomNamesMap[$normalize($trimmedC)] = $trimmedC;
                }
            }
        }

        // Equivalencias de sedes renombradas en el nuevo formato de Shalom
        $renamedTerminals = [
            'JR AGUILAR' => 'JR MAYRO',
            'HUARAZ' => 'AV. 27 DE NOVIEMBRE CO',
            'HUARAZ CO' => 'AV. 27 DE NOVIEMBRE CO',
            'HUANCAVELICA' => 'BARRIO STA ANA CO',
            'MARISCAL NIETO' => 'CALLE TACNA',
            'UCHUMAYO' => 'CONGATA',
            'IQUITOS CO JR. PABLO ROSSELL' => 'IQUITOS CO JR. BOLOGNESI',
            'IQUITOS JR FRANCISCO BOLOGNESI' => 'IQUITOS JR PABLO ROSSEL',
            'JAEN' => 'JAEN CO',
            'OVALO ORQUIDEAS CO' => 'MOYOBAMBA  CO',
            'SICUANI CO OVALO SAN ANDRES' => 'SICUANI AV CONFEDERACION CO',
            'AV BERTELLO SMP' => 'SMP AV BERTELLO',
            'TAMBOPATA AV LA JOYA CO' => 'TAMBOPATA AV UCAYALI  CO',
            'JR CESAR VALLEJO' => 'URB UNIVERSAL',
            'AV PARRA 379 CO' => 'AV PARRA 379',
        ];

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
            // El formato de nombre_oficina en Shalom es "DEPARTAMENTO / PROVINCIA / DISTRITO / NOMBRE_SEDE"
            // Usamos límite 4 por si el nombre de la sede contiene " / " (ej. "AV LOS PRECURSORES / LAS AMERICAS")
            $partes = explode(' / ', $destinoRaw, 4);
            $destino = trim(end($partes));
            $zona = trim(optional($envio->Destino)->nombre ?? '');

            $destinoNorm = $normalize($destino);
            if (isset($renamedTerminals[$destinoNorm])) {
                $destino = $renamedTerminals[$destinoNorm];
                $destinoNorm = $normalize($destino);
            }

            // Intentar buscar match exacto antes de recortar sufijo de zona (ej. "CACHIMAYO - SAN SEBASTIAN")
            if (!empty($shalomNamesMap) && isset($shalomNamesMap[$destinoNorm])) {
                $destino = $shalomNamesMap[$destinoNorm];
            } else {
                // Shalom a veces añade " - NOMBRE_ZONA" al final del nombre de la sucursal en su API, pero en su Excel lo omiten.
                $suffix = " - " . $zona;
                if (!empty($zona) && substr($destino, -strlen($suffix)) === $suffix) {
                    $destino = trim(substr($destino, 0, -strlen($suffix)));
                }
                $destinoNorm = $normalize($destino);
                if (isset($renamedTerminals[$destinoNorm])) {
                    $destino = $renamedTerminals[$destinoNorm];
                    $destinoNorm = $normalize($destino);
                }

                if (!empty($shalomNamesMap)) {
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
            $alto  = optional($envio->Dimension)->alto_final ?? optional($envio->Detalle)->alto;
            $ancho = optional($envio->Dimension)->ancho_final ?? optional($envio->Detalle)->ancho;
            $largo = optional($envio->Dimension)->largo_final ?? optional($envio->Detalle)->largo;
            $peso  = optional($envio->Dimension)->peso_final ?? optional($envio->Detalle)->peso;

            if ($alto !== null && $ancho !== null && $largo !== null && $peso !== null) {
                $sheet->setCellValue('I' . $row, floatval($alto));
                $sheet->setCellValue('J' . $row, floatval($ancho));
                $sheet->setCellValue('K' . $row, floatval($largo));
                $sheet->setCellValue('L' . $row, floatval($peso));
            } else {
                $sheet->setCellValue('I' . $row, '=IF(H'.$row.'="SOBRE",0.1,IF(H'.$row.'="PAQUETE XXS",0.15,IF(H'.$row.'="PAQUETE XS",0.2,IF(H'.$row.'="PAQUETE S",0.3,IF(H'.$row.'="PAQUETE M",0.3,IF(H'.$row.'="PAQUETE L",0.42,""))))))');
                $sheet->setCellValue('J' . $row, '=IF(H'.$row.'="SOBRE",0.1,IF(H'.$row.'="PAQUETE XXS",0.1,IF(H'.$row.'="PAQUETE XS",0.15,IF(H'.$row.'="PAQUETE S",0.3,IF(H'.$row.'="PAQUETE M",0.24,IF(H'.$row.'="PAQUETE L",0.3,""))))))');
                $sheet->setCellValue('K' . $row, '=IF(H'.$row.'="SOBRE",0.15,IF(H'.$row.'="PAQUETE XXS",0.1,IF(H'.$row.'="PAQUETE XS",0.12,IF(H'.$row.'="PAQUETE S",0.12,IF(H'.$row.'="PAQUETE M",0.2,IF(H'.$row.'="PAQUETE L",0.23,""))))))');
                $sheet->setCellValue('L' . $row, '=IF(H'.$row.'="SOBRE",0,IF(H'.$row.'="PAQUETE XXS",0.25,IF(H'.$row.'="PAQUETE XS",0.5,IF(H'.$row.'="PAQUETE S",2,IF(H'.$row.'="PAQUETE M",5,IF(H'.$row.'="PAQUETE L",10,""))))))');
            }
            $sheet->setCellValue('M' . $row, intval($cantidad));

            $row++;
        }

        // Asegurar validaciones desplegables de ORIGEN (F) y DESTINO (G) hacia Hoja2
        if ($sheet2 && $maxRowB > 0 && $maxRowC > 0) {
            $validationRows = max(51, $row);
            for ($vRow = 2; $vRow <= $validationRows; $vRow++) {
                $dvF = $sheet->getCell('F' . $vRow)->getDataValidation();
                $dvF->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $dvF->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $dvF->setAllowBlank(false);
                $dvF->setShowInputMessage(false);
                $dvF->setShowErrorMessage(true);
                $dvF->setShowDropDown(true);
                $dvF->setErrorTitle('Error de escritura');
                $dvF->setError('Valor fuera de lista');
                $dvF->setFormula1("'Hoja2'!B1:B" . $maxRowB);

                $dvG = $sheet->getCell('G' . $vRow)->getDataValidation();
                $dvG->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $dvG->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $dvG->setAllowBlank(false);
                $dvG->setShowInputMessage(false);
                $dvG->setShowErrorMessage(true);
                $dvG->setShowDropDown(true);
                $dvG->setErrorTitle('Error de escritura');
                $dvG->setError('Valor fuera de lista');
                $dvG->setFormula1("'Hoja2'!C2:C" . $maxRowC);
            }
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
