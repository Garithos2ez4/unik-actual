<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Services\CalculadoraServiceInterface;
use App\Services\ConfiguracionServiceInterface;
use App\Services\HeaderServiceInterface;
use Illuminate\Http\Request;

class ConfigCalculosController extends Controller
{
    protected $headerService;
    protected $calculadoraService;
    protected $configuracionService;

    public function __construct(
        HeaderServiceInterface $headerService,
        CalculadoraServiceInterface $calculadoraService,
        ConfiguracionServiceInterface $configuracionService
    ) {
        $this->headerService = $headerService;
        $this->calculadoraService = $calculadoraService;
        $this->configuracionService = $configuracionService;
    }

    public function calculos()
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $categorias = $this->configuracionService->getAllCategorias();
                $rangos = $this->configuracionService->getAllRangos();

                $calculos = $this->calculadoraService->get();

                $calculosFijo = $this->calculadoraService->getTasaFija();

                $empresas = $this->configuracionService->getAllEmpresas();

                $plataformas = $this->configuracionService->getAllPlataformas();


                return view('configuracion.configcalculos', [
                    'user' => $userModel,
                    'pagina' => 'calculos',
                    'empresas' => $empresas,
                    'calculos' => $calculos,
                    'calculosfijo' => $calculosFijo,
                    'categorias' => $categorias,
                    'rangos' => $rangos,
                    'plataformas' => $plataformas
                ]);
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    /**
     * Aplica una plantilla de comisiones uniforme a todos los grupos de una categoría.
     * Template basado en la estructura estándar (9 rangos ordenados).
     */
    public function aplicarComisionUniforme(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                $idCategoria = $request->input('idCategoria');
                $comisionesTemplate = $request->input('comision'); // array [idRango => valor]

                if (empty($idCategoria) || empty($comisionesTemplate)) {
                    $this->headerService->sendFlashAlerts('Error', 'Datos incompletos', 'error', 'btn-danger');
                    return back();
                }

                $grupos = \App\Models\Catalogo\GrupoProducto::where('idCategoria', $idCategoria)->get();

                foreach ($grupos as $grupo) {
                    foreach ($comisionesTemplate as $idRango => $comision) {
                        $this->configuracionService->updateComisionValue($grupo->idGrupoProducto, $idRango, $comision);
                    }
                }

                $this->headerService->sendFlashAlerts(
                    'Comisiones actualizadas',
                    'Se aplicó la plantilla uniforme a todos los grupos de la categoría.',
                    'success',
                    'btn-success'
                );
                return back();
            }
        }

        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateComision(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $comisiones = $request->input('comision');
        $grupo = $request->input('grupo');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($comisiones) && !empty($grupo)) {
                    foreach ($comisiones as $rango => $comision) {
                        $this->configuracionService->updateComisionValue($grupo, $rango, $comision);
                    }
                    $this->headerService->sendFlashAlerts('Actualizacion correcta', 'Operacion realizada con exito', 'success', 'btn-success');
                    return back();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCalculosTasaFija(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $igv = $request->input('igv');
        $facturacion = $request->input('facturacion');
        $tasaCambio = $request->input('tasaCambio');
        $empresas = $request->input('empresas');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($igv) && !empty($facturacion) && !empty($tasaCambio) && !empty($empresas)) {
                    $this->configuracionService->updateCalculadoraTasaFija($igv, $facturacion, $tasaCambio);

                    foreach ($empresas as $idEmpresa => $comision) {
                        $this->configuracionService->updateComisionEmpresa($idEmpresa, $comision);
                    }

                    return back()->withInput();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function updateCalculos(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $igv = $request->input('igv');
        $facturacion = $request->input('facturacion');
        $empresas = $request->input('empresas');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (!empty($igv) && !empty($facturacion) && !empty($empresas)) {
                    $this->configuracionService->updateCalculadora($igv, $facturacion);

                    foreach ($empresas as $idEmpresa => $comision) {
                        $this->configuracionService->updateComisionEmpresa($idEmpresa, $comision);
                    }

                    return back()->withInput();
                } else {
                    $this->headerService->sendFlashAlerts('Error', 'Hubo un error en la operacion', 'error', 'btn-danger');
                    return back()->withInput();
                }
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function createComisionPlataforma(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idPlataforma = $request->input('plataforma');
        $comision = $request->input('comision');
        $flete = $request->input('flete');

        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($idPlataforma) && isset($comision) && isset($flete)) {
                    $this->configuracionService->createComisionPlataforma($idPlataforma, $comision, $flete);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function deleteComisionPlataforma(Request $request)
    {
        $userModel = $this->headerService->getModelUser();
        $idComisionPlataforma = $request->input('id');
        foreach ($userModel->Accesos as $acceso) {
            if ($acceso->idVista == 7) {
                if (isset($idComisionPlataforma)) {
                    $this->configuracionService->deleteComisionPlataforma($idComisionPlataforma);
                }
                return back();
            }
        }
        $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para realizar esta operacion', 'warning', 'btn-danger');
        return redirect()->route('dashboard', ['user' => $userModel]);
    }

    public function consultarTipoCambio(Request $request)
    {
        try {
            $client = new \GuzzleHttp\Client(['base_uri' => 'https://api.apis.net.pe', 'verify' => false]);

            $fecha = $request->query('fecha', date('Y-m-d'));

            $parameters = [
                'http_errors' => false,
                'connect_timeout' => 5,
                'headers' => [
                    'Referer' => 'https://apis.net.pe/api-sunat-tipo-de-cambio',
                    'User-Agent' => 'laravel/guzzle',
                    'Accept' => 'application/json',
                ],
                'query' => ['fecha' => $fecha]
            ];

            $res = $client->request('GET', '/v1/tipo-cambio-sunat', $parameters);
            $response = json_decode($res->getBody()->getContents(), true);

            if ($res->getStatusCode() == 200) {
                return response()->json(['success' => true, 'data' => $response]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['message'] ?? 'Error al consultar SUNAT Tipo de Cambio'
            ], $res->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}