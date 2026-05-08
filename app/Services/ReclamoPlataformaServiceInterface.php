<?php

namespace App\Services;

interface ReclamoPlataformaServiceInterface
{
    public function getAllReclamos();
    public function getReclamoById($id);
    public function createReclamo(array $data);
    public function updateReclamo($id, array $data);
    public function getAllTipos();
    public function createTipo(array $data);
    public function addSeguimiento($idReclamo, array $data);
    public function addDiagnostico($idReclamo, array $data);
}
