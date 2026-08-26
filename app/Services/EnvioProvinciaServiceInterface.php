<?php

namespace App\Services;

interface EnvioProvinciaServiceInterface
{
    public function createEnvio(array $data, array $productos);
    public function updateEnvio($idEnvioProvincia, array $data, array $productos);
    public function getEnvioById($id);
    public function getUltimoEnvioCliente($idCliente);
    public function createAgencia($nombre);
    public function createProvincia($nombre);
    public function createDestino($idProvincia, $nombre);
    public function createSubAgencia($idAgencia, $idDestino, $nombre_oficina, $direccion, $telefono);
    public function getTopProvincias($fechaInicio, $fechaFin, $limit = 5);
}
