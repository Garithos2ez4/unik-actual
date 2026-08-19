<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Envios\EnvioProvincia;
use App\Models\Envios\EnvioProvinciaDetalle;
use App\Models\Usuarios\Cliente;
use App\Models\Envios\Departamento;
use App\Models\Envios\Agencia;
use App\Models\Envios\Provincia;
use App\Models\Envios\Destino;
use App\Models\Envios\SubAgencia;
use App\Models\Usuarios\TipoDocumento;
use Carbon\Carbon;

class FormularioPublicoController extends Controller
{
    /**
     * Muestra el formulario público para que el cliente llene sus datos.
     */
    public function show($token)
    {
        $solicitud = \App\Models\Envios\SolicitudEnvio::where('token', $token)->first();

        if (!$solicitud) {
            return view('public.formulario_error', [
                'titulo'  => 'Enlace no válido',
                'mensaje' => 'Este enlace de formulario no existe o fue eliminado.'
            ]);
        }

        if ($solicitud->estado === 'PROCESADO') {
            return view('public.formulario_error', [
                'titulo'  => 'Formulario ya completado',
                'mensaje' => 'Este formulario ya fue llenado previamente. Si necesitas modificar tus datos, contacta al vendedor.'
            ]);
        }

        if ($solicitud->token_expires_at && Carbon::now()->gt($solicitud->token_expires_at)) {
            return view('public.formulario_error', [
                'titulo'  => 'Enlace expirado',
                'mensaje' => 'Este enlace ha expirado. Solicita un nuevo enlace a tu vendedor.'
            ]);
        }

        $departamentos = Departamento::orderBy('nombre', 'asc')->get();
        $agencias      = Agencia::where('estado', 1)->orderBy('nombre', 'asc')->get();
        $documentos    = TipoDocumento::all();

        $minutosRestantes = $solicitud->token_expires_at
            ? Carbon::now()->diffInSeconds($solicitud->token_expires_at, false)
            : null;

        // Calcular si el link fue generado después de las 5pm Lima
        $creadoEn       = Carbon::parse($solicitud->created_at)->setTimezone('America/Lima');
        $esDespues5pm   = es_despues_del_corte($creadoEn);
        $fechaRegistroReal = null;
        if ($esDespues5pm) {
            $fechaRegistroReal = calcular_fecha_real_legible($creadoEn);
        }

        return view('public.formulario_envio', [
            'solicitud'         => $solicitud,
            'token'             => $token,
            'departamentos'     => $departamentos,
            'agencias'          => $agencias,
            'documentos'        => $documentos,
            'segundosRestantes' => $minutosRestantes,
            'esDespues5pm'      => $esDespues5pm,
            'fechaRegistroReal' => $fechaRegistroReal,
        ]);
    }

    /**
     * Procesa el formulario público del cliente.
     */
    public function store(Request $request, $token)
    {
        $solicitud = \App\Models\Envios\SolicitudEnvio::where('token', $token)->first();

        if (!$solicitud || $solicitud->estado === 'PROCESADO') {
            return view('public.formulario_error', [
                'titulo'  => 'Error',
                'mensaje' => 'Este formulario ya no está disponible.'
            ]);
        }

        if ($solicitud->token_expires_at && Carbon::now()->gt($solicitud->token_expires_at)) {
            return view('public.formulario_error', [
                'titulo'  => 'Enlace expirado',
                'mensaje' => 'Lo sentimos, el tiempo para completar este formulario ha expirado. Solicita un nuevo enlace.'
            ]);
        }

        $request->validate([
            'idTipoDocumento' => 'required|integer',
            'numeroDocumento' => 'required|string|max:20',
            'nombre'          => 'required|string|max:100',
            'apellidoPaterno' => 'required_unless:idTipoDocumento,3|nullable|string|max:100',
            'apellidoMaterno' => 'nullable|string|max:100',
            'telefono'        => 'required|string|max:20',
            'telefono_registrante' => 'nullable|string|max:20',
            'idDestino'       => 'required|integer',
            'idAgencia'       => 'required|integer',
            'correo'          => 'nullable|email:rfc,dns',
        ], [
            'correo.email' => 'El formato del correo no es válido.',
            'correo.dns'   => 'El dominio del correo no existe o no puede recibir mensajes.'
        ]);

        $tipoDoc = (int)$request->idTipoDocumento;
        $numDoc  = trim($request->numeroDocumento);

        if ($tipoDoc === 1 && !preg_match('/^[0-9]{8}$/', $numDoc)) {
            return back()->withErrors(['numeroDocumento' => 'El DNI debe tener 8 dígitos numéricos.'])->withInput();
        }
        if ($tipoDoc === 3 && !preg_match('/^(10|15|17|20)[0-9]{9}$/', $numDoc)) {
            return back()->withErrors(['numeroDocumento' => 'El RUC debe tener 11 dígitos numéricos y comenzar con 10, 15, 17 o 20.'])->withInput();
        }

        // Buscar o crear cliente
        $cliente = Cliente::where('numeroDocumento', $request->numeroDocumento)->first();

        if ($cliente) {
            $cliente->update([
                'nombre'          => $request->nombre,
                'apellidoPaterno' => $request->apellidoPaterno,
                'apellidoMaterno' => $request->apellidoMaterno ?? '',
                'telefono'        => $request->telefono,
                'correo'          => $request->correo ?? $cliente->correo,
                'idTipoDocumento' => $request->idTipoDocumento,
            ]);
        } else {
            $lastCliente = Cliente::orderBy('idCliente', 'desc')->first();
            $newId       = $lastCliente ? $lastCliente->idCliente + 1 : 1;

            $cliente = Cliente::create([
                'idCliente'       => $newId,
                'nombre'          => $request->nombre,
                'apellidoPaterno' => $request->apellidoPaterno,
                'apellidoMaterno' => $request->apellidoMaterno ?? '',
                'numeroDocumento' => $request->numeroDocumento,
                'idTipoDocumento' => $request->idTipoDocumento,
                'telefono'        => $request->telefono,
                'correo'          => $request->correo ?? '',
            ]);
        }

        // Crear el envío definitivo con fecha correcta (respeta corte de 5pm del momento de creación del link)
        $envio = EnvioProvincia::create([
            'idUser'      => $solicitud->idUser,
            'idCliente'   => $cliente->idCliente,
            'idDestino'   => $request->idDestino,
            'idAgencia'   => $request->idAgencia,
            'idSubAgencia' => $request->idSubAgencia ?? null,
            'idPlataforma' => 3, // WhatsApp por defecto
            'fecha_envio'  => resolver_fecha_envio(Carbon::parse($solicitud->created_at)->setTimezone('America/Lima')),
            'pago_destino' => 0
        ]);

        // Guardar dirección y referencia en detalle
        $detalle = EnvioProvinciaDetalle::create([
            'idEnvioProvincia' => $envio->idEnvioProvincia,
            'entrega_domicilio' => $request->has('entrega_domicilio') ? 1 : 0,
            'dir'    => $request->dir ?? '',
            'ref'    => $request->ref ?? '',
            'origen' => 'FORMULARIO_PUBLICO',
            'telefono_registrante' => $request->telefono_registrante ?? null,
        ]);

        // Guardar datos del receptor si se proporcionaron (Shalom + RUC)
        if ($request->has('receptor') && !empty($request->input('receptor.nombre')) && !empty($request->input('receptor.dni'))) {
            \App\Models\Envios\EnvioProvinciaReceptor::create([
                'id_envio_provincia_detalle' => $detalle->idEnvioProvinciaDetalle,
                'nombre'   => $request->input('receptor.nombre'),
                'dni'      => $request->input('receptor.dni'),
                'telefono' => $request->input('receptor.telefono'),
            ]);
        }

        // Marcar solicitud como procesada
        $solicitud->update(['estado' => 'PROCESADO']);

        return view('public.formulario_completado', [
            'nombre' => $request->nombre
        ]);
    }

    // ─── Endpoints AJAX públicos para cascada de ubigeo ─────────

    public function provincias($idDepartamento)
    {
        $provincias = Provincia::where('idDepartamento', $idDepartamento)
            ->orderBy('nombre', 'asc')
            ->get(['idProvincia', 'nombre']);

        return response()->json($provincias);
    }

    public function destinos($idProvincia)
    {
        $destinos = Destino::where('idProvincia', $idProvincia)
            ->orderBy('nombre', 'asc')
            ->get(['idDestino', 'nombre']);

        return response()->json($destinos);
    }

    public function subagencias($idAgencia, $idDestino)
    {
        $query = \App\Models\Envios\SubAgencia::where('idAgencia', $idAgencia)
            ->where('idDestino', $idDestino)
            ->where('estado', 1);

        if ($idAgencia == 3) {
            $query->where('nombre_oficina', 'NOT LIKE', '%AGENTE%')
                  ->where('nombre_oficina', 'NOT LIKE', '%AG.%');
        }

        $subagencias = $query->orderBy('nombre_oficina', 'asc')
            ->get(['idSubAgencia', 'nombre_oficina', 'direccion']);

        // Filtrar agencias de Shalom que no reciben paquetes (Ej. Aeropuertos, México, Luna Pizarro)
        if ($idAgencia == 1) { // 1 = SHALOM
            try {
                // Obtener las restricciones usando el controlador (consulta API en tiempo real + caché local)
                $shalomController = app(\App\Http\Controllers\Api\ShalomTarifaController::class);
                $response = $shalomController->getRestricciones();
                $restriccionesData = json_decode($response->getContent(), true)['data'] ?? [];

                if (!empty($restriccionesData)) {
                    $subagencias = $subagencias->filter(function ($sub) use ($restriccionesData) {
                        $partes = explode(' / ', $sub->nombre_oficina);
                        $nombreOficina = trim(end($partes));
                        $nombreNorm = preg_replace('/\s+/', ' ', strtoupper($nombreOficina));

                        $restriccion = null;
                        foreach ($restriccionesData as $term) {
                            $terminalName = $term['nombre_terminal'] ?? '';
                            $terminalNorm = preg_replace('/\s+/', ' ', strtoupper(trim($terminalName)));
                            if (empty($terminalNorm)) continue;

                            if ($terminalNorm === $nombreNorm || str_contains($nombreNorm, $terminalNorm) || str_contains($terminalNorm, $nombreNorm)) {
                                $restriccion = $term;
                                break;
                            }
                        }

                        if (!$restriccion) {
                            return true;
                        }

                        $puedeRecibir = isset($restriccion['recibe'])
                            && is_array($restriccion['recibe'])
                            && !empty($restriccion['recibe'])
                            && isset($restriccion['recibe']['hasta']);

                        return $puedeRecibir;
                    })->values();
                }
            } catch (\Exception $e) {
                // Si falla la consulta a la API, no filtramos y mostramos todo por defecto
            }
        }

        return response()->json($subagencias);
    }

    public function buscarCliente($documento)
    {
        $cliente = \App\Models\Usuarios\Cliente::where('numeroDocumento', $documento)->first();
        if ($cliente) {
            $ultimoEnvio = \App\Models\Envios\EnvioProvincia::with(['Detalle', 'Destino.Provincia'])
                ->where('idCliente', $cliente->idCliente)
                ->orderBy('idEnvioProvincia', 'desc')
                ->first();

            $envioData = null;
            if ($ultimoEnvio) {
                $envioData = [
                    'idDepartamento'   => $ultimoEnvio->Destino->Provincia->idDepartamento ?? null,
                    'idProvincia'      => $ultimoEnvio->Destino->idProvincia ?? null,
                    'idDestino'        => $ultimoEnvio->idDestino,
                    'idAgencia'        => $ultimoEnvio->idAgencia,
                    'idSubAgencia'     => $ultimoEnvio->idSubAgencia,
                    'entrega_domicilio' => $ultimoEnvio->Detalle->entrega_domicilio ?? 0,
                    'dir'              => $ultimoEnvio->Detalle->dir ?? '',
                    'ref'              => $ultimoEnvio->Detalle->ref ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'cliente' => [
                    'nombre'          => $cliente->nombre,
                    'apellidoPaterno' => $cliente->apellidoPaterno,
                    'apellidoMaterno' => $cliente->apellidoMaterno,
                    'telefono'        => $cliente->telefono,
                    'correo'          => $cliente->correo,
                    'idTipoDocumento' => $cliente->idTipoDocumento,
                ],
                'ultimo_envio' => $envioData
            ]);
        }
        return response()->json(['success' => false]);
    }
}
