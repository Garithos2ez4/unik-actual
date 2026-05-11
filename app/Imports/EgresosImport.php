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

class EgresosImport implements ToCollection, WithHeadingRow
{
    protected $egresoService;

    public function __construct(EgresoProductoServiceInterface $egresoService)
    {
        $this->egresoService = $egresoService;
    }

    public function collection(Collection $rows)
    {
        Log::info("Iniciando importación. Total filas encontradas: " . $rows->count());

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            
            // Debug inicial para la primera fila de datos
            if ($index === 0) {
                Log::info("Fila 0 (Data) detectada: " . json_encode($rowArray));
            }

            // Intentamos obtener valores por nombre de columna (slug) o por índice físico
            // Basado en el formato: A=0(Fecha), E=4(Movimiento), H=7(SERIES), I=8(Orden), J=9(SKU)
            
            $movimiento  = $this->getVal($rowArray, 'movimiento', 4);
            $numeroSerie = $this->getVal($rowArray, 'series', 7);
            $numeroOrden = $this->getVal($rowArray, 'orden', 8);
            $sku         = $this->getVal($rowArray, 'sku', 9);
            $fecha       = $this->getVal($rowArray, 'fecha', 0);

            // Limpieza de datos
            $movimiento  = $movimiento ? trim((string)$movimiento) : null;
            $numeroSerie = $numeroSerie ? trim((string)$numeroSerie) : null;
            $sku         = $sku ? trim((string)$sku) : null;

            // Manejo especial para Números de Orden en formato científico (ej: 2.0E+15)
            if (!empty($numeroOrden)) {
                if (is_numeric($numeroOrden)) {
                    $numeroOrden = number_format($numeroOrden, 0, '', '');
                }
                $numeroOrden = trim((string)$numeroOrden);
            }

            // Validamos que sea un Egreso
            if (empty($movimiento) || strtolower($movimiento) !== 'egreso') {
                continue; // Si no es egreso, ignoramos silenciosamente
            }

            if (empty($numeroOrden) || empty($numeroSerie)) {
                Log::warning("Fila $index saltada: Faltan datos críticos (Serie: '$numeroSerie', Orden: '$numeroOrden')");
                continue;
            }

            $fechaDespacho = $this->transformDate($fecha);
            $registro = RegistroProducto::where('numeroSerie', $numeroSerie)->first();

            if ($registro) {
                // Solo egresamos si está NUEVO
                if ($registro->estado !== 'NUEVO') {
                    Log::warning("Fila $index saltada: La serie $numeroSerie ya no está NUEVA (Estado actual: {$registro->estado})");
                    continue;
                }

                // Buscamos la publicación por SKU si la hay
                $idPublicacion = null;
                if (!empty($sku) && strtolower($sku) !== 'no aplica') {
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
                    Log::info("Fila $index: Egreso exitoso para Serie $numeroSerie");
                } catch (\Exception $e) {
                    Log::error("Fila $index: Error al procesar serie {$numeroSerie}: " . $e->getMessage());
                }

            } else {
                Log::warning("Fila $index saltada: Serie no encontrada en base de datos: " . $numeroSerie);
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

    private function getVal($row, $key, $index)
    {
        // Primero intentamos por la llave (slug)
        if (isset($row[$key]) && !is_null($row[$key]) && $row[$key] !== '') {
            return $row[$key];
        }
        // Luego intentamos por el índice físico (A=0, B=1, etc)
        $values = array_values($row);
        if (isset($values[$index]) && !is_null($values[$index]) && $values[$index] !== '') {
            return $values[$index];
        }
        return null;
    }
}
