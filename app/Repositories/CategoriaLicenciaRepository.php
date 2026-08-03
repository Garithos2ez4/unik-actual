<?php
namespace App\Repositories;
use App\Models\Licencias\CategoriaLicencia;
use App\Models\Licencias\Licencia;

class CategoriaLicenciaRepository implements  CategoriaLicenciaRepositoryInterface
{
    protected $modelColumns;
    
    public function __construct() {
        $this->modelColumns = (new CategoriaLicencia())->getFillable();
    }

    public function validateColumns ($column){
        if(!in_array($column, $this->modelColumns)){
            throw new \InvalidArgumentException("La columna '$column' no es valida");
        }
    }
    
    public function all()
    {
        return CategoriaLicencia::all();
    }
    
    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaLicencia::where($column, '=', $data)->first();
    }
    
    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaLicencia::where($column,'=', $data)->get();
    }

    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaLicencia::where($column,'LIKE', '%' . $data . '%')->first();
    }
    
    public function SearchList($column, $data)
    {
        $this->validateColumns($column);
        return CategoriaLicencia::where($column, 'LIKE', '%' . $data . '%')->get();
        throw new \Exception('Not implemented');
    }

    public function create(array $LicenciaData)
    {
        return CategoriaLicencia::create($LicenciaData);
    }

    public function update($idLicencia, array $licenciaData)
    {
        $licencia = CategoriaLicencia::findOrFail($idLicencia);
        $licencia->update($licenciaData);
        return $licencia;
    }
}