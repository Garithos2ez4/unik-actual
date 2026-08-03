<?php

namespace App\Services;

use App\Models\Inventario\DivisionPack;
use App\Models\Inventario\DivisionPackDetalle;
use App\Models\Catalogo\ProductoPack;
use App\Models\Inventario\RegistroProducto;
use App\Models\Ventas\DetalleComprobante;
use App\Models\Inventario\IngresoProducto;
use App\Models\Inventario\Inventario;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class UnionPackService implements UnionPackServiceInterface
{
    protected $inventarioRepository;
    protected $registroRepository;
    protected $headerService;
    protected $productoService;

    public function __construct(
        InventarioRepositoryInterface $inventarioRepository,
        RegistroProductoRepositoryInterface $registroRepository,
        HeaderServiceInterface $headerService,
        ProductoServiceInterface $productoService
    ) {
        $this->inventarioRepository = $inventarioRepository;
        $this->registroRepository = $registroRepository;
        $this->headerService = $headerService;
        $this->productoService = $productoService;
    }

    public function reunirPack($idProductoPack, array $idRegistrosHijos)
    {
        DB::beginTransaction();
        try {
            // 1. Validar que el producto es un pack
            $packDefinicion = ProductoPack::where('idProductoPack', $idProductoPack)->get();
            if ($packDefinicion->isEmpty()) {
                throw new Exception("El producto no es un pack válido.");
            }

            // 2. Validar que se proporcionaron la cantidad correcta de hijos
            $totalHijosNecesarios = $packDefinicion->sum('cantidad');
            if (count($idRegistrosHijos) !== $totalHijosNecesarios) {
                throw new Exception("Se necesitan exactamente {$totalHijosNecesarios} componentes para reunir el pack.");
            }

            // 3. Validar que cada registro hijo está en estado NUEVO y pertenece al producto correcto
            $registrosHijos = [];
            $serieComun = null;
            $idAlmacen = null;

            foreach ($idRegistrosHijos as $idRegHijo) {
                $regHijo = RegistroProducto::with('DetalleComprobante.Producto')->findOrFail($idRegHijo);

                if ($regHijo->estado !== 'NUEVO') {
                    throw new Exception("El componente con serie {$regHijo->numeroSerie} no está en estado NUEVO.");
                }

                // Verificar que es un producto hijo válido del pack
                $idProductoHijo = $regHijo->DetalleComprobante->idProducto;
                $esHijoValido = $packDefinicion->where('idProductoHijo', $idProductoHijo)->isNotEmpty();
                if (!$esHijoValido) {
                    throw new Exception("El producto '{$regHijo->DetalleComprobante->Producto->nombreProducto}' no es un componente válido de este pack.");
                }

                // Verificar que todos tienen la misma serie (opcional, para coherencia)
                if ($serieComun === null) {
                    $serieComun = $regHijo->numeroSerie;
                    $idAlmacen = $regHijo->idAlmacen;
                }

                $registrosHijos[] = $regHijo;
            }

            $idUser = $this->headerService->getModelUser()->idUser;

            // 4. Buscar si existe un registro pack DIVIDIDO con la misma serie
            $registroPack = RegistroProducto::where('numeroSerie', $serieComun)
                ->where('estado', 'DIVIDIDO')
                ->whereHas('DetalleComprobante', function ($q) use ($idProductoPack) {
                    $q->where('idProducto', $idProductoPack);
                })
                ->first();

            if ($registroPack) {
                // 5a. Re-activar el registro del pack original
                $registroPack->estado = 'NUEVO';
                $registroPack->fechaMovimiento = now();
                $registroPack->observacion = "Pack reunido desde componentes individuales";
                $registroPack->save();

                // Sumar stock del pack
                $this->inventarioRepository->addStock($idProductoPack, $registroPack->idAlmacen);
            } else {
                // 5b. Si no existe, crear un nuevo registro de pack
                $detallePack = DetalleComprobante::whereHas('Producto', function ($q) use ($idProductoPack) {
                    $q->where('idProducto', $idProductoPack);
                })->first();

                if (!$detallePack) {
                    throw new Exception("No se encontró un DetalleComprobante para el producto pack.");
                }

                // Crear nuevo DetalleComprobante para el pack
                $lastDc = DetalleComprobante::orderBy('idDetalleComprobante', 'desc')->first();
                $newIdDc = $lastDc ? $lastDc->idDetalleComprobante + 1 : 1;

                // Sumar precios de los hijos para el precio del pack
                $precioPackTotal = 0;
                foreach ($registrosHijos as $rh) {
                    $precioPackTotal += $rh->DetalleComprobante->precioUnitario;
                }

                $comprobante = $registrosHijos[0]->DetalleComprobante->Comprobante;

                DetalleComprobante::create([
                    'idDetalleComprobante' => $newIdDc,
                    'idComprobante' => $comprobante->idComprobante,
                    'idProducto' => $idProductoPack,
                    'cantidad' => 1,
                    'medida' => 'UND',
                    'precioUnitario' => $precioPackTotal,
                    'precioCompra' => $precioPackTotal
                ]);

                $lastRegistro = RegistroProducto::orderBy('idRegistro', 'desc')->first();
                $newIdRegistro = $lastRegistro ? $lastRegistro->idRegistro + 1 : 1;

                $registroPack = RegistroProducto::create([
                    'idRegistro' => $newIdRegistro,
                    'idDetalleComprobante' => $newIdDc,
                    'idAlmacen' => $idAlmacen,
                    'numeroSerie' => $serieComun,
                    'estado' => 'NUEVO',
                    'fechaMovimiento' => now(),
                    'observacion' => "Pack reunido desde componentes individuales"
                ]);

                // Crear ingreso
                $lastIngreso = IngresoProducto::orderBy('idIngreso', 'desc')->first();
                $newIdIngreso = $lastIngreso ? $lastIngreso->idIngreso + 1 : 1;

                IngresoProducto::create([
                    'idIngreso' => $newIdIngreso,
                    'idRegistro' => $registroPack->idRegistro,
                    'idUser' => $idUser,
                    'fechaIngreso' => now()
                ]);

                $this->inventarioRepository->addStock($idProductoPack, $idAlmacen);
            }

            // 6. Crear registro de reunión
            $division = DivisionPack::create([
                'idRegistroPack' => $registroPack->idRegistro,
                'idUser' => $idUser,
                'tipo' => 'REUNION',
                'fechaDivision' => now(),
                'observacion' => "Reunión de componentes en pack (Serie: {$serieComun})"
            ]);

            // 7. Desactivar los registros hijos
            foreach ($registrosHijos as $regHijo) {
                DivisionPackDetalle::create([
                    'idDivision' => $division->idDivision,
                    'idRegistroHijo' => $regHijo->idRegistro,
                    'idProductoHijo' => $regHijo->DetalleComprobante->idProducto
                ]);

                // Cambiar estado a REUNIDO
                $regHijo->estado = 'REUNIDO';
                $regHijo->fechaMovimiento = now();
                $regHijo->observacion = "Componente reunido en pack";
                $regHijo->save();

                // Reducir stock del hijo
                $this->inventarioRepository->removeStock(
                    $regHijo->DetalleComprobante->idProducto,
                    $regHijo->idAlmacen
                );

                // Validar estado del producto hijo
                $this->productoService->validateState($regHijo->DetalleComprobante->idProducto);
            }

            // Validar estado del producto pack
            $this->productoService->validateState($idProductoPack, true);

            DB::commit();

            return [
                'success' => true,
                'message' => "Componentes reunidos exitosamente en pack",
                'division' => $division
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    public function getComponentesParaReunion($idProductoPack)
    {
        $packDefinicion = ProductoPack::where('idProductoPack', $idProductoPack)
            ->with('ProductoHijo')
            ->get();

        if ($packDefinicion->isEmpty()) return [];

        // Buscar registros NUEVO de cada producto hijo
        $componentes = [];
        foreach ($packDefinicion as $pack) {
            $registros = RegistroProducto::where('estado', 'NUEVO')
                ->whereHas('DetalleComprobante', function ($q) use ($pack) {
                    $q->where('idProducto', $pack->idProductoHijo);
                })
                ->get();

            $componentes[] = [
                'idProductoHijo' => $pack->idProductoHijo,
                'nombreProducto' => $pack->ProductoHijo->nombreProducto,
                'cantidadNecesaria' => $pack->cantidad,
                'disponibles' => $registros->map(function ($r) {
                    return [
                        'idRegistro' => $r->idRegistro,
                        'numeroSerie' => $r->numeroSerie
                    ];
                })
            ];
        }

        return $componentes;
    }

    // =========================================================================
    // UNIÓN LIBRE: unir componentes sueltos (de distintos registros) en un pack
    // =========================================================================

    public function unirComponentesEnPack(int $idProductoPack, array $idRegistrosHijos, int $idAlmacenDestino): array
    {
        DB::beginTransaction();
        try {
            // 1. Validar que el producto destino tiene definición de pack
            $packDefinicion = ProductoPack::where('idProductoPack', $idProductoPack)
                ->with('ProductoHijo')
                ->get();

            if ($packDefinicion->isEmpty()) {
                throw new Exception("El producto seleccionado no es un pack válido.");
            }

            // 2. Validar cantidad: los registros enviados deben ser exactamente los necesarios
            $totalNecesarios = $packDefinicion->sum('cantidad');
            if (count($idRegistrosHijos) !== $totalNecesarios) {
                throw new Exception("Se necesitan exactamente {$totalNecesarios} componentes para armar este pack. Se recibieron: " . count($idRegistrosHijos));
            }

            // 3. Cargar y validar cada registro hijo
            $registrosHijos = [];
            $costoTotal = 0;

            // Construir mapa de cuántos de cada idProductoHijo se esperan
            $esperados = [];
            foreach ($packDefinicion as $pd) {
                $esperados[$pd->idProductoHijo] = ($esperados[$pd->idProductoHijo] ?? 0) + $pd->cantidad;
            }

            // Contar cuántos de cada idProductoHijo se están enviando
            $recibidos = [];
            foreach ($idRegistrosHijos as $idRegHijo) {
                $regHijo = RegistroProducto::with('DetalleComprobante.Producto')->findOrFail($idRegHijo);

                if ($regHijo->estado !== 'NUEVO') {
                    throw new Exception("El componente con serie '{$regHijo->numeroSerie}' no está en estado NUEVO (estado actual: {$regHijo->estado}).");
                }

                $idProductoHijo = $regHijo->DetalleComprobante->idProducto;

                if (!isset($esperados[$idProductoHijo])) {
                    $nombreHijo = $regHijo->DetalleComprobante->Producto->nombreProducto ?? 'Desconocido';
                    throw new Exception("El producto '{$nombreHijo}' no es un componente válido de este pack.");
                }

                $recibidos[$idProductoHijo] = ($recibidos[$idProductoHijo] ?? 0) + 1;
                $costoTotal += $regHijo->DetalleComprobante->precioUnitario ?? 0;
                $registrosHijos[] = $regHijo;
            }

            // Verificar que no falte ni sobre ningún tipo de componente
            foreach ($esperados as $idPH => $cantEsperada) {
                $cantRecibida = $recibidos[$idPH] ?? 0;
                if ($cantRecibida !== $cantEsperada) {
                    $nombrePH = $packDefinicion->firstWhere('idProductoHijo', $idPH)->ProductoHijo->nombreProducto ?? "ID {$idPH}";
                    throw new Exception("Se esperaban {$cantEsperada} unidades de '{$nombrePH}' pero se recibieron {$cantRecibida}.");
                }
            }

            $idUser = $this->headerService->getModelUser()->idUser;

            // 4. Obtener un comprobante de referencia (del primer hijo)
            $comprobante = $registrosHijos[0]->DetalleComprobante->Comprobante;
            if (!$comprobante) {
                throw new Exception("No se pudo determinar el comprobante de referencia para el pack.");
            }

            // 5. Crear DetalleComprobante para el nuevo pack
            $lastDc = DetalleComprobante::orderBy('idDetalleComprobante', 'desc')->first();
            $newIdDc = $lastDc ? $lastDc->idDetalleComprobante + 1 : 1;

            $detallePackNuevo = DetalleComprobante::create([
                'idDetalleComprobante' => $newIdDc,
                'idComprobante'        => $comprobante->idComprobante,
                'idProducto'           => $idProductoPack,
                'medida'               => 'UND',
                'precioUnitario'       => $costoTotal,
                'precioCompra'         => $costoTotal,
            ]);

            // 6. Crear RegistroProducto del pack
            $lastRegistro = RegistroProducto::orderBy('idRegistro', 'desc')->first();
            $newIdRegistro = $lastRegistro ? $lastRegistro->idRegistro + 1 : 1;

            // Determinar serie: si todos los hijos comparten serie, se hereda; si no, se genera con formato UNK-
            $series = array_unique(array_map(fn($r) => $r->numeroSerie, $registrosHijos));
            
            if (count($series) === 1) {
                $serieResultante = $series[0];
            } else {
                $productoPadre = \App\Models\Catalogo\Producto::find($idProductoPack);
                $modelOrCode = !empty($productoPadre->modelo) ? $productoPadre->modelo : $productoPadre->codigoProducto;
                $cleanedModel = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($modelOrCode));
                $parcialCode = 'UNK-' . $cleanedModel;
                
                $validateCode = \App\Models\Inventario\RegistroProducto::where('numeroSerie', 'like', "%{$parcialCode}%")->count();
                $serieResultante = $parcialCode . '-' . (100000 + $validateCode + 1);
            }

            $registroPack = RegistroProducto::create([
                'idRegistro'          => $newIdRegistro,
                'idDetalleComprobante'=> $newIdDc,
                'idAlmacen'           => $idAlmacenDestino,
                'numeroSerie'         => $serieResultante,
                'estado'              => 'NUEVO',
                'fechaMovimiento'     => now(),
                'observacion'         => 'Pack armado desde componentes individuales (Unión libre)',
            ]);

            // 7. Crear IngresoProducto
            $lastIngreso = IngresoProducto::orderBy('idIngreso', 'desc')->first();
            $newIdIngreso = $lastIngreso ? $lastIngreso->idIngreso + 1 : 1;

            IngresoProducto::create([
                'idIngreso'   => $newIdIngreso,
                'idRegistro'  => $newIdRegistro,
                'idUser'      => $idUser,
                'fechaIngreso'=> now(),
            ]);

            // 8. Sumar stock del pack en inventario
            $this->inventarioRepository->addStock($idProductoPack, $idAlmacenDestino);

            // 9. Registrar el evento en DivisionPack
            $division = DivisionPack::create([
                'idRegistroPack' => $newIdRegistro,
                'idUser'         => $idUser,
                'tipo'           => 'REUNION',
                'fechaDivision'  => now(),
                'observacion'    => 'Unión libre de componentes en pack (Serie: ' . $serieResultante . ')',
            ]);

            // 10. Procesar cada hijo: marcar como REUNIDO, restar stock, guardar detalle
            foreach ($registrosHijos as $regHijo) {
                DivisionPackDetalle::create([
                    'idDivision'    => $division->idDivision,
                    'idRegistroHijo'=> $regHijo->idRegistro,
                    'idProductoHijo'=> $regHijo->DetalleComprobante->idProducto,
                    'costo_asignado'=> $regHijo->DetalleComprobante->precioUnitario ?? 0,
                ]);

                $regHijo->estado           = 'REUNIDO';
                $regHijo->fechaMovimiento  = now();
                $regHijo->observacion      = 'Componente consumido en unión libre de pack';
                $regHijo->save();

                $this->inventarioRepository->removeStock(
                    $regHijo->DetalleComprobante->idProducto,
                    $regHijo->idAlmacen
                );

                $this->productoService->validateState($regHijo->DetalleComprobante->idProducto);
            }

            // 11. Validar estado del producto pack
            $this->productoService->validateState($idProductoPack, true);

            DB::commit();

            return [
                'success'  => true,
                'message'  => 'Pack armado exitosamente desde componentes individuales.',
                'division' => $division,
                'serie'    => $serieResultante,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getPacksDisponiblesParaUnion(): array
    {
        // Obtener todos los productos que tienen definición de pack
        $todosLosPacks = ProductoPack::with('ProductoPadre', 'ProductoHijo')
            ->get()
            ->groupBy('idProductoPack');

        $disponibles = [];

        foreach ($todosLosPacks as $idProductoPack => $componentes) {
            $armable = true;
            $componentesInfo = [];

            foreach ($componentes as $comp) {
                // Contar cuántos RegistroProducto NUEVO tiene este componente
                $stockDisponible = RegistroProducto::where('estado', 'NUEVO')
                    ->whereHas('DetalleComprobante', function ($q) use ($comp) {
                        $q->where('idProducto', $comp->idProductoHijo);
                    })
                    ->count();

                if ($stockDisponible < $comp->cantidad) {
                    $armable = false;
                }

                $productoHijo = $comp->ProductoHijo;
                $componentesInfo[] = [
                    'idProductoHijo'   => $comp->idProductoHijo,
                    'nombreProducto'   => $productoHijo->nombreProducto ?? '',
                    'modelo'           => $productoHijo->modelo ?? '',
                    'cantidadNecesaria'=> $comp->cantidad,
                    'disponible'       => $stockDisponible,
                ];
            }

            if ($armable) {
                $productoPadre = $componentes->first()->ProductoPadre;
                $disponibles[] = [
                    'idProducto'    => $idProductoPack,
                    'nombreProducto'=> $productoPadre->nombreProducto ?? '',
                    'modelo'        => $productoPadre->modelo ?? '',
                    'componentes'   => $componentesInfo,
                ];
            }
        }

        return $disponibles;
    }

    public function getComponentesRequeridosParaUnion(int $idProductoPack): array
    {
        $packDefinicion = ProductoPack::where('idProductoPack', $idProductoPack)
            ->with('ProductoHijo')
            ->get();

        if ($packDefinicion->isEmpty()) return [];

        $componentes = [];

        foreach ($packDefinicion as $pack) {
            $productoHijo = $pack->ProductoHijo;

            // Registros NUEVO disponibles de este componente
            $registros = RegistroProducto::where('estado', 'NUEVO')
                ->whereHas('DetalleComprobante', function ($q) use ($pack) {
                    $q->where('idProducto', $pack->idProductoHijo);
                })
                ->with('DetalleComprobante', 'Almacen')
                ->get();

            $componentes[] = [
                'idProductoHijo'   => $pack->idProductoHijo,
                'nombreProducto'   => $productoHijo->nombreProducto ?? '',
                'modelo'           => $productoHijo->modelo ?? '',
                'cantidadNecesaria'=> $pack->cantidad,
                'disponibles'      => $registros->map(function ($r) {
                    return [
                        'idRegistro'  => $r->idRegistro,
                        'numeroSerie' => $r->numeroSerie,
                        'almacen'     => $r->Almacen->nombreAlmacen ?? 'Sin almacén',
                        'costo'       => $r->DetalleComprobante->precioUnitario ?? 0,
                    ];
                })->values()->toArray(),
            ];
        }

        return $componentes;
    }
}
