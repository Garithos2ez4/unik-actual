<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\ReglaComision;
use Illuminate\Http\Request;

class ReglaComisionController extends Controller
{
    public function index()
    {
        $user = app(\App\Services\HeaderService::class)->getModelUser();
        $reglas = ReglaComision::orderBy('plataforma')->orderBy('prioridad', 'desc')->get();
        $tarifas = \App\Models\Ecommerce\ReglaTarifaEnvio::orderBy('plataforma')->orderBy('peso_maximo')->get();
        return view('configuracion.configcomisiones', compact('reglas', 'tarifas', 'user'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plataforma' => 'required|string',
            'nombre_regla' => 'required|string',
            'tipo_condicion' => 'required|string',
            'valor_condicion' => 'nullable|string',
            'porcentaje_comision' => 'nullable|numeric',
            'monto_fijo' => 'nullable|numeric',
            'prioridad' => 'required|integer',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);
        
        $data['estado'] = $request->has('estado') ? 1 : 0;
        
        ReglaComision::create($data);
        
        return back()->with('success', 'Regla agregada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $regla = ReglaComision::findOrFail($id);
        
        $data = $request->validate([
            'plataforma' => 'required|string',
            'nombre_regla' => 'required|string',
            'tipo_condicion' => 'required|string',
            'valor_condicion' => 'nullable|string',
            'porcentaje_comision' => 'nullable|numeric',
            'monto_fijo' => 'nullable|numeric',
            'prioridad' => 'required|integer',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $data['estado'] = $request->has('estado') ? 1 : 0;

        $regla->update($data);
        
        return back()->with('success', 'Regla actualizada correctamente.');
    }

    public function destroy($id)
    {
        ReglaComision::findOrFail($id)->delete();
        return back()->with('success', 'Regla eliminada correctamente.');
    }

    public function storeTarifa(Request $request)
    {
        $data = $request->validate([
            'plataforma' => 'required|string',
            'peso_maximo' => 'nullable|numeric',
            'monto_fijo' => 'required|numeric',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);
        
        $data['estado'] = $request->has('estado') ? 1 : 0;
        
        \App\Models\Ecommerce\ReglaTarifaEnvio::create($data);
        
        return back()->with('success', 'Tarifa agregada correctamente.');
    }

    public function updateTarifa(Request $request, $id)
    {
        $tarifa = \App\Models\Ecommerce\ReglaTarifaEnvio::findOrFail($id);
        
        $data = $request->validate([
            'plataforma' => 'required|string',
            'peso_maximo' => 'nullable|numeric',
            'monto_fijo' => 'required|numeric',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $data['estado'] = $request->has('estado') ? 1 : 0;

        $tarifa->update($data);
        
        return back()->with('success', 'Tarifa actualizada correctamente.');
    }

    public function destroyTarifa($id)
    {
        \App\Models\Ecommerce\ReglaTarifaEnvio::findOrFail($id)->delete();
        return back()->with('success', 'Tarifa eliminada correctamente.');
    }
}
