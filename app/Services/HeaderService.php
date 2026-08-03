<?php
namespace App\Services;

use App\Models\Usuarios\Usuario;
use App\Models\Ventas\Transaccion;
use InvalidArgumentException;

class HeaderService implements HeaderServiceInterface
{
    public function getModelUser(){
        $idUser = session()->get('idUser',-1);
        $userModel = Usuario::select('idUser','user','bandeja')->where('idUser','=',$idUser)->first();
        return $userModel;
    }
    
    public function newTransaccion($referencia,$descripcion,$tipo){
        $usuario = $this->getModelUser();
        $newId = $this->generateIdTransaccion();
        
        $transaccion = new Transaccion();
        $transaccion->idTransaccion = $newId;
        $transaccion->idUser = $usuario->idUser;
        $transaccion->referencia = $referencia;
        $transaccion->descripcion = $descripcion;
        $transaccion->tipo = $tipo;
        $transaccion->fechaTransaccion = now();
        
        $transaccion->save();
        
        return $transaccion;
    }

    public function sendFlashAlerts($title, $message, $icon, $button){
        $acceptedIcons = ['success', 'error', 'warning', 'info', 'question'];

        if (is_null($title) || is_null($message) || is_null($icon) || is_null($button)) {
            throw new InvalidArgumentException('Se requieren cuatro variables para las alertas flas.');
        }

        if (!in_array($icon, $acceptedIcons)) {
            throw new InvalidArgumentException('El icono proporcionado no es válido.');
        }

        session()->flash('title', $title);
        session()->flash('message', $message);
        session()->flash('icon', $icon);
        session()->flash('button', $button);
    }
    
    private function generateIdTransaccion(){
        $id = Transaccion::select('idTransaccion')->orderBy('idTransaccion','desc')->first();
        return $id->idTransaccion + 1;
    }
}