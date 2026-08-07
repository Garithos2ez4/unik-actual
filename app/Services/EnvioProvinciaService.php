<?php

namespace App\Services;

use App\Models\Envios\EnvioProvincia;
use App\Models\Envios\EnvioProvinciaDetalle;
use App\Models\Envios\EnvioProvinciaProducto;
use App\Models\Envios\Agencia;
use App\Models\Envios\Provincia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnvioProvinciaService implements EnvioProvinciaServiceInterface
{
    public function createEnvio(array $data, array $productos)
    {
        DB::beginTransaction();
        try {
            $envio = EnvioProvincia::create($data);

            $hasAddress = isset($data['entrega_domicilio']) || isset($data['despachado']) || !empty($data['dir']) || !empty($data['ref']);
            $hasReceptor = isset($data['receptor']) && !empty($data['receptor']['nombre']) && !empty($data['receptor']['dni']);
            
            if ($hasAddress || $hasReceptor) {
                $detalle = EnvioProvinciaDetalle::create([
                    'idEnvioProvincia' => $envio->idEnvioProvincia,
                    'entrega_domicilio' => $data['entrega_domicilio'] ?? 0,
                    'despachado' => $data['despachado'] ?? 0,
                    'dir' => $data['dir'] ?? null,
                    'ref' => $data['ref'] ?? null,
                ]);

                if ($hasReceptor) {
                    \App\Models\Envios\EnvioProvinciaReceptor::create([
                        'id_envio_provincia_detalle' => $detalle->idEnvioProvinciaDetalle,
                        'nombre' => $data['receptor']['nombre'],
                        'dni' => $data['receptor']['dni'],
                        'telefono' => $data['receptor']['telefono'] ?? null,
                    ]);
                }
            }

            $hasDimensions = isset($data['peso']) || isset($data['largo']) || isset($data['ancho']) || isset($data['alto']) || isset($data['idTipoPaquete']);
            if ($hasDimensions) {
                \App\Models\Envios\EnvioDimension::create([
                    'idEnvioProvincia' => $envio->idEnvioProvincia,
                    'idTipoPaquete' => ($data['idTipoPaquete'] ?? 'custom') !== 'custom' ? $data['idTipoPaquete'] : null,
                    'largo_final' => $data['largo'] ?? 0,
                    'ancho_final' => $data['ancho'] ?? 0,
                    'alto_final' => $data['alto'] ?? 0,
                    'peso_final' => $data['peso'] ?? 0,
                    'precio_calculado' => $data['precio_envio'] ?? null,
                ]);
            }

            foreach ($productos as $prodData) {
                if (!empty($prodData['idProducto'])) {
                    EnvioProvinciaProducto::create([
                        'idEnvioProvincia' => $envio->idEnvioProvincia,
                        'idProducto' => $prodData['idProducto'],
                        'cantidad' => $prodData['cantidad'] ?? 1,
                        'nota_producto' => $prodData['nota_producto'] ?? null
                    ]);
                }
            }

            DB::commit();
            return $envio;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateEnvio($idEnvioProvincia, array $data, array $productos)
    {
        DB::beginTransaction();
        try {
            $envio = EnvioProvincia::findOrFail($idEnvioProvincia);
            $envio->update($data);

            $hasAddress = isset($data['entrega_domicilio']) || isset($data['despachado']) || !empty($data['dir']) || !empty($data['ref']);
            $hasReceptor = isset($data['receptor']) && !empty($data['receptor']['nombre']) && !empty($data['receptor']['dni']);

            if ($hasAddress || $hasReceptor) {
                $detalle = EnvioProvinciaDetalle::updateOrCreate(
                    ['idEnvioProvincia' => $envio->idEnvioProvincia],
                    [
                        'entrega_domicilio' => $data['entrega_domicilio'] ?? 0,
                        'despachado' => $data['despachado'] ?? 0,
                        'dir' => $data['dir'] ?? null,
                        'ref' => $data['ref'] ?? null,
                    ]
                );

                if ($hasReceptor) {
                    \App\Models\Envios\EnvioProvinciaReceptor::updateOrCreate(
                        ['id_envio_provincia_detalle' => $detalle->idEnvioProvinciaDetalle],
                        [
                            'nombre' => $data['receptor']['nombre'],
                            'dni' => $data['receptor']['dni'],
                            'telefono' => $data['receptor']['telefono'] ?? null,
                        ]
                    );
                } else {
                    if ($detalle) {
                        \App\Models\Envios\EnvioProvinciaReceptor::where('id_envio_provincia_detalle', $detalle->idEnvioProvinciaDetalle)->delete();
                    }
                }
            } else {
                $detalle = EnvioProvinciaDetalle::where('idEnvioProvincia', $envio->idEnvioProvincia)->first();
                if ($detalle) {
                    \App\Models\Envios\EnvioProvinciaReceptor::where('id_envio_provincia_detalle', $detalle->idEnvioProvinciaDetalle)->delete();
                    if ($detalle->origen !== 'FORMULARIO_PUBLICO') {
                        $detalle->delete();
                    }
                }
            }
            $hasDimensions = isset($data['peso']) || isset($data['largo']) || isset($data['ancho']) || isset($data['alto']) || isset($data['idTipoPaquete']);
            if ($hasDimensions) {
                \App\Models\Envios\EnvioDimension::updateOrCreate(
                    ['idEnvioProvincia' => $envio->idEnvioProvincia],
                    [
                        'idTipoPaquete' => ($data['idTipoPaquete'] ?? 'custom') !== 'custom' ? $data['idTipoPaquete'] : null,
                        'largo_final' => $data['largo'] ?? 0,
                        'ancho_final' => $data['ancho'] ?? 0,
                        'alto_final' => $data['alto'] ?? 0,
                        'peso_final' => $data['peso'] ?? 0,
                        'precio_calculado' => $data['precio_envio'] ?? null,
                    ]
                );
            } else {
                \App\Models\Envios\EnvioDimension::where('idEnvioProvincia', $envio->idEnvioProvincia)->delete();
            }

            EnvioProvinciaProducto::where('idEnvioProvincia', $envio->idEnvioProvincia)->delete();
            
            foreach ($productos as $prodData) {
                if (!empty($prodData['idProducto'])) {
                    EnvioProvinciaProducto::create([
                        'idEnvioProvincia' => $envio->idEnvioProvincia,
                        'idProducto' => $prodData['idProducto'],
                        'cantidad' => $prodData['cantidad'] ?? 1,
                        'nota_producto' => $prodData['nota_producto'] ?? null
                    ]);
                }
            }

            DB::commit();
            return $envio;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getEnvioById($id)
    {
        return EnvioProvincia::with(['Detalle', 'Destino.Provincia.Departamento', 'Productos.Producto', 'Dimension'])->findOrFail($id);
    }

    public function getUltimoEnvioCliente($idCliente)
    {
        return EnvioProvincia::with(['Detalle', 'Destino.Provincia.Departamento'])
            ->where('idCliente', $idCliente)
            ->orderBy('fecha_envio', 'desc')
            ->orderBy('idEnvioProvincia', 'desc')
            ->first();
    }

    public function createAgencia($nombre)
    {
        return Agencia::create([
            'nombre' => $nombre,
            'estado' => 1
        ]);
    }

    public function createProvincia($nombre)
    {
        return Provincia::create([
            'nombre' => $nombre
        ]);
    }

    public function createDestino($idProvincia, $nombre)
    {
        return Destino::create([
            'idProvincia' => $idProvincia,
            'nombre' => $nombre
        ]);
    }

    public function createSubAgencia($idAgencia, $idDestino, $nombre_oficina, $direccion, $telefono)
    {
        $existe = SubAgencia::where('idAgencia', $idAgencia)
            ->where('idDestino', $idDestino)
            ->where('nombre_oficina', $nombre_oficina)
            ->first();

        if ($existe) {
            throw new \Exception('Ya existe una oficina con este nombre en el destino seleccionado.');
        }

        return SubAgencia::create([
            'idAgencia' => $idAgencia,
            'idDestino' => $idDestino,
            'nombre_oficina' => $nombre_oficina,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'estado' => 1
        ]);
    }
}
