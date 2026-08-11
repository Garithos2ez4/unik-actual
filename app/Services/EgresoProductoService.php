<?php

namespace App\Services;

use App\Repositories\AlmacenRepositoryInterface;
use Carbon\Carbon;
use App\Repositories\EgresoProductoRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\PublicacionRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;

class EgresoProductoService implements EgresoProductoServiceInterface
{
    protected $egresoRepository;
    protected $registroRepository;
    protected $publicacionRepository;
    protected $headerService;
    protected $productoRepository;
    protected $inventarioRepository;
    protected $almacenRepository;

    public function __construct(
        EgresoProductoRepositoryInterface $egresoRepository,
        RegistroProductoRepositoryInterface $registroRepository,
        PublicacionRepositoryInterface $publicacionRepository,
        HeaderServiceInterface $headerService,
        ProductoServiceInterface $productoRepository,
        InventarioRepositoryInterface $inventarioRepository,
        AlmacenRepositoryInterface $almacenRepository
    ) {
        $this->egresoRepository = $egresoRepository;
        $this->registroRepository = $registroRepository;
        $this->publicacionRepository = $publicacionRepository;
        $this->headerService = $headerService;
        $this->productoRepository = $productoRepository;
        $this->inventarioRepository = $inventarioRepository;
        $this->almacenRepository = $almacenRepository;
    }

    public function getEgresosByMonth($date, $cant, $diaSeleccionado = null)
    {
        Carbon::setLocale('es');
        $carbonMonth = Carbon::createFromFormat('Y-m', $date);
        return $this->egresoRepository->getAllByMonth($carbonMonth->year, $carbonMonth->month, $cant, $diaSeleccionado);
    }

    public function searchAjaxRegistro($serial, $excludeArray = [])
    {
        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 1;
        $tasaFijaGlobal = \App\Models\Precios\Calculadora::where('idCalculadora', 2)->first()->tasaCambio ?? $tasaCambio;
        $preciosService = new \App\Services\PreciosService();

        $egresos = $this->registroRepository->searchByEgreso($serial, 7, $excludeArray);
        $result = $egresos->map(function ($details) use ($tasaCambio, $tasaFijaGlobal, $preciosService) {
            $producto = $details->DetalleComprobante->Producto;
            $detalleComprobante = $details->DetalleComprobante ?? null;

            $precioInventario = 0;
            $comp = $detalleComprobante->Comprobante ?? null;
            if ($detalleComprobante && $comp && stripos($comp->numeroComprobante ?? '', 'INVENTARIO') === false) {
                $precioInventario = ($detalleComprobante->precioUnitario ?? 0) / 1.18;
                if ($precioInventario > 1 && in_array(strtoupper($comp->moneda), ['SOL', 'SOLES', 'PEN'])) {
                    $precioInventario = $tasaCambio > 0 ? $precioInventario / $tasaCambio : $precioInventario;
                }
            }

            if ($precioInventario <= 1 && $producto) {
                $otroDc = \App\Models\Ventas\DetalleComprobante::where('idProducto', $producto->idProducto)
                    ->where('precioUnitario', '>', 0)
                    ->whereHas('Comprobante', function ($q) {
                        $q->where('numeroComprobante', 'NOT LIKE', '%INVENTARIO%');
                    })
                    ->orderBy('idDetalleComprobante', 'desc')
                    ->first();
                if ($otroDc) {
                    $precioInventario = $otroDc->precioUnitario / 1.18;
                    $mon = $otroDc->Comprobante->moneda ?? 'SOLES';
                    if (in_array(strtoupper($mon), ['SOL', 'SOLES', 'PEN'])) {
                        $precioInventario = $tasaCambio > 0 ? $precioInventario / $tasaCambio : $precioInventario;
                    }
                }
            }

            $precioDolarBase = ($precioInventario > 1) ? $precioInventario : ($producto->precioDolar ?? 0);

            $precioCalculado = $preciosService->getPrecioCalculado($precioDolarBase, $producto->idGrupo, 'DOLAR', $producto->estadoProductoWeb);
            $precioDolarTotal = $precioCalculado + ($producto->gananciaExtra ?? 0);

            $usar_tc_fijo = $producto->usar_tc_fijo ?? true;

            if ($usar_tc_fijo) {
                if (isset($producto->tc_fijo) && $producto->tc_fijo > 0) {
                    $tc_a_usar = $producto->tc_fijo;
                } else {
                    $tc_a_usar = $tasaFijaGlobal;
                }
            } else {
                $tc_a_usar = $tasaCambio; // SUNAT
            }

            $esHerramienta = (bool)$details->es_herramienta;
            $precioFinalSoles = $esHerramienta ? 0 : round($precioDolarTotal * $tc_a_usar, 2);

            return [
                'nombreProducto' => $producto->nombreProducto,
                'codigoProducto' => $producto->codigoProducto,
                'idRegistroProducto' => $details->idRegistro,
                'numeroSerie' => $details->numeroSerie,
                'estado' => $details->estado,
                'modelo' => $producto->modelo,
                'idGrupo' => $producto->idGrupo,
                'image' => $producto->imagenProducto1,
                'marca' => $producto->MarcaProducto->nombreMarca,
                'es_herramienta' => $esHerramienta,
                'precioSoles' => $precioFinalSoles,
                'precioCompra' => $detalleComprobante->precioCompra ?? 0
            ];
        });
        return $result;
    }

    public function getOneAjaxRegistro($serial)
    {
        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 1;
        $tasaFijaGlobal = \App\Models\Precios\Calculadora::where('idCalculadora', 2)->first()->tasaCambio ?? $tasaCambio;
        $preciosService = new \App\Services\PreciosService();

        $egreso = $this->registroRepository->getByEgreso($serial);

        if ($egreso) {
            $producto = $egreso->DetalleComprobante->Producto;
            $detalleComprobante = $egreso->DetalleComprobante ?? null;

            $precioInventario = 0;
            $comp = $detalleComprobante->Comprobante ?? null;
            if ($detalleComprobante && $comp && stripos($comp->numeroComprobante ?? '', 'INVENTARIO') === false) {
                $precioInventario = ($detalleComprobante->precioUnitario ?? 0) / 1.18;
                if ($precioInventario > 1 && in_array(strtoupper($comp->moneda), ['SOL', 'SOLES', 'PEN'])) {
                    $precioInventario = $tasaCambio > 0 ? $precioInventario / $tasaCambio : $precioInventario;
                }
            }

            if ($precioInventario <= 1 && $producto) {
                $otroDc = \App\Models\Ventas\DetalleComprobante::where('idProducto', $producto->idProducto)
                    ->where('precioUnitario', '>', 0)
                    ->whereHas('Comprobante', function ($q) {
                        $q->where('numeroComprobante', 'NOT LIKE', '%INVENTARIO%');
                    })
                    ->orderBy('idDetalleComprobante', 'desc')
                    ->first();
                if ($otroDc) {
                    $precioInventario = $otroDc->precioUnitario / 1.18;
                    $mon = $otroDc->Comprobante->moneda ?? 'SOLES';
                    if (in_array(strtoupper($mon), ['SOL', 'SOLES', 'PEN'])) {
                        $precioInventario = $tasaCambio > 0 ? $precioInventario / $tasaCambio : $precioInventario;
                    }
                }
            }

            $precioDolarBase = ($precioInventario > 1) ? $precioInventario : ($producto->precioDolar ?? 0);

            $precioCalculado = $preciosService->getPrecioCalculado($precioDolarBase, $producto->idGrupo, 'DOLAR', $producto->estadoProductoWeb);
            $precioDolarTotal = $precioCalculado + ($producto->gananciaExtra ?? 0);

            $usar_tc_fijo = $producto->usar_tc_fijo ?? true;

            if ($usar_tc_fijo) {
                if (isset($producto->tc_fijo) && $producto->tc_fijo > 0) {
                    $tc_a_usar = $producto->tc_fijo;
                } else {
                    $tc_a_usar = $tasaFijaGlobal;
                }
            } else {
                $tc_a_usar = $tasaCambio; // SUNAT
            }

            $esHerramienta = (bool)$egreso->es_herramienta;
            $precioFinalSoles = $esHerramienta ? 0 : round($precioDolarTotal * $tc_a_usar, 2);

            $result = [
                'nombreProducto' => $producto->nombreProducto,
                'codigoProducto' => $producto->codigoProducto,
                'idRegistroProducto' => $egreso->idRegistro,
                'numeroSerie' => $egreso->numeroSerie,
                'estado' => $egreso->estado,
                'modelo' => $producto->modelo,
                'image' => $producto->imagenProducto1,
                'marca' => $producto->MarcaProducto->nombreMarca,
                'es_herramienta' => $esHerramienta,
                'precioSoles' => $precioFinalSoles
            ];
            return $result;
        }

        return [];
    }


    public function searchAjaxEgreso($serie, $cant)
    {
        $egresos = $this->egresoRepository->getEgresoBySerial($serie, $cant);
        $result = $egresos->map(function ($details) {
            $devolucion = $details->Devoluciones->first();

            // Lógica de estado igual que en el componente lista_egresos
            $esDevueltoLegado = false;
            if (!$devolucion) {
                $ultimoEgresoId = $details->RegistroProducto->Egresos->max('idEgreso');
                if ($ultimoEgresoId > $details->idEgreso) {
                    $esDevueltoLegado = true;
                }
            }

            $state = $devolucion ? $devolucion->tipo : ($esDevueltoLegado ? 'DEVOLUCION' : $details->RegistroProducto->estado);
            $observacionFinal = $devolucion ? $devolucion->motivo : $details->RegistroProducto->observacion;

            $idPlataforma = null;
            $idCuentaPlataforma = null;
            $nombrePlataforma = 'VENTA DIRECTA';
            $nombreCuenta = 'S/C';

            if ($details->Publicacion) {
                $idPlataforma = $details->Publicacion->CuentasPlataforma->idPlataforma;
                $idCuentaPlataforma = $details->Publicacion->idCuentaPlataforma;
                $nombrePlataforma = $details->Publicacion->CuentasPlataforma->Plataforma->nombrePlataforma;
                $nombreCuenta = $details->Publicacion->CuentasPlataforma->nombreCuenta;
            } else {
                // Si es venta directa, buscamos la plataforma "Tienda" (ID 7)
                // y la cuenta vinculada al usuario que hizo la venta
                $idPlataforma = 7;
                $cuentaUsuario = \App\Models\Empresa\CuentasPlataforma::where('idPlataforma', 7)
                    ->where('nombreCuenta', $details->Usuario->user)
                    ->first();
                if ($cuentaUsuario) {
                    $idCuentaPlataforma = $cuentaUsuario->idCuentaPlataforma;
                    $nombrePlataforma = 'Tienda';
                    $nombreCuenta = $cuentaUsuario->nombreCuenta;
                }
            }

            $detalleVenta = $details->DetalleVenta;
            $fallbackPrecio = 0;
            if ($detalleVenta && $detalleVenta->precioVenta > 0) {
                $fallbackPrecio = $detalleVenta->precioVenta;
            } elseif ($detalleVenta && $detalleVenta->Venta && $detalleVenta->Venta->totalVenta > 0) {
                $fallbackPrecio = $detalleVenta->Venta->totalVenta;
            } elseif ($details->Publicacion && $details->Publicacion->precioPublicacion > 0) {
                $fallbackPrecio = $details->Publicacion->precioPublicacion;
            } else {
                $producto = $details->RegistroProducto->DetalleComprobante->Producto ?? null;
                if ($producto && isset($producto->precioDolar) && $producto->precioDolar > 0) {
                    $tasaCambio = \App\Models\Precios\Calculadora::first()?->tasaCambio ?? 1;
                    $fallbackPrecio = $producto->precioDolar * $tasaCambio;
                }
            }

            $productoAjax    = $details->RegistroProducto->DetalleComprobante->Producto ?? null;
            $idGrupoAjax     = $productoAjax->idGrupo ?? 0;
            $nombreUpperAjax = strtoupper($productoAjax->nombreProducto ?? '');
            $isLaptopOrAioAjax = in_array($idGrupoAjax, [1, 2, 3, 10])
                || str_contains($nombreUpperAjax, 'LAPTOP')
                || str_contains($nombreUpperAjax, 'AIO')
                || str_contains($nombreUpperAjax, 'ALL IN ONE');

            return [
                'idEgreso'          => $details->idEgreso,
                'idRegistro'        => $details->idRegistro,
                'idPublicacion'     => $details->idPublicacion,
                'nombreProducto'    => $productoAjax->nombreProducto ?? '',
                'codigoProducto'    => $productoAjax->codigoProducto ?? '',
                'numeroSerie'       => $details->RegistroProducto->numeroSerie,
                'sku'               => $details->Publicacion ? $details->Publicacion->sku : null,
                'numeroOrden'       => $details->numeroOrden,
                'fechaCompra'       => $details->fechaCompra,
                'fechaDespacho'     => $details->fechaDespacho,
                'fechaMovimiento'   => $devolucion ? ($devolucion->fechaDevolucion ?? $details->RegistroProducto->fechaMovimiento) : $details->RegistroProducto->fechaMovimiento,
                'usuario'           => $details->Usuario->user,
                'observacion'       => $observacionFinal,
                'estado'            => $state,
                'idPlataforma'      => $idPlataforma,
                'idCuentaPlataforma'=> $idCuentaPlataforma,
                'nombrePlataforma'  => $nombrePlataforma,
                'cuenta'            => $nombreCuenta,
                'imagenPublicacion' => $details->Publicacion ? asset('storage/' . $details->Publicacion->CuentasPlataforma->Plataforma->imagenPlataforma) : null,
                'precioVenta'       => $fallbackPrecio,
                'precioCosto'       => $details->RegistroProducto->DetalleComprobante->precioCompra ?? 0,
                'hasDetalleVenta'   => $detalleVenta !== null,
                'isLaptopOrAio'     => $isLaptopOrAioAjax,
            ];
        });
        return $result;
    }

    public function getRegistro($serial)
    {
        $registro = $this->registroRepository->getByEgreso($serial);
        return $registro;
    }

    public function getPublicacion($sku)
    {
        $publicacion = $this->publicacionRepository->getOne('sku', $sku);
        return $publicacion;
    }

    public function getAllAlmacenes()
    {
        return $this->almacenRepository->all();
    }

    public function createEgreso(array $data, array $items)
    {
        $productos = array();
        $egresosGenerados = array();
        if (!empty($data) && !empty($items)) {
            foreach ($items as $item) {
                $idRegistro = $item['idregistro'];
                $idPublicacion = isset($item['idpublicacion']) && $item['idpublicacion'] !== 'NULO' && $item['idpublicacion'] !== '' ? $item['idpublicacion'] : null;
                $registro = $this->registroRepository->getOne('idRegistro', $idRegistro);

                // Si se especifica un costo personalizado (por ejemplo, para componentes), actualizar el DetalleComprobante
                if (isset($item['costo']) && $item['costo'] !== '') {
                    $customCostoSoles = floatval($item['costo']);
                    $dc = \App\Models\Ventas\DetalleComprobante::with('Comprobante')->find($registro->idDetalleComprobante);
                    if ($dc && $dc->Comprobante) {
                        $moneda = $dc->Comprobante->moneda ?? 'SOLES';
                        $tasaCambio = \App\Models\Precios\Calculadora::first()->tasaCambio ?? 3.70;

                        if (strtoupper($moneda) === 'DOLAR' || strtoupper($moneda) === 'USD') {
                            $newPrecioUnitario = $tasaCambio > 0 ? $customCostoSoles / $tasaCambio : $customCostoSoles;
                        } else {
                            $newPrecioUnitario = $customCostoSoles;
                        }

                        $newPrecioUnitario = round($newPrecioUnitario, 4);
                        $sharedCount = \App\Models\Inventario\RegistroProducto::where('idDetalleComprobante', $registro->idDetalleComprobante)->count();

                        if ($sharedCount > 1) {
                            $newDc = $dc->replicate();
                            $lastDc = \App\Models\Ventas\DetalleComprobante::orderBy('idDetalleComprobante', 'desc')->first();
                            $nextId = $lastDc ? $lastDc->idDetalleComprobante + 1 : 1;

                            $newDc->idDetalleComprobante = $nextId;
                            $newDc->precioUnitario = $newPrecioUnitario;
                            $newDc->precioCompra = $newPrecioUnitario;
                            $newDc->save();

                            $registro->idDetalleComprobante = $nextId;
                            $registro->save();

                            $newQty = $sharedCount - 1;
                            $dc->precioCompra = round($dc->precioUnitario * $newQty, 2);
                            $dc->save();
                        } else {
                            $dc->precioUnitario = $newPrecioUnitario;
                            $dc->precioCompra = $newPrecioUnitario;
                            $dc->save();
                        }
                    }
                }

                // Determinar si el producto es un Reseteador (herramienta de servicio) usando la columna es_herramienta
                $esReseteador = (bool)$registro->es_herramienta;
                $productoAsociado = \App\Models\Catalogo\Producto::with('GrupoProducto')->find($registro->DetalleComprobante->idProducto ?? null);

                // Validamos que el producto est en un estado vendible (NUEVO), a menos que sea un Reseteador
                if (!$esReseteador && $registro->estado !== 'NUEVO') {
                    throw new \Exception("La serie {$registro->numeroSerie} no se puede vender porque esta en estado {$registro->estado}.");
                }

                // REGLA DE NEGOCIO: Validar historial de Devoluciones/Garantías
                $ultimaDevolucion = \App\Models\Ventas\Devolucion::where('idRegistro', $idRegistro)
                    ->latest('created_at')
                    ->first();

                if ($ultimaDevolucion) {
                    // 1. Validar aptitud para venta si fue garantía
                    if ($ultimaDevolucion->tipo === 'GARANTIA' && !$ultimaDevolucion->aptoParaVenta) {
                        throw new \Exception("La serie {$registro->numeroSerie} proviene de GARANTA y no ha sido marcada como apta para la venta. El rea tcnica debe actualizar el detalle de reparacin.");
                    }

                    // 2. Validar fechas: No se puede vender antes de ser devuelto físicamente
                    $fechaVenta = \Carbon\Carbon::parse($data['fechaCompra']);
                    // Usamos fechaDevolucion si existe, sino created_at
                    $fechaRetorno = $ultimaDevolucion->fechaDevolucion ? \Carbon\Carbon::parse($ultimaDevolucion->fechaDevolucion) : $ultimaDevolucion->created_at;

                    if ($fechaVenta->lt($fechaRetorno->startOfDay())) {
                        throw new \Exception("Error en serie {$registro->numeroSerie}: La fecha de venta ({$fechaVenta->format('d/m/Y')}) no puede ser anterior a la fecha de su retorno físico ({$fechaRetorno->format('d/m/Y')}).");
                    }
                }

                $idAlmacen = $registro->idAlmacen;
                $data['idRegistro'] = $idRegistro;
                $data['idPublicacion'] = $idPublicacion;
                $data['idUser'] = $this->headerService->getModelUser()->idUser;
                $data['idEgreso'] = $this->getNewIdEgreso(); // Generamos un ID nuevo

                $this->egresoRepository->create($data); // Guardamos la nueva venta
                $egresosGenerados[$idRegistro] = $data['idEgreso'];

                if (!$esReseteador) {
                    $arrayRegistro = [
                        'estado' => 'ENTREGADO',
                        'fechaMovimiento' => $data['fechaCompra'],
                        'observacion' => ''
                    ]; // Limpiamos la observación para la nueva venta

                    $this->registroRepository->update($idRegistro, $arrayRegistro);
                    $producto = $this->updateStock($idAlmacen, $idRegistro);
                } else {
                    $arrayRegistro = [
                        'estado' => 'EN_USO', // Mantenemos como herramienta en uso
                        'fechaMovimiento' => $data['fechaCompra'],
                        'observacion' => 'Utilizado para servicio de reseteo'
                    ];
                    $this->registroRepository->update($idRegistro, $arrayRegistro);
                    $producto = clone $productoAsociado; // No restamos stock
                    $producto->idProducto = $productoAsociado->idProducto; // Aseguramos que el objeto tenga el idProducto
                }

                $productos[] = $producto;

                // Validar estado del producto (por si se agotó), solo si no es reseteador
                if (!$esReseteador) {
                    $this->productoRepository->validateState($producto->idProducto);
                }
            }
        }
        return [
            'productos' => $productos,
            'egresos' => $egresosGenerados
        ];
    }

    public function updateEgreso($transaction, $idEgreso, $observacion, $plataforma = null, $fechaDevolucion = null, $dataEgreso = [])
    {
        $modelEgreso = $this->egresoRepository->getOne('idEgreso', $idEgreso);
        $registro = $modelEgreso->RegistroProducto;
        $tipoTransaccion = strtoupper($transaction); // 'DEVOLUCION', 'GARANTIA' o 'UPDATE'

        // Si hay datos para actualizar en el egreso (fecha, sku, nro_orden, precioVenta)
        if (!empty($dataEgreso)) {
            $updateData = [];
            if (isset($dataEgreso['sku'])) {
                $publicacion = $this->getPublicacion($dataEgreso['sku']);
                $updateData['idPublicacion'] = $publicacion ? $publicacion->idPublicacion : null;
            }
            if (isset($dataEgreso['fechaCompra'])) {
                $updateData['fechaCompra'] = $dataEgreso['fechaCompra'];
                if ($registro->estado === 'ENTREGADO') {
                    $this->registroRepository->update($registro->idRegistro, ['fechaMovimiento' => $dataEgreso['fechaCompra']]);
                }
            }
            if (isset($dataEgreso['fechaDespacho'])) $updateData['fechaDespacho'] = $dataEgreso['fechaDespacho'];
            if (isset($dataEgreso['numeroOrden'])) $updateData['numeroOrden'] = $dataEgreso['numeroOrden'];

            if (!empty($updateData)) {
                $this->egresoRepository->update($idEgreso, $updateData);
            }

            // Actualizar precio de venta en DetalleVenta y totalVenta en Venta
            if (isset($dataEgreso['precioVenta']) && $dataEgreso['precioVenta'] !== '') {
                $nuevoPrecio = floatval($dataEgreso['precioVenta']);

                if ($nuevoPrecio == 0) {
                    $nuevoPrecio = 0.1;
                }

                $detalleVenta = \App\Models\Ventas\DetalleVenta::withoutGlobalScope('completado')->where('idEgreso', $idEgreso)->first();
                if ($detalleVenta) {
                    $detalleVenta->precioVenta = $nuevoPrecio;
                    $detalleVenta->save();

                    $venta = $detalleVenta->Venta;
                    if ($venta) {
                        $nuevoTotal = \App\Models\Ventas\DetalleVenta::where('idVenta', $venta->idVenta)
                            ->selectRaw('SUM(precioVenta * cantidad) as total')
                            ->first()
                            ->total ?? 0;

                        $venta->totalVenta = floatval($nuevoTotal);
                        $venta->save();

                        // Actualizar PagoVenta para reflejar el nuevo precio
                        // Si solo hay un pago, le asignamos todo el total. Si hay varios, se ajusta el primero proporcionalmente o se deja una alerta,
                        // pero la regla general es tener un solo pago para el total.
                        $pagos = \App\Models\Ventas\PagoVenta::where('idVenta', $venta->idVenta)->get();
                        if ($pagos->count() === 1) {
                            $pagoUnico = $pagos->first();
                            $pagoUnico->monto = floatval($nuevoTotal);
                            $pagoUnico->save();
                        } elseif ($pagos->count() > 1) {
                            // Si hay múltiples pagos, calculamos la diferencia y se la restamos/sumamos al último pago
                            // para que la suma cuadre.
                            $diferencia = floatval($nuevoTotal) - $pagos->sum('monto');
                            $ultimoPago = $pagos->last();
                            $ultimoPago->monto += $diferencia;
                            if ($ultimoPago->monto < 0) $ultimoPago->monto = 0; // Prevenir montos negativos
                            $ultimoPago->save();
                        }
                    }
                } else {
                    // MIGRACIÓN AUTOMÁTICA DE EGRESO ANTIGUO
                    // 1. Obtener usuario (el del egreso actual o el logueado)
                    $idUser = $this->headerService->getModelUser()->idUser ?? $modelEgreso->idUser;

                    // 2. Determinar canal
                    $canal = $modelEgreso->idPublicacion ? 'PLATAFORMA' : 'TIENDA';

                    // 3. Crear cabecera Venta
                    $venta = \App\Models\Ventas\Venta::create([
                        'idUser'      => $idUser,
                        'canal'       => $canal,
                        'numeroOrden' => $modelEgreso->numeroOrden,
                        'fechaVenta'  => $modelEgreso->fechaCompra ? $modelEgreso->fechaCompra->toDateTimeString() : now()->toDateTimeString(),
                        'totalVenta'  => $nuevoPrecio,
                        'observacion' => 'Migración automática de egreso histórico',
                    ]);

                    // 4. Crear DetalleVenta
                    \App\Models\Ventas\DetalleVenta::create([
                        'idVenta'       => $venta->idVenta,
                        'idEgreso'      => $idEgreso,
                        'idProducto'    => $registro->DetalleComprobante->idProducto,
                        'idPublicacion' => $modelEgreso->idPublicacion,
                        'precioVenta'   => $nuevoPrecio,
                        'cantidad'      => 1,
                        'origenPrecio'  => $modelEgreso->idPublicacion ? 'PUBLICACION' : 'TIENDA',
                    ]);
                }
            }
        }

        // Si es una actualización simple de observación
        if ($tipoTransaccion === 'UPDATE') {
            $this->registroRepository->update($registro->idRegistro, ['observacion' => $observacion]);
            return;
        }

        // Si es una Devolución o Garantía, procesamos si el estado no era ya el mismo
        if ($registro->estado != $tipoTransaccion) {

            // Preparamos la observación con la fecha concatenada
            $fechaFormateada = $fechaDevolucion ? \Carbon\Carbon::parse($fechaDevolucion)->format('d/m/Y') : now()->format('d/m/Y');
            $observacionFinal = $observacion . " - " . $fechaFormateada;

            // 1. Creamos el registro en la nueva tabla de devoluciones
            \App\Models\Ventas\Devolucion::create([
                'idEgreso'   => $idEgreso,
                'idRegistro' => $registro->idRegistro,
                'idUser'     => $this->headerService->getModelUser()->idUser,
                'tipo'       => $tipoTransaccion,
                'plataforma' => $plataforma,
                'motivo'     => $observacionFinal,
                'fechaDevolucion' => $fechaDevolucion,
                'aptoParaVenta' => ($tipoTransaccion === 'DEVOLUCION') ? true : false,
            ]);

            // 2. Actualizamos el estado del producto físico
            $dataRegistro = [
                'estado' => $tipoTransaccion,
                'fechaMovimiento' => $fechaDevolucion ?? now(),
                'observacion' => $observacionFinal // Guardamos la observación formateada
            ];
            $this->registroRepository->update($registro->idRegistro, $dataRegistro);

            // Determinar si es Reseteador leyendo directamente la columna es_herramienta de RegistroProducto
            $esReseteador = (bool)$registro->es_herramienta;
            $productoFisico = $registro->DetalleComprobante->Producto ?? null;

            // 3. Retornamos el stock al inventario SOLO si no es reseteador
            if (!$esReseteador) {
                $idProducto = $productoFisico->idProducto;
                $idAlmacen = $registro->idAlmacen;
                $this->inventarioRepository->addStock($idProducto, $idAlmacen);

                // Validar estado del producto (por si ahora hay stock)
                $this->productoRepository->validateState($idProducto);
            }

            // 4. Actualizar estado financiero de DetalleVenta y Venta
            $detalleVentaDevuelto = \App\Models\Ventas\DetalleVenta::withoutGlobalScope('completado')->where('idEgreso', $idEgreso)->first();
            if ($detalleVentaDevuelto) {
                $detalleVentaDevuelto->estado = 'DEVUELTO';
                $detalleVentaDevuelto->save();

                $ventaDevuelta = $detalleVentaDevuelto->Venta;
                if ($ventaDevuelta) {
                    $nuevoTotalDevuelto = \App\Models\Ventas\DetalleVenta::where('idVenta', $ventaDevuelta->idVenta)
                        ->selectRaw('SUM(precioVenta * cantidad) as total')
                        ->first()
                        ->total ?? 0;

                    $ventaDevuelta->totalVenta = floatval($nuevoTotalDevuelto);
                    $ventaDevuelta->save();

                    $pagosDevuelto = \App\Models\Ventas\PagoVenta::where('idVenta', $ventaDevuelta->idVenta)->get();
                    if ($pagosDevuelto->count() === 1) {
                        $pagoUnico = $pagosDevuelto->first();
                        $pagoUnico->monto = floatval($nuevoTotalDevuelto);
                        $pagoUnico->save();
                    } elseif ($pagosDevuelto->count() > 1) {
                        $diferencia = floatval($nuevoTotalDevuelto) - $pagosDevuelto->sum('monto');
                        $ultimoPago = $pagosDevuelto->last();
                        $ultimoPago->monto += $diferencia;
                        if ($ultimoPago->monto < 0) $ultimoPago->monto = 0;
                        $ultimoPago->save();
                    }
                }
            }
        }
    }

    private function updateStock($idAlmacen, $idRegistro)
    {
        if ($idAlmacen && $idRegistro) {
            $producto = $this->registroRepository->getOne('idRegistro', $idRegistro)
                ->DetalleComprobante->Producto;
            $this->inventarioRepository->removeStock($producto->idProducto, $idAlmacen);
            return $producto;
        }
    }

    private function getNewIdEgreso()
    {
        $lastEgreso = $this->egresoRepository->getLast();
        $id = $lastEgreso ? $lastEgreso->idEgreso : 0;
        return $id + 1;
    }
}
