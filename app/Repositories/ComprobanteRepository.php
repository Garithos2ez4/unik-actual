<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Comprobante;

class ComprobanteRepository implements ComprobanteRepositoryInterface
{
    protected $modelColumns;

    public function __construct()
    {
        // Define las columnas válidas
        $this->modelColumns = (new Comprobante())->getFillable();
    }

    public function getOne($column, $data)
    {
        $this->validateColumns($column);
        return Comprobante::query()->where($column,'=', $data)->first();
    }

    public function getAllByColumn($column, $data)
    {
        $this->validateColumns($column);
        return Comprobante::query()->where($column,'=', $data)->get();
    }
    
    public function getAllByMonth(\Carbon\Carbon $month,$cant,$querys)
    {
        $query = Comprobante::query();
        $query->whereMonth('fechaRegistro', '=', $month->month, 'and')
                ->whereYear('fechaRegistro', '=', $month->year, 'and');
        
        if(isset($querys)){
            if(isset($querys['usuario'])){
                $query->where('idUser','=',$querys['usuario']);
            }
            if(isset($querys['proveedor'])){
                $query->where('idProveedor','=',$querys['proveedor']);
            }
            if(isset($querys['documento'])){
                $query->where('idTipoComprobante','=',$querys['documento']);
            }
            if(isset($querys['estado'])){
                $query->where('estado','=',$querys['estado']);
            }
        }
        return $query->orderBy('fechaRegistro','desc')->paginate($cant);
    }
    
    public function searchOne($column, $data)
    {
        $this->validateColumns($column);
        return Comprobante::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->first();
    }

    public function searchList($column, $data)
    {
        $this->validateColumns($column);
        return Comprobante::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->get();
    }

    public function searchTakeList($column, $data,$cant)
    {
        $this->validateColumns($column);
        return Comprobante::query()->where($column, 'LIKE', '%' . $data . '%', 'and')->take($cant)->get();
    }

    public function searchByInvoiceOrSerial($data, $cant)
    {
        return Comprobante::query()
            ->where('numeroComprobante', 'LIKE', '%' . $data . '%')
            ->orWhereHas('DetalleComprobante.RegistroProducto', function ($q) use ($data) {
                $q->where('numeroSerie', 'LIKE', '%' . $data . '%');
            })
            ->take($cant)
            ->get();
    }

    public function getUsuariosByMonth(\Carbon\Carbon $month){
        return Comprobante::select('idUser')->distinct()
                            ->whereMonth('fechaRegistro', '=', $month->month, 'and')
                            ->whereYear('fechaRegistro', '=', $month->year, 'and')
                            ->get();
    }

    public function getProveedoresByMonth(\Carbon\Carbon $month){
        return Comprobante::select('idProveedor')->distinct()
                        ->whereMonth('fechaRegistro', '=', $month->month, 'and')
                        ->whereYear('fechaRegistro', '=', $month->year, 'and')
                        ->get();
    }

    public function getDocumentosByMonth(\Carbon\Carbon $month){
        return Comprobante::select('idTipoComprobante')->distinct()
                            ->whereMonth('fechaRegistro', '=', $month->month, 'and')
                            ->whereYear('fechaRegistro', '=', $month->year, 'and')
                            ->get();
    }

    public function getEstadosByMonth(\Carbon\Carbon $month){
        return Comprobante::select('estado')->distinct()
                            ->whereMonth('fechaRegistro', '=', $month->month, 'and')
                            ->whereYear('fechaRegistro', '=', $month->year, 'and')
                            ->get();
    }
    
    public function create(array $data)
    {
        return Comprobante::create($data);
    }
    
    
    public function update($id, array $data)
    {
        $comprobante = Comprobante::findOrFail($id);
        $comprobante->update($data);
        return $comprobante;
    }
    
    public function remove($id){
        $comprobante = Comprobante::findOrFail($id);
        $comprobante->delete();
    }
    
    public function validateDuplicity($number,$type,$idProveedor){
        $validate = Comprobante::query()->where('estado','<>','INVALIDO', 'and')
                                ->where('numeroComprobante','=',$number, 'and')
                                ->where('idTipoComprobante','=',$type, 'and')
                                ->where('idProveedor','=',$idProveedor, 'and')->first();
        if($validate){
            return true;
        }else{
            return false;
        }
    }
    
    public function getLast(){
        return Comprobante::orderBy('idComprobante', 'desc')->first();
    }

    public function getAllRegistrosByComprobanteId($id){
        return Comprobante::query()->join('DetalleComprobante','DetalleComprobante.idComprobante','=','Comprobante.idComprobante', 'inner', false)
                ->join('RegistroProducto','DetalleComprobante.idDetalleComprobante','=','RegistroProducto.idDetalleComprobante', 'inner', false)
                ->select('RegistroProducto.*')->where('Comprobante.idComprobante','=',$id, 'and')->get();
    }
    
    private function validateColumns($column){
        if (!in_array($column, $this->modelColumns)) {
            throw new \InvalidArgumentException("La columna '$column' no es válida.");
        }
    }
}