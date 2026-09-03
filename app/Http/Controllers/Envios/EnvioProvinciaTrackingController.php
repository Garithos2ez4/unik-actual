<?php

namespace App\Http\Controllers\Envios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Envios\EnvioProvincia;
use App\Services\HeaderServiceInterface;
use Illuminate\Support\Facades\DB;

class EnvioProvinciaTrackingController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function generarLinkPublico(Request $request)
    {
        try {
            $userModel = $this->headerService->getModelUser();

            $hasAccess = false;
            foreach ($userModel->Accesos as $acceso) {
                if ($acceso->idVista == 14) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso para generar enlaces.']);
            }

            $token = \Illuminate\Support\Str::random(32);

            $solicitud = \App\Models\Envios\SolicitudEnvio::create([
                'idUser'           => $userModel->idUser,
                'token'            => $token,
                'estado'           => 'PENDIENTE',
                'token_expires_at' => now()->addMinutes(20),
            ]);

            $baseUrl = rtrim(config('app.public_envio_url', url('/')), '/');
            $link = "{$baseUrl}/formulario-envio/{$token}";

            // Determinar si se está generando el link después del horario de cierre (17:00 Lima)
            $esDespues5pm    = es_despues_del_corte();
            $fechaRegistroReal = calcular_fecha_real_legible();

            return response()->json([
                'success'           => true,
                'link'              => $link,
                'idSolicitud'       => $solicitud->idSolicitud,
                'expira_en'         => '20 minutos',
                'es_despues_5pm'    => $esDespues5pm,
                'fecha_registro_real' => $fechaRegistroReal,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }


    public function obtenerSolicitudes(Request $request)
    {
        try {
            // Actualizar a EXPIRADO las solicitudes que ya pasaron su tiempo límite
            \App\Models\Envios\SolicitudEnvio::where('estado', 'PENDIENTE')
                ->whereNotNull('token_expires_at')
                ->where('token_expires_at', '<', now())
                ->update(['estado' => 'EXPIRADO']);

            // Solo traemos idUser y user para no cargar la bandeja (que es muy pesada)
            $query = \App\Models\Envios\SolicitudEnvio::with(['Usuario:idUser,user']);

            // Si pasan parametro ?estado=PROCESADO (u otro)
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $solicitudes = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $solicitudes
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Regenera un nuevo token para una solicitud existente (extiende la expiración).
     */
    public function regenerarLink($id)
    {
        try {
            $solicitud = \App\Models\Envios\SolicitudEnvio::findOrFail($id);
            $token = \Illuminate\Support\Str::random(32);

            $solicitud->update([
                'token' => $token,
                'estado' => 'PENDIENTE',
                'token_expires_at' => now()->addMinutes(20),
            ]);

            $baseUrl = rtrim(config('app.public_envio_url', url('/')), '/');
            $link = "{$baseUrl}/formulario-envio/{$token}";

            return response()->json([
                'success' => true,
                'link' => $link,
                'expira_en' => '20 minutos'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function trackFlores($id)
    {
        try {
            $envio = EnvioProvincia::findOrFail($id);

            if (empty($envio->numero_guia)) {
                return response()->json(['success' => false, 'message' => 'El envío no tiene un número de guía registrado.']);
            }

            // Validar que tenga el formato SERIE-NUMERO
            if (!str_contains($envio->numero_guia, '-')) {
                return response()->json(['success' => false, 'message' => 'El formato de la guía debe ser SERIE-NUMERO (Ej: 5984-49745364).']);
            }

            $partes = explode('-', $envio->numero_guia);
            $serie = trim($partes[0]);
            $numero = trim($partes[1]);

            // Flores usa diferentes códigos de documento: 09 (Guía), 03 (Boleta), 01 (Factura)
            // Intentaremos con los 3 hasta obtener resultados.
            $codigosDocumento = ['09', '03', '01'];
            $resultadosList = [];
            $ultimoMensaje = 'No se pudo conectar con el servidor de Transporte Flores.';

            foreach ($codigosDocumento as $codDoc) {
                $url = 'https://sfe.floreshnos.pe/ConsultaEncomiendas/Encomienda/ListEncomienda';
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->get($url, [
                        'Serie' => $serie,
                        'Numero' => $numero,
                        'Codi_documento' => $codDoc,
                        'Codi_empresa' => '1',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['EsCorrecto']) && $data['EsCorrecto'] == true) {
                        $list = $data['Valor']['List'] ?? [];
                        if (count($list) > 0) {
                            $resultadosList = $list;
                            break; // Encontramos datos, salimos del bucle
                        }
                    } else {
                        $ultimoMensaje = $data['Mensaje'] ?? 'Error desconocido de la agencia Flores.';
                    }
                }
            }

            if (count($resultadosList) > 0) {
                return response()->json([
                    'success' => true,
                    'data' => $resultadosList
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => [] // Devolvemos vacío para que el frontend maneje el mensaje
                ]);
            }

            return response()->json(['success' => false, 'message' => 'No se pudo conectar con el servidor de Transporte Flores.']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $th->getMessage()]);
        }
    }

    public function pendientesEnvios(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 9) {
                $fechaDia = $request->input('dia');
                $fechaMes = $request->input('month');
                $fechaCarbon = null;

                $query = \App\Models\Envios\EnvioProvinciaProducto::with(['EnvioProvincia.Cliente', 'EnvioProvincia.Destino'])
                    ->whereNotNull('nota_producto')
                    ->where('nota_producto', 'LIKE', '%S/N:%');

                if ($fechaDia) {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaDia);
                    $query->whereHas('EnvioProvincia', function ($q) use ($fechaDia) {
                        $q->whereDate('fecha_envio', $fechaDia);
                    });
                } elseif ($fechaMes) {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaMes . '-01');
                    $query->whereHas('EnvioProvincia', function ($q) use ($fechaMes) {
                        $q->whereMonth('fecha_envio', date('m', strtotime($fechaMes)))
                            ->whereYear('fecha_envio', date('Y', strtotime($fechaMes)));
                    });
                } else {
                    $fechaCarbon = \Carbon\Carbon::now();
                    $query->whereHas('EnvioProvincia', function ($q) {
                        $q->whereMonth('fecha_envio', date('m'))
                            ->whereYear('fecha_envio', date('Y'));
                    });
                }

                $pes = $query->orderBy('idEnvioProvinciaProducto', 'desc')->get();

                $series = [];
                foreach ($pes as $pe) {
                    preg_match_all('/S\/N:\s*([^\s,]+)/', $pe->nota_producto, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $serial) {
                            $serialClasificado = trim($serial);
                            $registro = \App\Models\Inventario\RegistroProducto::with('DetalleComprobante.Producto')
                                ->where('numeroSerie', $serialClasificado)
                                ->where('estado', '!=', 'ENTREGADO')
                                ->first();

                            if ($registro) {
                                $series[] = [
                                    'idEnvioProducto' => $pe->idEnvioProvinciaProducto,
                                    'serial' => $serialClasificado,
                                    'producto' => $registro->DetalleComprobante->Producto->nombreProducto ?? 'Producto desconocido',
                                    'envio' => $pe->EnvioProvincia->toArray(),
                                    'cantidad' => $pe->cantidad
                                ];
                            }
                        }
                    }
                }

                return view('egresos.pendientes_envios', [
                    'user' => $userModel,
                    'series' => $series,
                    'fecha' => $fechaCarbon
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }


    public function markPendienteEgresado(Request $request)
    {
        $idEnvioProducto = $request->input('id_envio_producto');
        $serial = $request->input('serial');

        $envioProducto = \App\Models\Envios\EnvioProvinciaProducto::find($idEnvioProducto);
        if ($envioProducto && $envioProducto->nota_producto) {
            $nota = $envioProducto->nota_producto;
            $nota = preg_replace('/S\/N:\s*' . preg_quote($serial, '/') . '/', 'EGRESADO: ' . $serial, $nota);
            $envioProducto->nota_producto = $nota;
            $envioProducto->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'No encontrado']);
    }
}

