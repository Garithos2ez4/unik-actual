<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ecommerce\FalabellaOrder;
use App\Models\Ecommerce\MercadoLibreOrder;
use App\Models\Ecommerce\RipleyOrder;
use App\Services\HeaderServiceInterface;
use Illuminate\Support\Facades\DB;

class EgresoPendienteController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    public function index(Request $request)
    {
        $userModel = $this->headerService->getModelUser();

        // Falabella: Status = delivered, NOT IN EgresoProducto
        $falabellaPendientes = FalabellaOrder::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('EgresoProducto')
                ->whereRaw('EgresoProducto.numeroOrden = falabella_orders.order_number');
        })
            ->with('items')
            ->where('status', 'delivered')
            ->orderBy('created_at_falabella', 'desc')
            ->get();

        // MercadoLibre: return_status is null, status != cancelled, NOT IN EgresoProducto, idCuentaPlataforma = 3
        $mlPendientes = MercadoLibreOrder::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('EgresoProducto')
                ->whereRaw('EgresoProducto.numeroOrden = ml_orders.ml_order_id');
        })
            ->with('items')
            ->where(function ($q) {
                $q->whereNull('return_status')->orWhere('return_status', '');
            })
            ->whereIn('status', ['delivered', 'shipped'])
            ->orderBy('created_at_ml', 'desc')
            ->get();

        // Ripley: Status = SHIPPED/DELIVERED, NOT IN EgresoProducto
        $ripleyPendientes = RipleyOrder::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('EgresoProducto')
                ->whereRaw('EgresoProducto.numeroOrden = ripley_orders.order_number');
        })
            ->with('items')
            ->whereIn('status', ['DELIVERED', 'SHIPPED'])
            ->orderBy('created_at_ripley', 'desc')
            ->get();

        return view('egresos.egresos_pendientes', [
            'user' => $userModel,
            'falabellaPendientes' => $falabellaPendientes,
            'mlPendientes' => $mlPendientes,
            'ripleyPendientes' => $ripleyPendientes,
        ]);
    }
}
