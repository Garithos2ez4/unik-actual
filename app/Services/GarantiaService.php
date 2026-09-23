<?php

namespace App\Services;

use App\Repositories\GarantiaRepositoryInterface;
use App\Repositories\RegistroProductoRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;
use App\Services\ReclamoPlataformaServiceInterface;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GarantiaService implements GarantiaServiceInterface
{
    protected $garantiaRepository;
    protected $tipoDocumentoRepository;
    protected $registroRepository;
    protected $reclamoService;

    public function __construct(
        GarantiaRepositoryInterface $garantiaRepository,
        TipoDocumentoRepositoryInterface $tipoDocumentoRepository,
        RegistroProductoRepositoryInterface $registroRepository,
        ReclamoPlataformaServiceInterface $reclamoService
    ) {
        $this->garantiaRepository = $garantiaRepository;
        $this->tipoDocumentoRepository = $tipoDocumentoRepository;
        $this->registroRepository = $registroRepository;
        $this->reclamoService = $reclamoService;
    }

    public function getGarantiasByMonth(Carbon $date, int $cant)
    {
        return $this->garantiaRepository->paginateAllByMonth($date, $cant);
    }

    public function getAllTipoDocumentos()
    {
        return $this->tipoDocumentoRepository->all();
    }

    public function insertGarantia($idRegistro, $idCliente, $numeroComprobante, $recepcion, $estado, $fallo)
    {
        $data = [
            'idGarantia' => $this->getNewIdGarantia(),
            'idRegistro' => $idRegistro,
            'idCliente' => $idCliente,
            'fechaGarantia' => now(),
            'numeroComprobante' => $numeroComprobante,
            'recepcion' => $recepcion,
            'estado' => $estado,
            'falla' => $fallo
        ];

        $this->garantiaRepository->create($data);
        $this->updateStateGarantia($data['idRegistro'], $fallo);

        // Integración con Reclamos
        $registroProducto = \App\Models\Inventario\RegistroProducto::with('Egresos.Publicacion.CuentasPlataforma')->find($idRegistro);
        $idPlataforma = 7; // Valor por defecto: "Tienda"
        $idCuentaPlataforma = 0; // Valor por defecto
        $idPublicacion = null;
        $ordenCompra = $numeroComprobante;

        if ($registroProducto) {
            $egreso = $registroProducto->Egresos->first();
            if ($egreso) {
                $ordenCompra = $egreso->numeroOrden ?: $numeroComprobante;
                $idPublicacion = $egreso->idPublicacion;
                if ($egreso->Publicacion && $egreso->Publicacion->CuentasPlataforma) {
                    $idPlataforma = $egreso->Publicacion->CuentasPlataforma->idPlataforma;
                    $idCuentaPlataforma = $egreso->Publicacion->CuentasPlataforma->idCuentaPlataforma;
                }
            }
        }

        $dataReclamo = [
            'idCliente' => $idCliente,
            'idRegistro' => $idRegistro,
            'numeroCaso' => 'GAR-' . $data['idGarantia'],
            'ordenCompra' => $ordenCompra,
            'fechaReclamo' => now()->format('Y-m-d'),
            'estadoGeneral' => 'ABIERTO',
            'detalleReclamo' => $fallo,
            'idUser' => Auth::id() ?? 1,
            'idPlataforma' => $idPlataforma,
            'idCuentaPlataforma' => $idCuentaPlataforma,
            'idPublicacion' => $idPublicacion,
            'idTipoReclamoPlataforma' => 1
        ];

        $this->reclamoService->createReclamo($dataReclamo);
        $response = $this->garantiaRepository->getOne($data['idGarantia']);
        return $response;
    }

    public function searchAjaxRegistro($serial)
    {
        $egresos = $this->registroRepository->searchByGarantia($serial, 5);
        $result = $egresos->map(function ($details) {
            return [
                'nombreProducto' => $details->DetalleComprobante->Producto->nombreProducto,
                'codigoProducto' => $details->DetalleComprobante->Producto->codigoProducto,
                'idRegistroProducto' => $details->idRegistro,
                'numeroSerie' => $details->numeroSerie,
                'estado' => $details->estado,
                'modelo' => $details->DetalleComprobante->Producto->modelo,
                'image' => $details->DetalleComprobante->Producto->imagenProducto1,
                'marca' => $details->DetalleComprobante->Producto->MarcaProducto->nombreMarca
            ];
        });
        return $result;
    }

    public function getOneAjaxRegistro($serial)
    {
        $egreso = $this->registroRepository->getByGarantia($serial);

        if ($egreso) {
            $details = $egreso->DetalleComprobante->Producto;

            $result = [
                'nombreProducto' => $details->nombreProducto,
                'codigoProducto' => $details->codigoProducto,
                'idRegistroProducto' => $egreso->idRegistro,
                'numeroSerie' => $egreso->numeroSerie,
                'estado' => $egreso->estado,
                'modelo' => $details->modelo,
                'image' => $details->imagenProducto1,
                'marca' => $details->MarcaProducto->nombreMarca
            ];
            return $result;
        }

        return [];
    }

    private function updateStateGarantia($idRegistro, $falla)
    {
        if (!empty($idRegistro)) {
            $data = [
                'estado' => 'GARANTIA',
                'observacion' => $falla
            ];
            $this->registroRepository->update($idRegistro, $data);
        }
    }

    private function getNewIdGarantia()
    {
        $garantia = $this->garantiaRepository->getLast();
        $id = $garantia ? $garantia->idGarantia : 0;
        return $id + 1;
    }
}
