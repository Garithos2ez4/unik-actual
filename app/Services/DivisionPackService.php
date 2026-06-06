<?php

namespace App\Services;

use App\Models\DivisionPack;
use App\Models\DivisionPackDetalle;
use App\Models\ProductoPack;
use App\Models\RegistroProducto;
use App\Models\DetalleComprobante;
use App\Models\IngresoProducto;
use App\Models\Inventario;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class DivisionPackService implements DivisionPackServiceInterface
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

    public function dividirPack($idRegistro)
    {
        DB::beginTransaction();
        try {
            // 1. Obtener el registro del pack
            $registroPack = RegistroProducto::with('DetalleComprobante.Producto.packHijos.ProductoHijo')
                ->findOrFail($idRegistro);

            // 2. Validar estado
            if ($registroPack->estado !== 'NUEVO') {
                throw new Exception("Solo se pueden dividir packs en estado NUEVO. Estado actual: {$registroPack->estado}");
            }

            $producto = $registroPack->DetalleComprobante->Producto;

            // 3. Validar que es un pack
            $packHijos = $producto->packHijos;
            if ($packHijos->isEmpty()) {
                throw new Exception("El producto '{$producto->nombreProducto}' no es un pack divisible.");
            }

            $idAlmacen = $registroPack->idAlmacen;
            $seriePack = $registroPack->numeroSerie;
            $idUser = $this->headerService->getModelUser()->idUser;
            $precioUnitarioPack = $registroPack->DetalleComprobante->precioUnitario;
            $comprobante = $registroPack->DetalleComprobante->Comprobante;

            // 4. Determinar si los porcentajes de los hijos suman > 0
            $totalPorcentaje = $packHijos->sum('porcentaje_costo');

            // 5. Cambiar estado del pack a DIVIDIDO
            $registroPack->estado = 'DIVIDIDO';
            $registroPack->fechaMovimiento = now();
            $registroPack->observacion = "Pack dividido en componentes individuales";
            $registroPack->save();

            // 6. Reducir stock del producto pack
            $this->inventarioRepository->removeStock($producto->idProducto, $idAlmacen);

            // 7. Crear la entrada de DivisionPack
            $division = DivisionPack::create([
                'idRegistroPack' => $idRegistro,
                'idUser' => $idUser,
                'tipo' => 'DIVISION',
                'fechaDivision' => now(),
                'observacion' => "División de pack: {$producto->nombreProducto} (Serie: {$seriePack})"
            ]);

            $hijosCreados = [];

            // 8. Para cada hijo definido en el pack, crear registros
            $totalHijos = $packHijos->sum('cantidad');
            
            foreach ($packHijos as $packHijo) {
                // Cálculo del precio unitario para este hijo
                if ($totalPorcentaje > 0) {
                    // Distribución basada en el porcentaje_costo asignado
                    // La "bolsa" total para este tipo de componente es = PrecioPack * (porcentaje / 100)
                    // El costo unitario es = Bolsa / cantidad
                    $bolsaCosto = $precioUnitarioPack * ($packHijo->porcentaje_costo / 100);
                    $precioUnitarioHijo = $packHijo->cantidad > 0 ? round($bolsaCosto / $packHijo->cantidad, 4) : 0;
                } else {
                    // Fallback: Distribución equitativa por componente configurado si no hay porcentajes (50%-50%)
                    $tiposHijos = $packHijos->count();
                    $bolsaCosto = $precioUnitarioPack / $tiposHijos;
                    $precioUnitarioHijo = $packHijo->cantidad > 0 ? round($bolsaCosto / $packHijo->cantidad, 4) : 0;
                }

                for ($i = 0; $i < $packHijo->cantidad; $i++) {
                    $productoHijo = $packHijo->ProductoHijo;

                    // 8a. Crear DetalleComprobante para el hijo
                    $lastDc = DetalleComprobante::orderBy('idDetalleComprobante', 'desc')->first();
                    $newIdDc = $lastDc ? $lastDc->idDetalleComprobante + 1 : 1;

                    $detalleHijo = DetalleComprobante::create([
                        'idDetalleComprobante' => $newIdDc,
                        'idComprobante' => $comprobante->idComprobante,
                        'idProducto' => $productoHijo->idProducto,
                        'medida' => $registroPack->DetalleComprobante->medida ?? 'UND',
                        'precioUnitario' => $precioUnitarioHijo,
                        'precioCompra' => $precioUnitarioHijo
                    ]);

                    // 8b. Crear RegistroProducto con la misma serie del pack
                    $lastRegistro = RegistroProducto::orderBy('idRegistro', 'desc')->first();
                    $newIdRegistro = $lastRegistro ? $lastRegistro->idRegistro + 1 : 1;

                    $registroHijo = RegistroProducto::create([
                        'idRegistro' => $newIdRegistro,
                        'idDetalleComprobante' => $newIdDc,
                        'idAlmacen' => $idAlmacen,
                        'numeroSerie' => $seriePack, // Hereda la serie del pack
                        'estado' => 'NUEVO',
                        'fechaMovimiento' => now(),
                        'observacion' => "Componente de pack dividido: {$producto->nombreProducto}"
                    ]);

                    // 8c. Crear IngresoProducto
                    $lastIngreso = IngresoProducto::orderBy('idIngreso', 'desc')->first();
                    $newIdIngreso = $lastIngreso ? $lastIngreso->idIngreso + 1 : 1;

                    IngresoProducto::create([
                        'idIngreso' => $newIdIngreso,
                        'idRegistro' => $newIdRegistro,
                        'idUser' => $idUser,
                        'fechaIngreso' => now()
                    ]);

                    // 8d. Sumar stock del producto hijo
                    $this->inventarioRepository->addStock($productoHijo->idProducto, $idAlmacen);

                    // 8e. Validar estado del producto hijo
                    $this->productoService->validateState($productoHijo->idProducto, true);

                    // 8f. Registrar detalle de la división con su costo asignado
                    DivisionPackDetalle::create([
                        'idDivision' => $division->idDivision,
                        'idRegistroHijo' => $newIdRegistro,
                        'idProductoHijo' => $productoHijo->idProducto,
                        'costo_asignado' => $precioUnitarioHijo
                    ]);

                    $hijosCreados[] = [
                        'idRegistro' => $newIdRegistro,
                        'nombreProducto' => $productoHijo->nombreProducto,
                        'serie' => $seriePack
                    ];
                }
            }

            // 9. Validar estado del producto pack (podría quedar AGOTADO)
            $this->productoService->validateState($producto->idProducto);

            DB::commit();

            return [
                'success' => true,
                'message' => "Pack dividido exitosamente en " . count($hijosCreados) . " componentes",
                'division' => $division,
                'hijos' => $hijosCreados
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

    public function verificarPackDivisible($idRegistro)
    {
        $registro = RegistroProducto::with('DetalleComprobante.Producto.packHijos.ProductoHijo')
            ->find($idRegistro);

        if (!$registro) return null;

        $producto = $registro->DetalleComprobante->Producto;
        $packHijos = $producto->packHijos;

        if ($packHijos->isEmpty()) return null;
        if ($registro->estado !== 'NUEVO') return null;

        return [
            'idRegistro' => $registro->idRegistro,
            'nombreProducto' => $producto->nombreProducto,
            'serie' => $registro->numeroSerie,
            'componentes' => $packHijos->map(function ($ph) {
                return [
                    'idProductoHijo' => $ph->idProductoHijo,
                    'nombreProducto' => $ph->ProductoHijo->nombreProducto,
                    'cantidad' => $ph->cantidad
                ];
            })
        ];
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
}
