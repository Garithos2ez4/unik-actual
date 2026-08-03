<?php
namespace App\Repositories;

use App\Models\Catalogo\Caracteristicas_Producto;

class CaracteristicasProductoRepository implements CaracteristicasProductoRepositoryInterface
{
    public function updateCaracteristica($idCaracteristica,$idProducto,$descripcion){
        $data = ['caracteristicaProducto' => $descripcion];
        $caracteristica = Caracteristicas_Producto::where('idProducto','=',$idProducto)->where('idCaracteristica','=',$idCaracteristica)->first();
        $caracteristica->update($data);
        return $caracteristica;
    }

    public function insertCaracteristica($idCaracteristica,$idProducto,$descripcion){
        $data = ['idCaracteristica' => $idCaracteristica,
                'idProducto' => $idProducto,
                'caracteristicaProducto' => $descripcion];
        return Caracteristicas_Producto::create($data);

    }

    public function deleteSpect($idProducto,$idCaracteristica){
        $caracteristica = Caracteristicas_Producto::where('idProducto','=',$idProducto)->where('idCaracteristica','=',$idCaracteristica)->first();
        $caracteristica->delete();
    }
}