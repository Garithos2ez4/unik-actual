<?php

namespace App\Http\Middleware;

use App\Services\HeaderServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SesionService;
use App\Services\UsuarioServiceInterface;

class ValidateSession   
{
    protected $usuarioService;
    protected $sesionService;
    protected $headerService;

    public function __construct(UsuarioServiceInterface $usuarioService,
                                SesionService $sesionService,
                                HeaderServiceInterface $headerService)
    {
        $this->usuarioService = $usuarioService;
        $this->sesionService = $sesionService;
        $this->headerService = $headerService;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idUserSesion = $this->sesionService->getUser();
        $tokenSesion = $this->sesionService->getSesion();

        if($idUserSesion == -1 || empty($idUserSesion)){
            $this->headerService->sendFlashAlerts('Inicia sesion','Se acabo el tiempo de sesión','warning','btn-danger');
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }else{
            $usuario = $this->usuarioService->getUserId($idUserSesion);
            $tokenBase = $usuario->tokenSesion;
            if($tokenBase == $tokenSesion){
                return $next($request);
            }else{
                $this->headerService->sendFlashAlerts('Inicia sesion','Sesión en otro dispositivo','warning','btn-danger');
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Session active in another device'], 401);
                }
                return redirect()->route('login');
            }
        }
    }
}
