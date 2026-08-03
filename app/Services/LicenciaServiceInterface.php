<?php

namespace App\Services;

interface LicenciaServiceInterface
{
    public function getLicenciasNuevasQuery();
    public function buscarLicenciaPorCodigo(string $voucherCode);
    /**
     * Importar licencias desde un archivo Excel.
     *
     * @param mixed $archivo
     * @return void
     */
    public function importarDesdeExcel($archivo, array $datosFijos);

    /**
     * Obtener licencias en estado NUEVA.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLicenciasNuevas();

    /**
     * Crear una nueva licencia.
     *
     * @param array $data
     * @return \App\Models\Licencias\Licencia
     */
    public function crearLicencia(array $data);

    /**
     * Registrar una licencia (forma genérica).
     *
     * @param array $data
     * @return \App\Models\Licencias\Licencia
     */
    public function registrar(array $data);

    /**
     * Importar licencias desde un array de datos.
     *
     * @param array $data
     * @return void
     */
    public function importarDesdeArray(array $data);

    /**
     * Cambiar el estado de una licencia y registrar información adicional.
     *
     * @param string $serial
     * @param string $nuevoEstado
     * @param array $datosForm
     * @return \App\Models\Licencias\Licencia
     */
    public function cambiarEstadoConFormulario(string $serial, string $nuevoEstado, array $datosForm);
}
