<?php
namespace App\Repositories;

use App\Models\UbicacionAlmacen;

class UbicacionAlmacenRepository implements UbicacionAlmacenRepositoryInterface
{
    public function all()
    {
        return UbicacionAlmacen::all();
    }

    public function getByAlmacen($idAlmacen)
    {
        return UbicacionAlmacen::where('idAlmacen', $idAlmacen)->get();
    }

    public function create(array $data)
    {
        return UbicacionAlmacen::create($data);
    }

    public function update($id, array $data)
    {
        $ubicacion = UbicacionAlmacen::find($id);
        if ($ubicacion) {
            $ubicacion->update($data);
            return $ubicacion;
        }
        return null;
    }

    public function delete($id)
    {
        $ubicacion = UbicacionAlmacen::find($id);
        if ($ubicacion) {
            return $ubicacion->delete();
        }
        return false;
    }
}
