<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Services\HeaderServiceInterface;

class ReviewController extends Controller
{
    protected $headerService;

    public function __construct(HeaderServiceInterface $headerService)
    {
        $this->headerService = $headerService;
    }

    /**
     * Valida si el usuario tiene acceso a la vista específica.
     */
    private function validateAccess($userModel, int $idVista)
    {
        return $userModel->Accesos->contains('idVista', $idVista);
    }

    public function index()
    {
        $userModel = $this->headerService->getModelUser();

        // Validar acceso a la vista 16 (Reviews)
        if (!$this->validateAccess($userModel, 16)) {
            $this->headerService->sendFlashAlerts('Acceso denegado', 'No tienes permiso para ingresar a esta pestaña', 'warning', 'btn-danger');
            return redirect()->route('dashboard', ['user' => $userModel]);
        }

        // Obtener reseñas con cliente y producto, ordenadas por las más recientes primero
        $reviews = Review::with(['cliente', 'producto'])->orderBy('created_at', 'desc')->get();

        return view('reviews.index', [
            'user' => $userModel,
            'reviews' => $reviews
        ]);
    }

    public function updateEstado(Request $request, $id)
    {
        $userModel = $this->headerService->getModelUser();

        // Validar acceso a la vista 16 (Reviews)
        if (!$this->validateAccess($userModel, 16)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $request->validate([
            'estado' => 'required|in:aprobado,rechazado,pendiente'
        ]);

        $estadoMap = [
            'pendiente' => 0,
            'aprobado' => 1,
            'rechazado' => 2
        ];

        $review = Review::findOrFail($id);
        $review->estado = $estadoMap[$request->estado];
        $review->save();

        return redirect()->route('reviews.index')->with([
            'title' => 'Éxito',
            'message' => 'El estado de la reseña ha sido actualizado correctamente.',
            'icon' => 'success',
            'button' => 'btn-success'
        ]);
    }
}
