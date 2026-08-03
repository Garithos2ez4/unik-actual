<?php
namespace App\Repositories;
use App\Models\Licencias\Licencia;
use App\Repositories\LicenciaRepositoryInterface;

class LicenciaRepository implements LicenciaRepositoryInterface
{

    public function getNuevasQuery()
    {
        return Licencia::where('estado', 'NUEVA');
    }
    public function getByEstado(string $estado)
    {
        return Licencia::where('estado', $estado)->get();
    }
    public function allNuevas()
    {
        return Licencia::where('estado', 'NUEVA')->get();
    }

    public function crear(array $data)
    {
        return Licencia::create($data);
    }
    
    public function storeFromExcel(array $rows)
    {
        foreach ($rows as $row) {
            $this->crear($row);
        }
    }

    public function cambiarEstado(string $serial, string $nuevoEstado, array $datosAdicionales)
    {
        $licencia = Licencia::where('voucher_code', $serial)->firstOrFail();
        $licencia->estado = $nuevoEstado;
        $licencia->save();

        // Aquí delegas el resto al service
        return $licencia;
    }

    public function findBySerial(string $serial)
    {
        return Licencia::where('serial_number', $serial)->first();
    }
    public function findByCode(string $voucher)
    {
        return Licencia::where('voucher_code', $voucher)->first();
    }
}
