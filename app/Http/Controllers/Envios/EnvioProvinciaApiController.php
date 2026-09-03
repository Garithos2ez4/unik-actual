<?php

namespace App\Http\Controllers\Envios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Envios\EnvioProvincia;
use App\Models\Envios\Provincia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;

class EnvioProvinciaApiController extends Controller
{
    public function buscarRegistro(Request $request)
    {
        $query = trim($request->get('query'));

        if (empty($query)) {
            return response()->json([]);
        }

        $terminos = array_filter(explode(' ', $query), 'strlen');

        // 1. Buscar Registros donde el numero de serie coincida con el query (búsqueda manual de serie)
        $registrosPorSerie = \App\Models\Inventario\RegistroProducto::with(['DetalleComprobante.Producto'])
            ->where('estado', '!=', 'ENTREGADO')
            ->where('estado', '!=', 'INVALIDO')
            ->where('numeroSerie', 'LIKE', '%' . $query . '%')
            ->take(15)
            ->get();

        // 2. Buscar Productos que coincidan con los términos (nombre, codigo, modelo)
        $productos = \App\Models\Catalogo\Producto::where(function ($subQ) use ($terminos) {
            foreach ($terminos as $termino) {
                $subQ->where(function ($wQ) use ($termino) {
                    $wQ->where('nombreProducto', 'LIKE', '%' . $termino . '%')
                        ->orWhere('codigoProducto', 'LIKE', '%' . $termino . '%')
                        ->orWhere('modelo', 'LIKE', '%' . $termino . '%');
                });
            }
        })
            ->take(20)
            ->get();

        $resultadosFinales = collect();
        $productosAgregados = [];

        // Agregar los encontrados por serie directamente, pero solo UNO por producto
        // Así, si buscan "524", no agregamos los 15 seriales de la misma tinta, sino solo el primero.
        // Pero si buscan "100004", encontrará ese específicamente y lo agregará.
        foreach ($registrosPorSerie as $reg) {
            $idProd = $reg->DetalleComprobante?->Producto?->idProducto;
            if ($idProd && !in_array($idProd, $productosAgregados)) {
                $resultadosFinales->push($reg);
                $productosAgregados[] = $idProd;
            }
        }

        // Para los productos encontrados que no han sido agregados por la búsqueda de serie,
        // buscar UN registro disponible (el primero)
        foreach ($productos as $prod) {
            if (!in_array($prod->idProducto, $productosAgregados)) {
                $primerRegistro = \App\Models\Inventario\RegistroProducto::with(['DetalleComprobante.Producto'])
                    ->where('estado', '!=', 'ENTREGADO')
                    ->where('estado', '!=', 'INVALIDO')
                    ->whereHas('DetalleComprobante', function ($q) use ($prod) {
                        $q->where('idProducto', $prod->idProducto);
                    })
                    ->first();

                if ($primerRegistro) {
                    $resultadosFinales->push($primerRegistro);
                } else {
                    $resultadosFinales->push([
                        'numeroSerie' => null,
                        'detalle_comprobante' => [
                            'producto' => [
                                'idProducto' => $prod->idProducto,
                                'nombreProducto' => $prod->nombreProducto,
                                'codigoProducto' => $prod->codigoProducto,
                                'modelo' => $prod->modelo,
                            ]
                        ]
                    ]);
                }
                $productosAgregados[] = $prod->idProducto;
            }
        }

        return response()->json($resultadosFinales->values()->all());
    }

    public function getUltimoEnvioCliente($idCliente)
    {
        $ultimoEnvio = $this->envioService->getUltimoEnvioCliente($idCliente);

        if ($ultimoEnvio) {
            return response()->json([
                'success' => true,
                'data' => $ultimoEnvio
            ]);
        }

        return response()->json(['success' => false]);
    }

    public function getProvinciasPorDepartamento($idDepartamento)
    {
        $provincias = Provincia::where('idDepartamento', $idDepartamento)
            ->orderBy('nombre', 'asc')
            ->get(['idProvincia', 'nombre']);

        return response()->json($provincias);
    }

    public function getDestinosPorProvincia($idProvincia)
    {
        $destinos = Destino::where('idProvincia', $idProvincia)
            ->orderBy('nombre', 'asc')
            ->get(['idDestino', 'nombre']);

        return response()->json($destinos);
    }

    public function getSubAgenciasPorAgenciaYDestino($idAgencia, $idDestino)
    {
        $subagencias = SubAgencia::where('idAgencia', $idAgencia)
            ->where('idDestino', $idDestino)
            ->where('estado', 1)
            ->orderBy('nombre_oficina', 'asc')
            ->get(['idSubAgencia', 'nombre_oficina', 'direccion', 'telefono']);

        return response()->json($subagencias);
    }

}

