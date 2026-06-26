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

            // 8. Verificar si este pack fue armado manualmente antes (REUNION)
            $reunionPrevia = DivisionPack::with('detalles.RegistroProducto.DetalleComprobante.Producto')
                ->where('idRegistroPack', $idRegistro)
                ->where('tipo', 'REUNION')
                ->first();

            if ($reunionPrevia) {
                // RESTAURAR LOS COMPONENTES ORIGINALES
                foreach ($reunionPrevia->detalles as $detalle) {
                    $regHijo = $detalle->RegistroProducto;
                    if ($regHijo) {
                        $regHijo->estado = 'NUEVO';
                        $regHijo->fechaMovimiento = now();
                        $regHijo->observacion = "Componente restaurado tras dividir pack (Serie Origen: {$seriePack})";
                        $regHijo->save();

                        // Sumar stock del producto hijo
                        $this->inventarioRepository->addStock($regHijo->DetalleComprobante->idProducto, $idAlmacen);
                        $this->productoService->validateState($regHijo->DetalleComprobante->idProducto, true);

                        // Registrar detalle de la división
                        DivisionPackDetalle::create([
                            'idDivision' => $division->idDivision,
                            'idRegistroHijo' => $regHijo->idRegistro,
                            'idProductoHijo' => $regHijo->DetalleComprobante->idProducto,
                            'costo_asignado' => $regHijo->DetalleComprobante->precioUnitario ?? 0
                        ]);

                        $hijosCreados[] = [
                            'idRegistro' => $regHijo->idRegistro,
                            'nombreProducto' => $regHijo->DetalleComprobante->Producto->nombreProducto ?? 'Desconocido',
                            'serie' => $regHijo->numeroSerie
                        ];
                    }
                }
            } else {
                // 8. Para cada hijo definido en el pack de fábrica, crear nuevos registros
                $totalHijos = $packHijos->sum('cantidad');
                
                foreach ($packHijos as $packHijo) {
                    // Cálculo del precio unitario para este hijo
                    if ($totalPorcentaje > 0) {
                        $bolsaCosto = $precioUnitarioPack * ($packHijo->porcentaje_costo / 100);
                        $precioUnitarioHijo = $packHijo->cantidad > 0 ? round($bolsaCosto / $packHijo->cantidad, 4) : 0;
                    } else {
                        $tiposHijos = $packHijos->count();
                        $bolsaCosto = $precioUnitarioPack / $tiposHijos;
                        $precioUnitarioHijo = $packHijo->cantidad > 0 ? round($bolsaCosto / $packHijo->cantidad, 4) : 0;
                    }

                    for ($i = 0; $i < $packHijo->cantidad; $i++) {
                        $productoHijo = $packHijo->ProductoHijo;

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

                        // Generar serie propia del hijo
                        $modelOrCodeHijo = !empty($productoHijo->modelo) ? $productoHijo->modelo : $productoHijo->codigoProducto;
                        $cleanedModelHijo = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($modelOrCodeHijo));
                        $parcialCodeHijo = 'UNK-' . $cleanedModelHijo;
                        $validateCodeHijo = \App\Models\RegistroProducto::where('numeroSerie', 'like', "%{$parcialCodeHijo}%")->count();
                        $serieHijoGenerada = $parcialCodeHijo . '-' . (100000 + $validateCodeHijo + 1);

                        $lastRegistro = RegistroProducto::orderBy('idRegistro', 'desc')->first();
                        $newIdRegistro = $lastRegistro ? $lastRegistro->idRegistro + 1 : 1;

                        $registroHijo = RegistroProducto::create([
                            'idRegistro' => $newIdRegistro,
                            'idDetalleComprobante' => $newIdDc,
                            'idAlmacen' => $idAlmacen,
                            'numeroSerie' => $serieHijoGenerada,
                            'estado' => 'NUEVO',
                            'fechaMovimiento' => now(),
                            'observacion' => "Componente de pack dividido (Serie Origen: {$seriePack})"
                        ]);

                        $lastIngreso = IngresoProducto::orderBy('idIngreso', 'desc')->first();
                        $newIdIngreso = $lastIngreso ? $lastIngreso->idIngreso + 1 : 1;

                        IngresoProducto::create([
                            'idIngreso' => $newIdIngreso,
                            'idRegistro' => $newIdRegistro,
                            'idUser' => $idUser,
                            'fechaIngreso' => now()
                        ]);

                        $this->inventarioRepository->addStock($productoHijo->idProducto, $idAlmacen);
                        $this->productoService->validateState($productoHijo->idProducto, true);

                        DivisionPackDetalle::create([
                            'idDivision' => $division->idDivision,
                            'idRegistroHijo' => $newIdRegistro,
                            'idProductoHijo' => $productoHijo->idProducto,
                            'costo_asignado' => $precioUnitarioHijo
                        ]);

                        $hijosCreados[] = [
                            'idRegistro' => $newIdRegistro,
                            'nombreProducto' => $productoHijo->nombreProducto,
                            'serie' => $serieHijoGenerada
                        ];
                    }
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

}
