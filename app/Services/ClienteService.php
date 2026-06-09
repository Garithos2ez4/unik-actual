<?php
namespace App\Services;

use App\Repositories\ClienteRepositoryInterface;
use App\Repositories\TipoDocumentoRepositoryInterface;

class ClienteService implements ClienteServiceInterface
{
    protected $tipoDocumentoRepository;
    protected $clienteRepository;

    public function __construct(TipoDocumentoRepositoryInterface $tipoDocumentoRepository,
                                ClienteRepositoryInterface $clienteRepository)
    {
        $this->tipoDocumentoRepository = $tipoDocumentoRepository;
        $this->clienteRepository = $clienteRepository;
    }

    public function getAllTipoDocumentos(){
        return $this->tipoDocumentoRepository->all();
    }

    public function createCliente($nombre,$apePaterno,$apeMaterno,$tipoDoc,$numeroDoc,$telefono,$correo)
    {
        if(!empty($nombre) && !empty($tipoDoc) && !empty($numeroDoc)){
            $validate = $this->clienteRepository->validateDuplicity($tipoDoc,$numeroDoc);
            if($validate){
                $message = 'Cliente ya registrado en la base de datos.';
                return $message;
            }
            $data = ['idCliente' => $this->getNewIdCliente(),
                    'nombre' => $nombre,
                    'apellidoPaterno' => $apePaterno,
                    'apellidoMaterno' => $apeMaterno,
                    'numeroDocumento' => $numeroDoc,
                    'idTipoDocumento' => $tipoDoc,
                    'telefono' => $telefono,
                    'correo' => $correo];

            $this->clienteRepository->create($data);
            $response = $this->clienteRepository->getOne('idCliente',$data['idCliente'])->load('TipoDocumento');
            return $response;
        }
        return null;
    }

    public function updateCliente($id,$nombre,$apePaterno,$apeMaterno,$tipoDoc,$numeroDoc,$telefono,$correo)
    {
        if(!empty($nombre) && !empty($tipoDoc) && !empty($numeroDoc)){
            $cliente = $this->clienteRepository->getOne('idCliente', $id);
            if(!$cliente){
                return 'Cliente no encontrado.';
            }

            // Validar si otro cliente tiene el mismo documento
            $validate = $this->clienteRepository->validateDuplicity($tipoDoc,$numeroDoc);
            if($validate && $validate->idCliente != $id){
                return 'El documento ya está registrado en otro cliente.';
            }

            $data = [
                    'nombre' => $nombre,
                    'apellidoPaterno' => $apePaterno,
                    'apellidoMaterno' => $apeMaterno,
                    'numeroDocumento' => $numeroDoc,
                    'idTipoDocumento' => $tipoDoc,
                    'telefono' => $telefono,
                    'correo' => $correo];

            $this->clienteRepository->update($data, $id);
            $response = $this->clienteRepository->getOne('idCliente', $id)->load('TipoDocumento');
            return $response;
        }
        return null;
    }

    public function paginateAllCliente($cant){
        return $this->clienteRepository->all($cant);
    }

    public function searchAjaxCLiente($doc){
        return $this->clienteRepository->searchCliente($doc,5)->load('TipoDocumento');
    }

    public function searchClientePaginated($doc, $cant){
        return $this->clienteRepository->searchClientePaginated($doc, $cant);
    }

    private function getNewIdCliente(){
        $lastId = $this->clienteRepository->getLast();
        $id = $lastId ? $lastId->idCliente : 0;
        return $id + 1;
    }
}