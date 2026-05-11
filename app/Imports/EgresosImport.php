<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\RegistroProducto;
use App\Models\Publicacion;
use App\Services\EgresoProductoServiceInterface;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EgresosImport implements ToCollection
{
    protected $egresoService;

    public function __construct(EgresoProductoServiceInterface $egresoService)
    {
        $this->egresoService = $egresoService;
    }

    public function collection(Collection $rows)
    {
        Log::info("Iniciando importación. Total filas encontradas: " . $rows->count());

        $contadorDebug = 0;
        foreach ($rows as $index => $row) {
            // Columna B = 1, F = 5, I = 8, J = 9, K = 10
            $fecha       = isset($row[1]) ? trim((string)$row[1]) : null;
            $movimiento  = isset($row[5]) ? trim((string)$row[5]) : null;
            $numeroSerie = isset($row[8]) ? trim((string)$row[8]) : null;
            $numeroOrden = isset($row[9]) ? trim((string)$row[9]) : null;
            $sku         = isset($row[10]) ? trim((string)$row[10]) : null;

            if ($contadorDebug < 5 && strtolower($movimiento) === 'egreso') {
                Log::info("¡Egreso encontrado! Row $index -> Serie: $numeroSerie | Orden: $numeroOrden");
                $contadorDebug++;
            }

            // Validamos que sea un Egreso y tenga orden y serie
            if (empty($numeroOrden) || empty($numeroSerie) || strtolower($movimiento) !== 'egreso') {
                continue;
            }

            $fechaDespacho = $this->transformDate($fecha);

            $registro = RegistroProducto::where('numeroSerie', $numeroSerie)->first();

            if ($registro) {
                // Buscamos la publicación por SKU si la hay
                $idPublicacion = null;
                if (!empty($sku) && $sku !== 'No aplica') {
                    $publicacion = Publicacion::where('sku', $sku)->first();
                    if ($publicacion) {
                        $idPublicacion = $publicacion->idPublicacion;
                    }
                }

                try {
                    $items = [
                        [
                            'idregistro' => $registro->idRegistro,
                            'idpublicacion' => $idPublicacion
                        ]
                    ];
                    
                    $arrayEgreso = [
                        'numeroOrden' => $numeroOrden,
                        'fechaCompra' => $fechaDespacho,
                        'fechaDespacho' => $fechaDespacho
                    ];
                    
                    $this->egresoService->createEgreso($arrayEgreso, $items);
                } catch (\Exception $e) {
                    Log::warning("Error al procesar serie {$numeroSerie}: " . $e->getMessage());
                }

            } else {
                Log::warning("No se pudo egresar. Serie no encontrada: " . $numeroSerie);
            }
        }
    }

    private function getValueByKeyword($row, $keyword)
    {
        foreach ($row as $key => $value) {
            if (is_string($key) && str_contains(strtolower($key), strtolower($keyword))) {
                return $value;
            }
        }
        return null;
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        if (empty($value)) return now()->format($format);
        
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format($format);
            }
            // A veces las fechas vienen con el formato d/m/Y, intentamos parsear
            try {
                return Carbon::createFromFormat('d/m/Y', $value)->format($format);
            } catch (\Exception $e) {
                return Carbon::parse($value)->format($format);
            }
        } catch (\Exception $e) {
            return now()->format($format);
        }
    }
}
