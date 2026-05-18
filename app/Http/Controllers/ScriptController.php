<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ScriptServiceInterface;
use Illuminate\Support\Facades\DB;

class ScriptController extends Controller
{
    protected $scriptService;

    public function __construct(ScriptServiceInterface $scriptService)
    {
        $this->scriptService = $scriptService;
    }
    public function headerScript(){

        $js = view('js.header-scripts')->render();
        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function createProductScript($tc)
    {
        $latestProductCodes = $this->scriptService->getCodigosProductos();
        $tasaFija = app(\App\Services\CalculadoraServiceInterface::class)->getTasaFija()->tasaCambio;
        $js = view('js.create-product-scripts',['tc' => $tc,
                                                'tasaFija' => $tasaFija,
                                                'codigos' => $latestProductCodes])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function updateProductScript($tc){

        $js = view('js.update-product-scripts',['tc' => $tc])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function listProductScript($tc){

        $js = view('js.list-products-scripts',['tc' => $tc])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function calculatorScript(){
        $valores = $this->scriptService->getCalculadora();

        $js = view('js.calculator-scripts',['valores' => $valores])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function documentoScript($idDocumento){
        $ubicaciones = $this->scriptService->getAllAlmacen();
        $documento = $this->scriptService->getOneComprobante($idDocumento);

        $estados = [['value' => 'NUEVO', 'name' => 'Nuevo'],
                    ['value' => 'ABIERTO', 'name' => 'Abierto'],
                    ['value' => 'DEFECTUOSO', 'name' => 'Defectuoso']
                    ];

        $medidas = [['value' => 'CAJA', 'name' => 'Caja'],
                    ['value' => 'UNIDAD', 'name' => 'Unidad']];

        $adquisiciones = [['value' => 'NORMAL', 'name' => 'Normal'],
                        ['value' => 'OFERTA', 'name' => 'Oferta']];

        $js = view('js.documento-scripts',['ubicaciones' => $ubicaciones,
                                            'estados' => $estados,
                                            'medidas' => $medidas,
                                            'adquisiciones' => $adquisiciones,
                                            'documento' => $documento])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function configCalculosScript(){
        $categorias = $this->scriptService->getAllCategorias();
        $js = view('js.config-calculos-scripts',['categorias' => $categorias])->render();

        return response($js)->header('Content-Type', 'application/javascript');
    }
}
