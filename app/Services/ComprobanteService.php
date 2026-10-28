<?php
namespace App\Services;

use Carbon\Carbon;
use App\Repositories\AlmacenRepositoryInterface;
use App\Repositories\ComprobanteRepositoryInterface;
use App\Repositories\DetalleComprobanteRepositoryInterface;
use App\Repositories\ProductoRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use App\Repositories\IngresoProductoRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Catalogo\Producto;

class ComprobanteService implements ComprobanteServiceInterface
{
    protected $comprobanteRepository;
    protected $almacenRepository;
    protected $detalleComprobanteRepository;
    protected $productoRepository;
    protected $registroProductoRepository;
    protected $ingresoRepository;
    protected $headerService;
    protected $inventarioRepository;
    protected $productoService;


    public function __construct(ComprobanteRepositoryInterface $comprobanteRepository,
                                AlmacenRepositoryInterface $almacenService,
                                DetalleComprobanteRepositoryInterface $detalleComprobanteRepository,
                                ProductoRepositoryInterface $productoRepository,
                                RegistroProductoRepositoryInterface $registroProductoRepository,
                                IngresoProductoRepositoryInterface $ingresoRepository,
                                HeaderServiceInterface $headerService,
                                InventarioRepositoryInterface $inventarioRepository,
                                ProductoServiceInterface $productoService)
    {
        $this->comprobanteRepository = $comprobanteRepository;
        $this->almacenRepository = $almacenService;
        $this->detalleComprobanteRepository = $detalleComprobanteRepository;
        $this->productoRepository = $productoRepository;
        $this->registroProductoRepository = $registroProductoRepository;
        $this->ingresoRepository = $ingresoRepository;
        $this->headerService = $headerService;
        $this->inventarioRepository = $inventarioRepository;
        $this->productoService = $productoService;
    }

    public function getAllRegistrosByComprobanteId($idComprobante){
        return $this->comprobanteRepository->getAllRegistrosByComprobanteId($idComprobante);
    }
    
     public function getAllAlmacen(){
        $almacenes = $this->almacenRepository->all();
        return $almacenes;
    }
    
    public function getOneById($id){
        return $this->comprobanteRepository->getOne('idComprobante',$id);
    }
    
    public function getByMonth($date,$cant,$querys){
        Carbon::setLocale('es');
        $fechacompleta = $date. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->comprobanteRepository->getAllByMonth($carbonMonth,$cant,$querys);
    }
    
    public function insertComprobante(array $inputs){
        $exists = $this->comprobanteRepository->validateDuplicity($inputs['numeroComprobante'],$inputs['idTipoComprobante'],$inputs['idProveedor']);
        
        if(!$exists){
            $inputs['idComprobante'] = $this->getNewIdComprobante();
            $comprobante = $this->comprobanteRepository->create($inputs);
            return $inputs['idComprobante'];
        }else{
            return null;
        }
    }
    
    public function updateComprobante($id,array $inputs,array $details){
        Log::info("[ComprobanteService::updateComprobante] Iniciando para ID={$id}. Inputs: " . json_encode($inputs) . " | Details count: " . count($details));
        if ($id && $inputs) {
            if (empty($details)) {
                Log::warning("[ComprobanteService::updateComprobante] 'details' vacio. Se eliminara comprobante ID={$id}.");
                $this->comprobanteRepository->remove($id);
            } else {
                \Illuminate\Support\Facades\DB::beginTransaction();
                try {
                    // Actualiza el comprobante si hay detalles
                    $arrayComprobante = [
                        'moneda' => $inputs['moneda'],
                        'adquisicion' => $inputs['adquisicion'],
                        'totalCompra' => $inputs['total'],
                        'estado' => 'REGISTRADO',
                    ];
                    
                    Log::info("[ComprobanteService::updateComprobante] Actualizando comprobante ID={$id} a REGISTRADO...");
                    $comprobante = $this->comprobanteRepository->update($id, $arrayComprobante);
                    foreach($details as $detailIdx => $detail){
                        $idProd = $detail['producto'] ?? null;
                        if ($idProd) {
                            $prod = Producto::find($idProd);
                            if ($prod && $prod->es_padre) {
                                Log::warning("[ComprobanteService::updateComprobante] Detalle #{$detailIdx} es producto padre ID={$idProd}. Omitiendo.");
                                continue;
                            }
                        }

                        $arrayDetalle = [
                            'idComprobante' => $id,
                            'idProducto' => $detail['producto'],
                            'medida' => $detail['medida'],
                            'precioUnitario' => $detail['preciounitario'],
                            'precioCompra' => $detail['preciototal'],
                        ];
                        
                        $registros = $detail['ingreso'] ?? [];
                        $almacen = $inputs['almacen'];
                        
                        Log::info("[ComprobanteService::updateComprobante] Procesando detalle #{$detailIdx}: idProducto={$detail['producto']}, almacen={$almacen}, series count=" . count($registros));
                        $this->insertDetalleComprobante($arrayDetalle, $registros, $almacen);
                    }
                    \Illuminate\Support\Facades\DB::commit();
                    Log::info("[ComprobanteService::updateComprobante] DB::commit exitoso para comprobante ID={$id}.");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    Log::error("[ComprobanteService::updateComprobante] DB::rollBack por excepcion: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
                    throw $e;
                }
            }
        } else {
            Log::warning("[ComprobanteService::updateComprobante] ID ({$id}) o inputs vacios.");
        }
    }
    public function updateRegisteredComprobante($id, $data, array $detalles = [])
    {
        DB::beginTransaction();
        try {
            $comprobante = $this->comprobanteRepository->getOne('idComprobante', $id);
            if (!$comprobante) {
                throw new Exception("Comprobante no encontrado.");
            }

            if (!is_array($data)) {
                $data = ['moneda' => $data];
            }

            $hasDetalleUpdates = false;
            $totalCompraCalculado = 0;

            if (!empty($detalles)) {
                foreach ($comprobante->DetalleComprobante as $detalle) {
                    $newPrecioUnitario = null;
                    foreach ($detalles as $reqDetalle) {
                        if ($reqDetalle['idDetalleComprobante'] == $detalle->idDetalleComprobante) {
                            $newPrecioUnitario = $reqDetalle['precioUnitario'];
                            break;
                        }
                    }

                    if ($newPrecioUnitario !== null) {
                        $hasDetalleUpdates = true;
                        $validRegistros = 0;
                        foreach ($detalle->RegistroProducto as $registro) {
                            if ($registro->estado != 'INVALIDO') {
                                $validRegistros++;
                            }
                        }
                        
                        $precioCompra = $newPrecioUnitario * $validRegistros;
                        $totalCompraCalculado += $precioCompra;

                        $this->detalleComprobanteRepository->update($detalle->idDetalleComprobante, [
                            'precioUnitario' => $newPrecioUnitario,
                            'precioCompra' => $precioCompra
                        ]);
                    } else {
                        $totalCompraCalculado += $detalle->precioCompra;
                    }
                }
            }

            // Si se actualizaron precios de detalles, actualizamos totalCompra calculado
            if ($hasDetalleUpdates) {
                $data['totalCompra'] = $totalCompraCalculado;
            } elseif (!isset($data['totalCompra']) || $data['totalCompra'] === null || $data['totalCompra'] === '') {
                unset($data['totalCompra']);
            }

            // Filtrar solo valores no nulos
            $dataToUpdate = array_filter($data, function ($val) {
                return $val !== null && $val !== '';
            });

            if (!empty($dataToUpdate)) {
                $this->comprobanteRepository->update($id, $dataToUpdate);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteComprobante($idComprobante)
    {
        DB::beginTransaction();

        try {
            $comprobante = $this->comprobanteRepository->getOne('idComprobante', $idComprobante);

            if (!$comprobante) {
                throw new Exception("Comprobante no encontrado.");
            }

            $updated = true;
            $totalCompra = $comprobante->totalCompra;

            foreach ($comprobante->DetalleComprobante as $detalle) {
                $precioCompra = $detalle->precioCompra;
                foreach ($detalle->RegistroProducto as $registro) {
                    
                    if ($registro->estado != 'INVALIDO') {
                        $dataReg = ['estado' => 'INVALIDO'];
                        
                        if ($registro->estado == 'ENTREGADO') {
                            // Anular la venta asociada para que no salga en métricas
                            $egresos = \App\Models\Inventario\EgresoProducto::where('idRegistro', $registro->idRegistro)->get();
                            foreach($egresos as $egreso) {
                                \App\Models\Ventas\DetalleVenta::where('idEgreso', $egreso->idEgreso)->update([
                                    'estado' => 'ANULADO',
                                    'precioVenta' => 0
                                ]);
                            }
                        } else {
                            // Si no estaba entregado, lo removemos del inventario físico
                            $this->inventarioRepository->removeStock($detalle->idProducto, $registro->idAlmacen);
                        }

                        $precioCompra -= $detalle->precioUnitario;
                        $totalCompra -= $detalle->precioUnitario;

                        $this->registroProductoRepository->update($registro->idRegistro, $dataReg);
                    } else {
                        $updated = false;
                    }
                }
                $dataDetail = ['precioCompra' => $precioCompra];
                $this->detalleComprobanteRepository->update($detalle->idDetalleComprobante, $dataDetail);
            }

            $dataComprobante = ['totalCompra' => $totalCompra];

            if ($updated) {
                $dataComprobante['estado'] = 'INVALIDO';
            }
            $this->comprobanteRepository->update($idComprobante, $dataComprobante);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function searchAjaxComprobante($column,$data){
        // Usamos el nuevo método si están buscando por comprobante, para que también busque por número de serie
        if ($column === 'numeroComprobante') {
            $comprobantes = $this->comprobanteRepository->searchByInvoiceOrSerial($data, 5);
        } else {
            $comprobantes = $this->comprobanteRepository->searchTakeList($column, $data, 5);
        }

        $comprobantes = $comprobantes->map(function($comprobante) {
            $comprobante->fechaPersonalizada = \Carbon\Carbon::parse($comprobante->fechaRegistro)->format('d-m-Y');
            $comprobante->encryptId = encrypt($comprobante->idComprobante );
                            $comprobante->Preveedor = $comprobante->Preveedor;
                            return $comprobante;
                        });
        return $comprobantes;
    }

    public function validateSeriesAjax(array $array){
        $serials = array();
        if($array){
            foreach($array as $data){
                $product = $this->productoRepository->validateSerial($data->producto,$data->serie);
                if(!empty($product)){
                    $serials[] = $data->serie;
                }
            }
        }
        return $serials;
    }

    public function filtroUsuario($month){
        Carbon::setLocale('es');
        $fechacompleta = $month. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->comprobanteRepository->getUsuariosByMonth($carbonMonth);
    }
    public function filtroProveedor($month){
        Carbon::setLocale('es');
        $fechacompleta = $month. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->comprobanteRepository->getProveedoresByMonth($carbonMonth);
    }
    public function filtroDocumento($month){
        Carbon::setLocale('es');
        $fechacompleta = $month. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->comprobanteRepository->getDocumentosByMonth($carbonMonth);
    }
    public function filtroEstado($month){
        Carbon::setLocale('es');
        $fechacompleta = $month. '-01';
        $carbonMonth = Carbon::createFromFormat('Y-m-d', $fechacompleta);
        return $this->comprobanteRepository->getEstadosByMonth($carbonMonth);
    }

    private function insertDetalleComprobante(array $data, array $registros, $idAlmacen){
        if ($data) {
            $idProducto = $data['idProducto'];
            $producto = Producto::find($idProducto);
            if ($producto && $producto->es_padre) {
                Log::warning("[insertDetalleComprobante] Producto padre detectado (ID={$idProducto}). No admite stock fisico.");
                return;
            }

            $data['idDetalleComprobante'] = $this->getNewIdDetalleComprobante();
            Log::info("[insertDetalleComprobante] Creando DetalleComprobante ID={$data['idDetalleComprobante']} para Producto ID={$idProducto}. Total series en payload: " . count($registros));
            $detalle = $this->detalleComprobanteRepository->create($data);
            if ($detalle && $idAlmacen && !empty($registros)) {
                // Pre-calcular correlativo para series generadas automáticamente una sola vez
                $hasGenericSerial = false;
                foreach ($registros as $r) {
                    $s = $r['serialnumber'] ?? null;
                    if ($s === 0 || $s === '0' || empty($s)) {
                        $hasGenericSerial = true;
                        break;
                    }
                }

                $parcialCode = '';
                $serialCounter = 0;
                if ($hasGenericSerial && $producto) {
                    $modelOrCode = !empty($producto->modelo) ? $producto->modelo : $producto->codigoProducto;
                    $cleanedModel = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($modelOrCode));
                    $parcialCode = 'UNK-' . $cleanedModel;
                    $validateCode = $this->registroProductoRepository->searchList('numeroSerie', $parcialCode);
                    $serialCounter = 100000 + count($validateCode);
                    Log::info("[insertDetalleComprobante] Series genericas detectadas. Prefijo={$parcialCode}, contador inicial={$serialCounter}");
                }

                $nextIdRegistro = $this->getNewIdRegistro();
                $nextIdIngreso = $this->getNewIdIngreso();
                $user = $this->headerService->getModelUser();
                $idUser = $user ? $user->idUser : 1;

                Log::info("[insertDetalleComprobante] Iniciando insercion de registros. nextIdRegistro={$nextIdRegistro}, nextIdIngreso={$nextIdIngreso}, idUser={$idUser}");

                $itemsAgregados = 0;
                $tLoopStart = microtime(true);
                $batchRegistros = [];
                $batchIngresos = [];
                $nowTimestamp = now();

                foreach ($registros as $registro) {
                    $serial = $registro['serialnumber'] ?? null;
                    if ($serial === 0 || $serial === '0' || empty($serial)) {
                        $serialCounter++;
                        $serialFinal = $parcialCode . '-' . $serialCounter;
                        $validate = null;
                    } else {
                        $serialFinal = $serial;
                        $validate = $this->productoRepository->validateSerial($idProducto, $serialFinal);
                    }

                    if (!$validate) {
                        $idReg = $nextIdRegistro++;
                        $idIng = $nextIdIngreso++;

                        $ubicacionEspecifica = (!empty($registro['idUbicacion']) && is_numeric($registro['idUbicacion']))
                            ? (int)$registro['idUbicacion']
                            : null;

                        $batchRegistros[] = [
                            'idRegistro' => $idReg,
                            'idDetalleComprobante' => $data['idDetalleComprobante'],
                            'numeroSerie' => $serialFinal,
                            'estado' => (!empty($registro['estado'])) ? $registro['estado'] : 'NUEVO',
                            'observacion' => (!empty($registro['observacion'])) ? $registro['observacion'] : null,
                            'idAlmacen' => $idAlmacen,
                            'ubicacion_especifica' => $ubicacionEspecifica,
                            'fechaMovimiento' => $nowTimestamp,
                        ];

                        $batchIngresos[] = [
                            'idIngreso' => $idIng,
                            'idRegistro' => $idReg,
                            'idUser' => $idUser,
                            'fechaIngreso' => $nowTimestamp,
                        ];

                        $itemsAgregados++;
                    } else {
                        Log::warning("[insertDetalleComprobante] Serie ya existente rechazada: {$serialFinal}");
                    }
                }

                // Inserción en bloques (chunks) de 250 registros para velocidad extrema y evitar saturar MySQL
                if (!empty($batchRegistros)) {
                    foreach (array_chunk($batchRegistros, 250) as $chunkReg) {
                        \App\Models\Inventario\RegistroProducto::insert($chunkReg);
                    }
                    foreach (array_chunk($batchIngresos, 250) as $chunkIng) {
                        \App\Models\Inventario\IngresoProducto::insert($chunkIng);
                    }
                }

                $durLoop = round(microtime(true) - $tLoopStart, 3);
                Log::info("[insertDetalleComprobante] Insercion en bucle completada. Items agregados: {$itemsAgregados} / " . count($registros) . " en {$durLoop}s");

                // Incrementar el stock del producto en el almacén una sola vez por todo el lote
                if ($itemsAgregados > 0) {
                    $inventario = \App\Models\Inventario\Inventario::where('idProducto', $idProducto)
                        ->where('idAlmacen', $idAlmacen)
                        ->first();
                    if (!$inventario) {
                        $inventario = \App\Models\Inventario\Inventario::create([
                            'idProducto' => $idProducto,
                            'idAlmacen' => $idAlmacen,
                            'stock' => 0
                        ]);
                    }
                    $inventario->increment('stock', $itemsAgregados);
                    Log::info("[insertDetalleComprobante] Stock incrementado en {$itemsAgregados} para Producto ID={$idProducto} en Almacen ID={$idAlmacen}.");

                    // Validar estado del producto (por si estaba AGOTADO, pasar a DISPONIBLE) una sola vez
                    $this->productoService->validateState($idProducto, true);
                } else {
                    Log::warning("[insertDetalleComprobante] ALERTA: No se agrego ningun item (itemsAgregados = 0).");
                }
            } else {
                Log::warning("[insertDetalleComprobante] No se insertaron series: detalle=" . ($detalle ? 'OK' : 'NULL') . ", idAlmacen={$idAlmacen}, registros vacios=" . (empty($registros) ? 'SI' : 'NO'));
            }
        }
    }

    private function generateSerial($idProduct){
        try {
            $product = $this->productoRepository->getOne('idProducto', $idProduct);
            
            if (!$product) {
                throw new \Exception('Producto no encontrado.');
            }
    
            $modelOrCode = !empty($product->modelo) ? $product->modelo : $product->codigoProducto;
            $cleanedModel = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($modelOrCode));
            
            $parcialCode = 'UNK-' . $cleanedModel;
            $validateCode = $this->registroProductoRepository->searchList('numeroSerie', $parcialCode);
            
            $code = $parcialCode . '-' . (100000 + (count($validateCode) + 1));
            
            return $code;
    
        } catch (\Exception $e) {
            throw new \Exception('Error al generar el número de serie: ' . $e->getMessage());
        }
    }

    private function insertRegistro(array $data){
        if($data){
            $data['idRegistro'] = $this->getNewIdRegistro();
            $data['fechaMovimiento'] = now();
            $registro = $this->registroProductoRepository->create($data);
            if($registro){
                $this->insertIngreso($data['idRegistro']);
            }
        }
    }

    private function insertIngreso($idRegistro){
        $user = $this->headerService->getModelUser();
        if($idRegistro){
            $data = [
                'idIngreso' => $this->getNewIdIngreso(),
                'idRegistro' => $idRegistro,
                'idUser' => $user->idUser,
                'fechaIngreso' => now(),
                ];
            $this->ingresoRepository->create($data);
        }
    }

    private function addStock($idProducto,$idAlmacen){
        $this->inventarioRepository->addStock($idProducto,$idAlmacen);
    }

    private function getNewIdIngreso(){
        $lastIngreso = $this->ingresoRepository->getLast();
        $id = $lastIngreso ? $lastIngreso->idIngreso : 0;
        
        return $id + 1;
    }

    private function getNewIdDetalleComprobante(){
        $lastComprobante = $this->detalleComprobanteRepository->getLast();
        $id = $lastComprobante ? $lastComprobante->idDetalleComprobante : 0;
        
        return $id + 1;
    }
    
    private function getNewIdComprobante(){
        $lastComprobante = $this->comprobanteRepository->getLast();
        $id = $lastComprobante ? $lastComprobante->idComprobante : 0;
        
        return $id + 1;
    }

    private function getNewIdRegistro(){
        $lastRegistro = $this->registroProductoRepository->getLast();
        $id = $lastRegistro ? $lastRegistro->idRegistro : 0;
        
        return $id + 1;
    }
}