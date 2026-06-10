<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Services\HeaderService;
use App\Models\Empresa;
use App\Models\EmpresaRedSocial;

class PublicidadController extends Controller
{
    public function index(){
        //variables de la cabecera
        $serviceHeader = new HeaderService;
        $userModel = $serviceHeader->getModelUser();
        
        //variables propias del controlador
        $empresas = Empresa::select('idEmpresa','nombreComercial','rucEmpresa','colorUno','colorDos','colorTres','logo','icon','correoEmpresa','ubicacion','ubicacionLink','linkPaginaWeb')->get();
        
        return view('publicidad',['user' => $userModel,
                                    'empresas' => $empresas
                                ]);
    }
    
    public function empresa($idEmpresa){
        //variables de la cabecera
        $serviceHeader = new HeaderService;
        $userModel = $serviceHeader->getModelUser();
        
        //variables propias del controlador
        $empresa = Empresa::where('idEmpresa','=',decrypt($idEmpresa))->first();
        
        return view('empresa-publicidad',['user' => $userModel,
                                            'empresa' => $empresa
                                ]);
    }
    
    public function updatePublicaion(Request $request){
        $enlaces = $request->input('enlaces');
        $idEmpresa = $request->input('empresa');
        
        if(!empty($idEmpresa)){
            $empresa = Empresa::where('idEmpresa','=',$idEmpresa)->first();
            
            // Subida de Logo, Icono y Fondo
            if ($request->hasFile('logo')) {
                if ($empresa->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($empresa->logo);
                }
                $empresa->logo = $request->file('logo')->store('publicidad', 'public');
            }
            
            if ($request->hasFile('icon')) {
                if ($empresa->icon && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->icon)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($empresa->icon);
                }
                $empresa->icon = $request->file('icon')->store('publicidad', 'public');
            }
            
            if ($request->hasFile('fondo')) {
                if ($empresa->fondo && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->fondo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($empresa->fondo);
                }
                $empresa->fondo = $request->file('fondo')->store('fondos_empresa', 'public');
            }
            
            $empresa->save();

            // Guardar Enlaces e Imágenes de Redes Sociales
            if(!empty($enlaces)){
                foreach($enlaces as $id => $enlace){
                    $redSocial = EmpresaRedSocial::where('idEmpresa','=',$idEmpresa)->where('idRedSocial','=',$id)->first();
                    $redSocial->enlace = $enlace;
                    
                    if ($request->hasFile("imgRed.$id")) {
                        if ($redSocial->imagen && \Illuminate\Support\Facades\Storage::disk('public')->exists($redSocial->imagen)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($redSocial->imagen);
                        }
                        $redSocial->imagen = $request->file("imgRed.$id")->store('publicidad', 'public');
                    }
                    
                    $redSocial->save();
                }
            }
            
            // Banners Principales y Verticales (img)
            if ($request->hasFile('img')) {
                foreach($request->file('img') as $idBanner => $fileBanner) {
                    $banner = \App\Models\Publicidad::where('idEmpresa', $idEmpresa)->where('idPublicidad', $idBanner)->first();
                    if ($banner) {
                        if ($banner->imagenPublicidad && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner->imagenPublicidad)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($banner->imagenPublicidad);
                        }
                        $banner->imagenPublicidad = $fileBanner->store('publicidad', 'public');
                        $banner->save();
                    }
                }
            }

            // Banners Campaña (imgPubli)
            if ($request->hasFile('imgPubli')) {
                foreach($request->file('imgPubli') as $idBanner => $fileBanner) {
                    $banner = \App\Models\Publicidad::where('idEmpresa', $idEmpresa)->where('idPublicidad', $idBanner)->first();
                    if ($banner) {
                        if ($banner->imagenPublicidad && \Illuminate\Support\Facades\Storage::disk('public')->exists($banner->imagenPublicidad)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($banner->imagenPublicidad);
                        }
                        $banner->imagenPublicidad = $fileBanner->store('publicidad', 'public');
                        $banner->save();
                    }
                }
            }
        }
        
        return redirect()->route('publicidad')->with('success', 'Publicación actualizada correctamente');
    }
}