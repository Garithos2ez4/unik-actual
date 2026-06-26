<?php

namespace App\Providers;

use App\Models\Almacen;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;

use App\Repositories\AccesosRepository;
use App\Repositories\AccesosRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\HeaderServiceInterface;
use App\Services\HeaderService;
use App\Services\CalculadoraServiceInterface;
use App\Services\CalculadoraService;
use App\Services\UsuarioServiceInterface;
use App\Services\UsuarioService;
use App\Services\ProductoServiceInterface;
use App\Services\ProductoService;
use App\Services\RegistroProductoServiceInterface;
use App\Services\RegistroProductoService;
use App\Services\ComprobanteServiceInterface;
use App\Services\ComprobanteService;
use App\Services\IngresoProductoServiceInterface;
use App\Services\IngresoProductoService;
use App\Services\PublicacionServiceInterface;
use App\Services\PublicacionService;
use App\Services\ReclamoPlataformaServiceInterface;
use App\Services\ReclamoPlataformaService;
use App\Services\VentaServiceInterface;
use App\Services\VentaService;

use App\Repositories\CalculadoraRepositoryInterface;
use App\Repositories\CalculadoraRepository;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\UsuarioRepository;
use App\Repositories\ProductoRepositoryInterface;
use App\Repositories\ProductoRepository;
use App\Repositories\GrupoProductoRepositoryInterface;
use App\Repositories\GrupoProductoRepository;
use App\Repositories\CategoriaProductoRepositoryInterface;
use App\Repositories\CategoriaProductoRepository;
use App\Repositories\MarcaProductoRepositoryInterface;
use App\Repositories\MarcaProductoRepository;
use App\Repositories\ProveedorRepositoryInterface;
use App\Repositories\ProveedorRepository;
use App\Repositories\AlmacenRepositoryInterface;
use App\Repositories\AlmacenRepository;
use App\Repositories\UbicacionAlmacenRepositoryInterface;
use App\Repositories\UbicacionAlmacenRepository;
use App\Repositories\CaracteristicasGrupoRepository;
use App\Repositories\CaracteristicasGrupoRepositoryInterface;
use App\Repositories\CaracteristicasProductoRepository;
use App\Repositories\CaracteristicasProductoRepositoryInterface;
use App\Repositories\CaracteristicasRepository;
use App\Repositories\CaracteristicasRepositoryInterface;
use App\Repositories\CaracteristicasSugerenciasRepository;
use App\Repositories\CaracteristicasSugerenciasRepositoryInterface;
use App\Repositories\ClienteRepository;
use App\Repositories\ClienteRepositoryInterface;
use App\Repositories\ComisionPlataformaRepository;
use App\Repositories\ComisionPlataformaRepositoryInterface;
use App\Repositories\InventarioRepositoryInterface;
use App\Repositories\InventarioRepository;
use App\Repositories\ProveedorInventarioRepositoryInterface;
use App\Repositories\ProveedorInventarioRepository;
use App\Repositories\PlataformaRepositoryInterface;
use App\Repositories\PlataformaRepository;
use App\Repositories\CuentasPlataformaRepositoryInterface;
use App\Repositories\CuentasPlataformaRepository;
use App\Repositories\RegistroProductoRepositoryInterface;
use App\Repositories\RegistroProductoRepository;
use App\Repositories\TipoComprobanteRepositoryInterface;
use App\Repositories\TipoComprobanteRepository;
use App\Repositories\ComprobanteRepositoryInterface;
use App\Repositories\ComprobanteRepository;
use App\Repositories\IngresoProductoRepositoryInterface;
use App\Repositories\IngresoProductoRepository;
use App\Repositories\ComisionRepositoryInterface;
use App\Repositories\ComisionRepository;
use App\Repositories\CuentasTransferenciaRepository;
use App\Repositories\CuentasTransferenciaRepositoryInterface;
use App\Repositories\DetalleComprobanteRepositoryInterface;
use App\Repositories\DetalleComprobanteRepository;
use App\Repositories\EgresoProductoRepository;
use App\Repositories\EgresoProductoRepositoryInterface;
use App\Repositories\EmpresaRepository;
use App\Repositories\EmpresaRepositoryInterface;
use App\Repositories\GarantiaRepository;
use App\Repositories\GarantiaRepositoryInterface;
use App\Repositories\PublicacionRepositoryInterface;
use App\Repositories\PublicacionRepository;
use App\Repositories\RangoPrecioRepository;
use App\Repositories\RangoPrecioRepositoryInterface;
use App\Repositories\TipoDocumentoRepository;
use App\Repositories\TipoDocumentoRepositoryInterface;
use App\Repositories\TipoProductoRepository;
use App\Repositories\TipoProductoRepositoryInterface;
use App\Repositories\VistaRepository;
use App\Repositories\VistaRepositoryInterface;
use App\Repositories\VentaRepository;
use App\Repositories\VentaRepositoryInterface;
use App\Repositories\DetalleVentaRepository;
use App\Repositories\DetalleVentaRepositoryInterface;
use App\Services\ClienteService;
use App\Services\ClienteServiceInterface;
use App\Services\ConfiguracionService;
use App\Services\ConfiguracionServiceInterface;
use App\Services\DashboardService;
use App\Services\DashboardServiceInterface;
use App\Services\EgresoProductoService;
use App\Services\EgresoProductoServiceInterface;
use App\Services\GarantiaService;
use App\Services\GarantiaServiceInterface;
use App\Services\PdfService;
use App\Services\PdfServiceInterface;
use App\Services\PlataformaService;
use App\Services\PlataformaServiceInterface;
use App\Services\ScriptService;
use App\Services\ScriptServiceInterface;
use App\Services\TrasladoService;
use App\Services\TrasladoServiceInterface;
use App\Services\LicenciaServiceInterface;
use App\Services\LicenciaService;
use App\Services\DivisionPackServiceInterface;
use App\Services\DivisionPackService;
use App\Services\UnionPackServiceInterface;
use App\Services\UnionPackService;
use App\Repositories\LicenciaRepository;
use App\Repositories\LicenciaRepositoryInterface;
use App\Repositories\RegistroUpdateRepository;
use App\Repositories\RegistroUpdateRepositoryInterface;
use App\Repositories\HistorialTipoCambioRepository;
use App\Repositories\HistorialTipoCambioRepositoryInterface;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LicenciaRepositoryInterface::class,LicenciaRepository::class);
        // Aquí enlazas la interfaz con la clase concreta
        $this->app->bind(LicenciaServiceInterface::class,LicenciaService::class);
        $this->app->bind(DivisionPackServiceInterface::class,DivisionPackService::class);
        $this->app->bind(UnionPackServiceInterface::class,UnionPackService::class);
        $this->app->bind(HeaderServiceInterface::class, HeaderService::class);
        $this->app->bind(CalculadoraServiceInterface::class, CalculadoraService::class);
        $this->app->bind(UsuarioServiceInterface::class, UsuarioService::class);
        $this->app->bind(ProductoServiceInterface::class, ProductoService::class);
        $this->app->bind(RegistroProductoServiceInterface::class, RegistroProductoService::class);
        $this->app->bind(ComprobanteServiceInterface::class, ComprobanteService::class);
        $this->app->bind(IngresoProductoServiceInterface::class, IngresoProductoService::class);
        $this->app->bind(PublicacionServiceInterface::class, PublicacionService::class);
        $this->app->bind(PlataformaServiceInterface::class,PlataformaService::class);
        $this->app->bind(EgresoProductoServiceInterface::class,EgresoProductoService::class);
        $this->app->bind(ConfiguracionServiceInterface::class,ConfiguracionService::class);
        $this->app->bind(PdfServiceInterface::class,PdfService::class);
        $this->app->bind(ReclamoPlataformaServiceInterface::class,ReclamoPlataformaService::class);
        $this->app->bind(VentaServiceInterface::class,VentaService::class);
        $this->app->bind(\App\Services\EnvioProvinciaServiceInterface::class, \App\Services\EnvioProvinciaService::class);

        $this->app->bind(CalculadoraRepositoryInterface::class, CalculadoraRepository::class);
        $this->app->bind(UsuarioRepositoryInterface::class, UsuarioRepository::class);
        $this->app->bind(ProductoRepositoryInterface::class, ProductoRepository::class);
        $this->app->bind(GrupoProductoRepositoryInterface::class, GrupoProductoRepository::class);
        $this->app->bind(CategoriaProductoRepositoryInterface::class, CategoriaProductoRepository::class);
        $this->app->bind(MarcaProductoRepositoryInterface::class, MarcaProductoRepository::class);
        $this->app->bind(ProveedorRepositoryInterface::class, ProveedorRepository::class);
        $this->app->bind(AlmacenRepositoryInterface::class, AlmacenRepository::class);
        $this->app->bind(UbicacionAlmacenRepositoryInterface::class, UbicacionAlmacenRepository::class);
        $this->app->bind(InventarioRepositoryInterface::class, InventarioRepository::class);
        $this->app->bind(ProveedorInventarioRepositoryInterface::class, ProveedorInventarioRepository::class);
        $this->app->bind(PlataformaRepositoryInterface::class, PlataformaRepository::class);
        $this->app->bind(CuentasPlataformaRepositoryInterface::class, CuentasPlataformaRepository::class);
        $this->app->bind(RegistroProductoRepositoryInterface::class, RegistroProductoRepository::class);
        $this->app->bind(TipoComprobanteRepositoryInterface::class, TipoComprobanteRepository::class);
        $this->app->bind(ComprobanteRepositoryInterface::class, ComprobanteRepository::class);
        $this->app->bind(IngresoProductoRepositoryInterface::class, IngresoProductoRepository::class);
        $this->app->bind(ComisionRepositoryInterface::class, ComisionRepository::class);
        $this->app->bind(DetalleComprobanteRepositoryInterface::class, DetalleComprobanteRepository::class);
        $this->app->bind(PublicacionRepositoryInterface::class, PublicacionRepository::class);
        $this->app->bind(VistaRepositoryInterface::class, VistaRepository::class);
        $this->app->bind(AccesosRepositoryInterface::class,AccesosRepository::class);
        $this->app->bind(EgresoProductoRepositoryInterface::class,EgresoProductoRepository::class);
        $this->app->bind(RangoPrecioRepositoryInterface::class,RangoPrecioRepository::class);
        $this->app->bind(EmpresaRepositoryInterface::class,EmpresaRepository::class);
        $this->app->bind(CaracteristicasRepositoryInterface::class,CaracteristicasRepository::class);
        $this->app->bind(CaracteristicasGrupoRepositoryInterface::class,CaracteristicasGrupoRepository::class);
        $this->app->bind(CuentasTransferenciaRepositoryInterface::class,CuentasTransferenciaRepository::class);
        $this->app->bind(ComisionPlataformaRepositoryInterface::class,ComisionPlataformaRepository::class);
        $this->app->bind(TipoProductoRepositoryInterface::class,TipoProductoRepository::class);
        $this->app->bind(CaracteristicasSugerenciasRepositoryInterface::class,CaracteristicasSugerenciasRepository::class);
        $this->app->bind(TrasladoServiceInterface::class,TrasladoService::class);
        $this->app->bind(DashboardServiceInterface::class,DashboardService::class);
        $this->app->bind(CaracteristicasProductoRepositoryInterface::class,CaracteristicasProductoRepository::class);
        $this->app->bind(ScriptServiceInterface::class,ScriptService::class);
        $this->app->bind(GarantiaServiceInterface::class,GarantiaService::class);
        $this->app->bind(GarantiaRepositoryInterface::class,GarantiaRepository::class);
        $this->app->bind(ClienteServiceInterface::class,ClienteService::class);
        $this->app->bind(TipoDocumentoRepositoryInterface::class,TipoDocumentoRepository::class);
        $this->app->bind(ClienteRepositoryInterface::class,ClienteRepository::class);
        $this->app->bind(RegistroUpdateRepositoryInterface::class,RegistroUpdateRepository::class);
        $this->app->bind(VentaRepositoryInterface::class,VentaRepository::class);
        $this->app->bind(DetalleVentaRepositoryInterface::class,DetalleVentaRepository::class);
        $this->app->bind(HistorialTipoCambioRepositoryInterface::class,HistorialTipoCambioRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('es');
        Paginator::useBootstrapFive();
        
        if (!app()->runningInConsole()) {
            $almacenes = Cache::remember('almacenes_all', 86400, function () {
                return Almacen::all();
            });
            View::share('almacenes', $almacenes);
        }
    }

}
