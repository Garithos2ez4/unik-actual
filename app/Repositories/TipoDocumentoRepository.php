<?php
namespace App\Repositories;

use App\Models\Usuarios\TipoDocumento;

class TipoDocumentoRepository implements TipoDocumentoRepositoryInterface
{
    public function all(){
        return TipoDocumento::all();
    }
}