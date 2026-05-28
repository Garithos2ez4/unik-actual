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
    protected $ventaService;

    public function __construct(EgresoProductoServiceInterface $egresoService, \App\Services\VentaServiceInterface $ventaService = null)
    {
        $this->egresoService = $egresoService;
        $this->ventaService = $ventaService;
    }

    public function collection(Collection $rows)
    {
        Log::info("Iniciando importación. Total filas encontradas: " . $rows->count());

        $ordenes = [];
        $sinOrdenCount = 0;

        // 1. Agrupar filas por Número de Orden
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();

            if ($index === 0) {
                Log::info("Fila 0 (Data) detectada: " . json_encode($rowArray));
            }

            $movimiento  = $this->getVal($rowArray, 'movimiento', 4);
            if (empty($movimiento) || strtolower(trim((string)$movimiento)) !== 'egreso') {
                continue;
            }

            $numeroOrden = $this->getVal($rowArray, 'orden', 8);
            if (!empty($numeroOrden)) {
                if (is_numeric($numeroOrden)) {
                    $numeroOrden = number_format($numeroOrden, 0, '', '');
                }
                $numeroOrden = trim((string)$numeroOrden);
            }

            $grupoKey = !empty($numeroOrden) ? $numeroOrden : 'sin_orden_' . ($sinOrdenCount++);

            $ordenes[$grupoKey][] = [
                'index' => $index,
                'row' => $rowArray
            ];
        }

        // 2. Procesar cada grupo de Orden
        foreach ($ordenes as $ordenKey => $filasOrden) {
            $itemsParaEgreso = [];
            $detallesVentaData = [];

            $primeraFila = $filasOrden[0]['row'];
            $fechaVenta = $this->transformDate($this->getVal($primeraFila, 'fecha', 0));
            $numeroOrdenStr = strpos($ordenKey, 'sin_orden_') === 0 ? null : $ordenKey;

            // Mapeo de canal de venta (FB, ML, RIPLEY)
            $plataformaStr = strtoupper(trim((string)$this->getVal($primeraFila, 'plataforma', 6)));
            if (str_starts_with($plataformaStr, 'FB') || str_contains($plataformaStr, 'FALABELLA')) {
                $canal = 'FALABELLA';
            } elseif (str_starts_with($plataformaStr, 'ML') || str_contains($plataformaStr, 'MERCADO')) {
                $canal = 'MERCADOLIBRE';
            } elseif (str_starts_with($plataformaStr, 'RIPLEY')) {
                $canal = 'RIPLEY';
            } else {
                $canal = 'TIENDA';
            }

            $ventaData = [
                'idCliente' => null,
                'numeroOrden' => $numeroOrdenStr,
                'fechaVenta' => $fechaVenta,
                'canal' => $canal
            ];

            foreach ($filasOrden as $filaInfo) {
                $rowArray = $filaInfo['row'];
                $index = $filaInfo['index'];

                $numeroSerie = $this->getVal($rowArray, 'series', 7);
                $sku         = $this->getVal($rowArray, 'sku', 9);
                $numeroSerie = $numeroSerie ? trim((string)$numeroSerie) : null;
                $sku         = $sku ? trim((string)$sku) : null;
                $cantidad    = $this->getVal($rowArray, 'un', 3) ?? 1;
                $nombreProducto = $this->getVal($rowArray, 'producto', 2);
                $almacenNombre  = $this->getVal($rowArray, 'almacen', 5);
                $fechaDespacho  = $this->transformDate($this->getVal($rowArray, 'fecha', 0));

                if (empty($numeroSerie)) {
                    if (empty($nombreProducto)) {
                        Log::warning("Fila $index saltada: No tiene Serie ni Nombre de Producto.");
                        continue;
                    }

                    $almacenBusqueda = str_replace(['De ', 'de '], '', $almacenNombre);
                    $almacen = \App\Models\Almacen::where('descripcion', 'LIKE', "%$almacenBusqueda%")->first();
                    $idAlmacen = $almacen ? $almacen->idAlmacen : null;

                    $query = RegistroProducto::join('DetalleComprobante', 'RegistroProducto.idDetalleComprobante', '=', 'DetalleComprobante.idDetalleComprobante')
                        ->join('Producto', 'DetalleComprobante.idProducto', '=', 'Producto.idProducto')
                        ->where(function ($q) use ($nombreProducto) {
                            $q->where('Producto.nombreProducto', $nombreProducto)
                                ->orWhere('Producto.modelo', $nombreProducto);
                        })
                        ->where('RegistroProducto.estado', 'NUEVO');

                    if ($idAlmacen) {
                        $query->where('RegistroProducto.idAlmacen', $idAlmacen);
                    }

                    $registrosDisponibles = $query->select('RegistroProducto.*')->take($cantidad)->get();

                    if ($registrosDisponibles->isEmpty()) {
                        Log::warning("Fila $index: No se encontró stock para '$nombreProducto' en almacén '$almacenNombre'");
                        continue;
                    }

                    foreach ($registrosDisponibles as $registro) {
                        $idPublicacion = null;
                        if (!empty($sku) && strtolower($sku) !== 'no aplica') {
                            $publicacion = Publicacion::where('sku', $sku)->first();
                            if ($publicacion) $idPublicacion = $publicacion->idPublicacion;
                        }

                        $itemsParaEgreso[] = [
                            'idregistro' => $registro->idRegistro,
                            'idpublicacion' => $idPublicacion
                        ];

                        $detallesVentaData[] = [
                            'idRegistro' => $registro->idRegistro,
                            'idPublicacion' => $idPublicacion,
                            'idProducto' => $registro->DetalleComprobante->idProducto ?? null,
                            'precioVenta' => '', // '' hace que herede auto en el service de venta
                            'cantidad' => 1
                        ];
                    }
                } else {
                    $seriesArray = explode(',', $numeroSerie);
                    foreach ($seriesArray as $serieIndividual) {
                        $serieIndividual = trim($serieIndividual);
                        if (empty($serieIndividual)) continue;

                        $registro = RegistroProducto::where('numeroSerie', $serieIndividual)
                            ->where('estado', 'NUEVO')
                            ->first();

                        if ($registro) {
                            $idPublicacion = null;
                            if (!empty($sku) && strtolower($sku) !== 'no aplica') {
                                $publicacion = Publicacion::where('sku', $sku)->first();
                                if ($publicacion) $idPublicacion = $publicacion->idPublicacion;
                            }

                            $itemsParaEgreso[] = [
                                'idregistro' => $registro->idRegistro,
                                'idpublicacion' => $idPublicacion
                            ];

                            $detallesVentaData[] = [
                                'idRegistro' => $registro->idRegistro,
                                'idPublicacion' => $idPublicacion,
                                'idProducto' => $registro->DetalleComprobante->idProducto ?? null,
                                'precioVenta' => '', // Se hereda automáticamente
                                'cantidad' => 1
                            ];
                        } else {
                            Log::warning("Fila $index: Serie $serieIndividual no está NUEVA o no existe.");
                        }
                    }
                }
            }

            // Realizar los guardados por Grupo
            if (count($itemsParaEgreso) > 0) {
                try {
                    $arrayEgreso = ['numeroOrden' => $numeroOrdenStr, 'fechaCompra' => $fechaVenta, 'fechaDespacho' => $fechaVenta];
                    $createResult = $this->egresoService->createEgreso($arrayEgreso, $itemsParaEgreso);

                    if ($this->ventaService && isset($createResult['egresos'])) {
                        $egresosGenerados = $createResult['egresos'];
                        foreach ($detallesVentaData as &$dv) {
                            $dv['idEgreso'] = $egresosGenerados[$dv['idRegistro']] ?? null;
                        }
                        $this->ventaService->createVenta($ventaData, $detallesVentaData);
                        Log::info("Orden $ordenKey: Venta generada con " . count($detallesVentaData) . " detalles.");
                    }
                } catch (\Exception $e) {
                    Log::error("Orden $ordenKey: Error procesando grupo - " . $e->getMessage());
                }
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
