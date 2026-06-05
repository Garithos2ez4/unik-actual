<?php
namespace App\Services;

use Carbon\Carbon;
use App\Repositories\PublicacionRepositoryInterface;
use App\Repositories\PlataformaRepositoryInterface;
use App\Repositories\CuentasPlataformaRepositoryInterface;

class PublicacionService implements PublicacionServiceInterface
{
    protected $publicacionRepository;
    protected $headerService;
    protected $plataformaRepository;
    protected $cuentasPlataformaRepository;

    public function __construct(PublicacionRepositoryInterface $publicacionRepository,
                                HeaderServiceInterface $headerService,
                                PlataformaRepositoryInterface $plataformaRepository,
                                CuentasPlataformaRepositoryInterface $cuentasPlataformaRepository)
    {
        $this->publicacionRepository = $publicacionRepository;
        $this->headerService = $headerService;
        $this->plataformaRepository = $plataformaRepository;
        $this->cuentasPlataformaRepository = $cuentasPlataformaRepository;
    }

    public function searchAjaxPubli($data){
        $publicaciones = $this->publicacionRepository->searchByEgreso($data,5)
                            ->map(function($details) {
                                             return [
                                                'sku' => $details->sku,
                                                'fechaPublicacion' => $details->fechaPublicacion->format('Y-m-d'),
                                                'idPublicacion' => $details->idPublicacion,
                                                'precio' => $details->precioPublicacion,
                                                'titulo' => $details->titulo,
                                                'modelo' => $details->Producto->modelo ?? '',
                                                'user' => $details->Usuario->user,
                                                'estado' => $details->estado
                                            ];
                                        });
        return $publicaciones;
    }

    public function getOneByPlataformaId($id){
        return $this->plataformaRepository->getOne('idPlataforma',$id);
    }

    public function getAllPlatafromasByTipe($type){
        return $this->plataformaRepository->getAllByColumn('tipoPlataforma',$type);
    }
    
    public function getOneById($id){
        return $this->publicacionRepository->getOne('idPublicacion',$id);
    }
    
    public function getAllPubliByMonth($month){
        Carbon::setLocale('es');
        $carbonMonth = Carbon::createFromFormat('Y-m', $month);
        return $this->publicacionRepository->getByMonth($carbonMonth->month,$carbonMonth->year)->sortByDesc('fechaPublicacion');
    }
    
    public function insertPublicacion(array $data){
        $message = array();
        if($data){
            $tienda = $this->cuentasPlataformaRepository->getOne('idCuentaPlataforma',$data['idCuentaPlataforma']);
            $skuValidation = $this->publicacionRepository->validateSkuDuplicity($data['sku'],$tienda->idPlataforma); 
            if(!$skuValidation){
                $newId = $this->getNewId();
                $user = $this->headerService->getModelUser();
                
                $data['idPublicacion'] = $newId;
                $data['idUser'] = $user->idUser;
                $data['estado'] = 1;
                
                $this->publicacionRepository->create($data);
                
                $message = ['transaccion' => true,'mensaje' => 'Operacion exitosa'];
            }else{
                $message = ['transaccion' => false,'mensaje' => 'SKU repetido'];
            }
        }else{
            $message = ['transaccion' => false,'mensaje' => 'Data Faltante'];
        }
        
        return $message;
    }
    
    public function updatePublicacion($id,$titulo,$precio,$estado){
        $message = '';
        $publicacion = $this->publicacionRepository->getOne('idPublicacion',$id);
        $data = ['titulo' => $titulo,
                'precioPublicacion' => $precio,
                'fechaPublicacion' => now()];

        if($publicacion->estado == -1){
            $data['estado'] = -1;
            $message = 'No puedes activar una publicacion borrada';
        }else{
            $data['estado'] = $estado;
        }
        $this->publicacionRepository->update($id,$data);
        $message = 'Publicacion actualizada';
        return $message;
    }
    
    private function getNewId(){
        $lastPublicacion = $this->publicacionRepository->getLast();
        $id = $lastPublicacion ? $lastPublicacion->idPublicacion : 0;
        return $id + 1;
    }
    
}