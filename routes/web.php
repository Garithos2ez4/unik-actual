<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\EgresoController;
use App\Http\Controllers\PlataformaController;
use App\Http\Controllers\PublicidadController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\CalculadoraController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\GarantiaController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\ReclamoPlataformaController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnalyticsFallabellaController;
use App\Http\Controllers\GananciaController;
use App\Http\Controllers\ReviewController;

//scripts
use App\Http\Controllers\ScriptController;
use App\Http\Controllers\TrasladoController;
use App\Http\Controllers\FormularioPublicoController;

Route::withoutMiddleware(['validate.session'])->group(function () {
    Route::get('/', [LoginController::class, 'index'])->name('login');
    Route::post('/', [LoginController::class, 'validation'])->name('validation');

    // Formulario público para clientes (sin sesión)
    Route::prefix('formulario-envio')->group(function () {
        Route::get('/api/provincias/{idDepartamento}', [FormularioPublicoController::class, 'provincias']);
        Route::get('/api/destinos/{idProvincia}', [FormularioPublicoController::class, 'destinos']);
        Route::get('/api/subagencias/{idAgencia}/{idDestino}', [FormularioPublicoController::class, 'subagencias']);
        Route::get('/api/buscar-cliente/{documento}', [FormularioPublicoController::class, 'buscarCliente']);
        Route::get('/{token}', [FormularioPublicoController::class, 'show'])->name('formulario.publico.show');
        Route::post('/{token}', [FormularioPublicoController::class, 'store'])->name('formulario.publico.store');
    });
});



Route::middleware(['validate.session'])->group(function () {

    // Agrega esta línea con las demás rutas
    Route::get('/descargar-licencia/{id}', [LicenciaController::class, 'descargarLicencia'])->name('licencia.descargar');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analitica', [AnalyticsController::class, 'index'])->name('dashboard.analitica');
    Route::get('/dashboard/analitica/falabella', [AnalyticsFallabellaController::class, 'falabella'])->name('dashboard.analitica.falabella');
    Route::get('/dashboard/herramientas/scraper-falabella', [AnalyticsFallabellaController::class, 'scraperFalabella'])->name('dashboard.herramientas.scraper_falabella');
    Route::get('/dashboard/analitica/mercadolibre', [\App\Http\Controllers\AnalyticsMercadolibreController::class, 'index'])->name('dashboard.analitica.mercadolibre');
    Route::get('/dashboard/analitica/ripley', [AnalyticsController::class, 'ripley'])->name('dashboard.analitica.ripley');
    Route::get('/dashboard/analitica/tienda', [AnalyticsController::class, 'tienda'])->name('dashboard.analitica.tienda');
    Route::get('/dashboard/analitica/tienda/data', [AnalyticsController::class, 'tiendaData'])->name('dashboard.analitica.tienda.data');

    // Rutas de Analítica de Envíos
    Route::get('/dashboard/analitica/envios', [\App\Http\Controllers\AnalyticsEnviosController::class, 'index'])->name('dashboard.analitica.envios');
    Route::get('/dashboard/analitica/envios/top-provincias', [\App\Http\Controllers\AnalyticsEnviosController::class, 'getTopProvincias'])->name('dashboard.analitica.envios.provincias');
    Route::get('/dashboard/analitica/envios/top-clientes', [\App\Http\Controllers\AnalyticsEnviosController::class, 'getTopClientes'])->name('dashboard.analitica.envios.clientes');
    Route::get('/dashboard/analitica/envios/top-agencias', [\App\Http\Controllers\AnalyticsEnviosController::class, 'getTopAgencias'])->name('dashboard.analitica.envios.agencias');

    // Ganancias
    Route::get('/ganancias/all', [GananciaController::class, 'getAllGanancias'])->name('ganancias.all');
    Route::get('/ganancias/detalles', [GananciaController::class, 'getAllGananciasPorDetalle'])->name('ganancias.detalles');
    Route::get('/ganancias/venta/{idVenta}', [GananciaController::class, 'getGananciaPorVenta'])->name('ganancias.venta');

    Route::get('/home', fn() => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard/stockmin', [HomeController::class, 'stockMinDashboard'])->name('stockmindashboard');
    Route::get('/dashboard/inventario/{estado}', [HomeController::class, 'dashboardInventario'])->name('dashboardinventario');

    Route::get('/calculadora', [CalculadoraController::class, 'index'])->name('calculadora');
    Route::get('/calculadora/calculate', [CalculadoraController::class, 'calculate'])->name('calculadora-calculate');

    Route::get('/ingresos/searchingresos', [IngresoController::class, 'searchIngreso'])->name('searchingresos');
    Route::get('/ingresos/getoneingreso', [IngresoController::class, 'getOneIngreso'])->name('getoneingreso');

    // Rutas para División de Packs (Deben ir antes de /{month} para evitar colisión)
    Route::get('/ingresos/verificar-pack', [IngresoController::class, 'verificarPack'])->name('verificarpack');
    Route::post('/ingresos/dividir-pack', [IngresoController::class, 'dividirPack'])->name('dividirpack');
    Route::post('/ingresos/reunir-pack', [IngresoController::class, 'reunirPack'])->name('reunirpack');
    Route::get('/ingresos/componentes-reunion', [IngresoController::class, 'getComponentesReunion'])->name('componentesreunion');
    Route::get('/ingresos/buscar-pack-por-serie', [IngresoController::class, 'buscarPackPorSerie'])->name('buscarpackporserie');
    Route::get('/ingresos/buscar-series-pack-ajax', [IngresoController::class, 'buscarSeriesPackAjax'])->name('buscarseriespackajax');

    // Rutas para Unión Libre de componentes en Pack
    Route::get('/ingresos/packs-para-union',         [IngresoController::class, 'getPacksParaUnion'])->name('packs.para.union');
    Route::get('/ingresos/buscar-padres-pack-ajax',   [IngresoController::class, 'buscarPadresPackAjax'])->name('buscar.padres.pack.ajax');
    Route::get('/ingresos/componentes-union',         [IngresoController::class, 'getComponentesUnion'])->name('componentes.union');
    Route::post('/ingresos/unir-componentes-en-pack', [IngresoController::class, 'unirComponentesEnPack'])->name('unir.componentes.pack');


    Route::get('/ingresos/{month}', [IngresoController::class, 'index'])->name('ingresos');
    Route::post('/ingreso/deleteingreso', [IngresoController::class, 'deleteIngreso'])->name('deleteingreso');
    Route::post('/ingreso/updateregistro', [IngresoController::class, 'updateRegistro'])->name('updateregistro');
    Route::post('/ingreso/insertcomprobante', [IngresoController::class, 'insertComprobante'])->name('insertcomprobante');
    Route::post('/ingreso/insertingreso/{comprobante}', [IngresoController::class, 'insertIngreso'])->name('insertingreso');

    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios');
    Route::get('/usuario/nuevo', [UsuarioController::class, 'create'])->name('nuevousuario');
    Route::post('/usuario/createuser', [UsuarioController::class, 'createUser'])->name('createuser');
    Route::post('/usuario/updatepass', [UsuarioController::class, 'updatePass'])->name('updatepass');
    Route::post('/usuario/updatebandeja', [UsuarioController::class, 'updateBandeja'])->name('updatebandeja');
    Route::post('/usuario/updateuser', [UsuarioController::class, 'updateUser'])->name('updateuser');
    Route::get('/colaboradores', [UsuarioController::class, 'colaboradoresIndex'])->name('colaboradores');
    Route::get('/usuario/getbandeja', [UsuarioController::class, 'getBandejaColaborador'])->name('getbandeja');

    Route::get('/productos/buscarproducto', [ProductoController::class, 'searchProduct'])->name('buscarproducto');
    Route::post('/productos/toggle-status', [ProductoController::class, 'toggleStatus'])->name('producto.toggleStatus');
    Route::get('/productos/searchmodelproduct', [ProductoController::class, 'searchModelProduct'])->name('searchmodelproduct');
    Route::get('/producto/calculate', [ProductoController::class, 'calculate'])->name('calculateproducto');
    Route::get('/productos/historial-precio-tienda/{id}', [ProductoController::class, 'obtenerHistorialPrecioTienda']);
    Route::get('/productos/{cat}/{grup}', [ProductoController::class, 'index'])->name('productos');
    Route::get('/producto/nuevoproducto', [ProductoController::class, 'create'])->name('createproducto');
    Route::get('/producto/especificaciones/{idProducto}', [ProductoController::class, 'details'])->name('details');
    Route::get('/producto/{id}/ubicacion-html', [ProductoController::class, 'obtenerUbicacionHtml']);
    Route::get('/producto/{id}/historial-precios-html', [ProductoController::class, 'obtenerHistorialPreciosHtml']);
    Route::get('/producto/{idproducto}', [ProductoController::class, 'update'])->name('producto');
    Route::post('/producto/createdetails', [ProductoController::class, 'createDetails'])->name('createdetails');
    Route::post('/producto/updateproduct/{id}', [ProductoController::class, 'updateProduct'])->name('updateproduct');
    Route::post('/producto/insertorupdatedetails', [ProductoController::class, 'insertOrUpdateDetails'])->name('insertorupdatedetails');
    Route::post('/producto/deletedetail/{idProducto}', [ProductoController::class, 'deleteDetail'])->name('deletedetail');

    // Rutas para configuración de packs
    Route::get('/producto/{idproducto}/pack-components', [ProductoController::class, 'getPackComponents'])->name('producto.pack.components');
    Route::post('/producto/{idproducto}/pack-components', [ProductoController::class, 'addPackComponent'])->name('producto.pack.add');
    Route::put('/producto/{idproducto}/pack-components/{idHijo}', [ProductoController::class, 'updatePackComponent'])->name('producto.pack.update');
    Route::delete('/producto/{idproducto}/pack-components/{idHijo}', [ProductoController::class, 'removePackComponent'])->name('producto.pack.remove');

    // Rutas para Herramientas de Servicio (Reseteadores)
    Route::get('/producto/{idproducto}/series-herramienta', [ProductoController::class, 'getSeriesHerramienta'])->name('producto.series.herramienta');
    Route::post('/producto/toggle-herramienta', [ProductoController::class, 'toggleHerramienta'])->name('producto.toggle.herramienta');

    Route::post('/producto/creargrupo-rapido', [ProductoController::class, 'quickCreateGrupo'])->name('quickcreategrupo');

    // Template Falabella pre-llenado
    Route::get('/producto/{idProducto}/falabella-template', [ProductoController::class, 'descargarTemplateFalabella'])->name('producto.falabella.template');
    Route::get('/producto/{idProducto}/falabella-template-express', [ProductoController::class, 'descargarTemplateFalabellaExpress'])->name('producto.falabella.template.express');
    // Sugerencias de títulos (AJAX) y descarga con títulos personalizados (POST)
    Route::get('/producto/{idProducto}/falabella-titulos-sugeridos', [ProductoController::class, 'sugerirTitulosFalabella'])->name('producto.falabella.titulos');
    Route::post('/producto/{idProducto}/falabella-template-express', [ProductoController::class, 'descargarTemplateFalabellaExpressPost'])->name('producto.falabella.template.express.post');


    Route::get('/traslado', [TrasladoController::class, 'index'])->name('traslados');
    Route::post('/traslado/updateregistroalmacen', [TrasladoController::class, 'updateRegistroAlmacen'])->name('updateregistroalmacen');
    Route::get('/traslado/search-producto-ajax', [TrasladoController::class, 'searchProductoAjax'])->name('traslado.searchproducto');
    Route::get('/traslado/series-disponibles', [TrasladoController::class, 'getSeriesDisponibles'])->name('traslado.seriesdisponibles');
    Route::post('/traslado/search-multiple-series', [TrasladoController::class, 'searchMultipleSeries'])->name('traslado.searchmultipleseries');

    Route::get('/documento/searchdocument', [DocumentoController::class, 'searchDocument'])->name('searchdocument');
    Route::get('/documento/{id}/{bool}', [DocumentoController::class, 'index'])->name('documento');
    Route::get('/documentos/{date}', [DocumentoController::class, 'list'])->name('documentos');
    Route::post('/documento/deletecomprobante', [DocumentoController::class, 'deleteComprobante'])->name('deletecomprobante');
    Route::post('/documento/editcomprobante', [DocumentoController::class, 'editComprobante'])->name('editcomprobante');
    Route::post('/documento/validateseries', [DocumentoController::class, 'validateSeries'])->name('validate.series');

    Route::get('/egresos/searchregistro', [EgresoController::class, 'searchRegistro'])->name('searchregistro');
    Route::post('/egresos/appendegreso', [EgresoController::class, 'appendEgreso'])->name('appendegreso');
    Route::get('/egresos/pendientes-envios', [EgresoController::class, 'pendientesEnvios'])->name('egresos.pendientes_envios');
    Route::post('/egresos/pendientes-envios/mark', [EgresoController::class, 'markPendienteEgresado'])->name('egresos.pendientes_envios.mark');
    Route::get('/egresos/searchegreso', [EgresoController::class, 'searchEgreso'])->name('searchegreso');
    Route::get('/egresos/getoneegreso', [EgresoController::class, 'getOneRegistro'])->name('getoneegreso');
    Route::get('/egresos/nuevosegresos', [EgresoController::class, 'create'])->name('createegreso');
    Route::get('/egresos/total', [EgresoController::class, 'getTotalEgresos'])->name('egresos.total');
    Route::post('/egresos/importar', [EgresoController::class, 'importarExcel'])->name('egresos.importar');
    Route::get('/egresos/descargar-formato', [EgresoController::class, 'descargarFormato'])->name('egresos.formato');
    Route::get('/egresos/masivos', [EgresoController::class, 'egresosMasivos'])->name('egresos.masivos');
    Route::get('/egresos/search-producto-ajax', [EgresoController::class, 'searchProductoAjax'])->name('egresos.searchproducto');
    Route::get('/egresos/series-disponibles', [EgresoController::class, 'getSeriesDisponibles'])->name('egresos.seriesdisponibles');
    Route::get('/egresos/costo-registro', [EgresoController::class, 'getCostoRegistro'])->name('egresos.costoregistro');
    Route::post('/egresos/calcular-costo-ensamble', [EgresoController::class, 'calcularCostoEnsamble'])->name('egresos.calcularcostoensamble');
    Route::get('/egresos/{month}', [EgresoController::class, 'index'])->name('egresos');
    Route::post('/egresos/insertegreso', [EgresoController::class, 'insertEgreso'])->name('insertegreso');
    Route::post('/egresos/devolucionegreso', [EgresoController::class, 'devolucionEgreso'])->name('devolucionegreso');

    // Rutas para Ventas
    Route::prefix('ventas')->name('ventas.')->group(function () {
        Route::get('/', [VentaController::class, 'index'])->name('index');
        Route::get('/laptops-aio', [VentaController::class, 'getVentasLaptopsAio'])->name('laptops_aio');
        Route::get('/{id}', [VentaController::class, 'show'])->name('show');
        Route::post('/store', [VentaController::class, 'store'])->name('store');
    });

    Route::get('/garantia/creategarantia', [GarantiaController::class, 'create'])->name('creategarantia');
    Route::get('/garantias/{date}', [GarantiaController::class, 'index'])->name('garantias');
    Route::post('/garantia/insertgarantia', [GarantiaController::class, 'insertGarantia'])->name('insertgarantia');
    Route::get('/garantia/searchregistro', [GarantiaController::class, 'searchRegistro'])->name('garantia.searchregistro');
    Route::get('/garantia/getoneegreso', [GarantiaController::class, 'getOneRegistro'])->name('garantia.getoneegreso');

    Route::get('/plataformas', [PlataformaController::class, 'index'])->name('plataformas');
    Route::post('/plataforma/updatecuenta', [PlataformaController::class, 'updateCuentas'])->name('updatecuenta');
    Route::post('/plataforma/createcuenta', [PlataformaController::class, 'createCuenta'])->name('createcuenta');

    // Envios a Provincias
    Route::prefix('envios-provincias')->name('envios.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EnvioProvinciaController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\EnvioProvinciaController::class, 'create'])->name('create');
        Route::post('/store', [\App\Http\Controllers\EnvioProvinciaController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [\App\Http\Controllers\EnvioProvinciaController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [\App\Http\Controllers\EnvioProvinciaController::class, 'update'])->name('update');
        Route::get('/pdf', [\App\Http\Controllers\EnvioProvinciaController::class, 'pdf'])->name('pdf');
        Route::get('/excel', [\App\Http\Controllers\EnvioProvinciaController::class, 'excel'])->name('excel');
        Route::get('/etiquetas', [\App\Http\Controllers\EnvioProvinciaController::class, 'etiquetas'])->name('etiquetas');
        Route::get('/lista-productos', [\App\Http\Controllers\EnvioProvinciaController::class, 'listaProductos'])->name('listaProductos');
        Route::get('/solicitudes', [\App\Http\Controllers\EnvioProvinciaController::class, 'obtenerSolicitudes'])->name('solicitudes');
        Route::get('/sync-marvisur', [\App\Http\Controllers\Api\MarvisurSyncController::class, 'sync']);
        Route::get('/sync-emtrafesa', [\App\Http\Controllers\Api\EmtrafesaSyncController::class, 'sync']);
        Route::get('/sync-olva', [\App\Http\Controllers\Api\OlvaSyncController::class, 'sync']);
        Route::get('/sync-shalom', [\App\Http\Controllers\Api\ShalomSyncController::class, 'sync']);
        Route::get('/sync-all', [\App\Http\Controllers\EnvioProvinciaController::class, 'syncAllAgencias']);
        Route::get('/shalom-terminals', [\App\Http\Controllers\Api\ShalomTarifaController::class, 'getTerminals']);
        Route::post('/shalom-cotizar', [\App\Http\Controllers\Api\ShalomTarifaController::class, 'calculate']);
        Route::get('/shalom-restricciones', [\App\Http\Controllers\Api\ShalomTarifaController::class, 'getRestricciones']);
        Route::get('/olva-cajas', [\App\Http\Controllers\Api\OlvaTarifaController::class, 'getCajas']);

        // AJAX Endpoints
        Route::get('/buscar-registro', [\App\Http\Controllers\EnvioProvinciaController::class, 'buscarRegistro'])->name('buscar-registro');
        Route::get('/ultimo-envio-cliente/{idCliente}', [\App\Http\Controllers\EnvioProvinciaController::class, 'getUltimoEnvioCliente'])->name('ultimo-envio-cliente');
        Route::get('/provincias-por-departamento/{idDepartamento}', [\App\Http\Controllers\EnvioProvinciaController::class, 'getProvinciasPorDepartamento'])->name('provincias-por-departamento');
        Route::get('/destinos-por-provincia/{idProvincia}', [\App\Http\Controllers\EnvioProvinciaController::class, 'getDestinosPorProvincia'])->name('destinos-por-provincia');
        Route::get('/subagencias-por-agencia-y-destino/{idAgencia}/{idDestino}', [\App\Http\Controllers\EnvioProvinciaController::class, 'getSubAgenciasPorAgenciaYDestino'])->name('subagencias-por-agencia-y-destino');
        Route::post('/agencia', [\App\Http\Controllers\EnvioProvinciaController::class, 'storeAgencia'])->name('agencia.store');
        Route::post('/sub-agencia', [\App\Http\Controllers\EnvioProvinciaController::class, 'storeSubAgencia'])->name('subagencia.store');
        Route::post('/provincia', [\App\Http\Controllers\EnvioProvinciaController::class, 'storeProvincia'])->name('provincia.store');
        Route::post('/destino', [\App\Http\Controllers\EnvioProvinciaController::class, 'storeDestino'])->name('destino.store');
        Route::post('/generar-link', [\App\Http\Controllers\EnvioProvinciaController::class, 'generarLinkPublico'])->name('generar-link');
        Route::post('/regenerar-link/{id}', [\App\Http\Controllers\EnvioProvinciaController::class, 'regenerarLink'])->name('regenerar-link');
        Route::get('/tracking-flores/{id}', [\App\Http\Controllers\EnvioProvinciaController::class, 'trackFlores'])->name('tracking-flores');
    });
    // REVIEWS
    Route::get('/dashboard/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('/dashboard/reviews/{id}/estado', [ReviewController::class, 'updateEstado'])->name('reviews.estado');

    // FALABELLA
    Route::prefix('plataformas/falabella')->name('plataformas.falabella.')->group(function () {
        Route::get('/productos', [PlataformaController::class, 'falabellaProductos'])->name('productos');
        Route::get('/catalogo-fbf', [PlataformaController::class, 'falabellaPublicaciones'])->name('publicaciones');
        Route::get('/seller', [PlataformaController::class, 'sellerFalabella'])->name('seller');
        Route::get('/orders', [PlataformaController::class, 'falabellaOrders'])->name('orders');
        Route::get('/order/{order_id}', [PlataformaController::class, 'falabellaOrderDetails'])->name('order-details');
        Route::post('/sync-orders', [PlataformaController::class, 'syncFalabellaOrders'])->name('sync-orders');
        Route::get('/picking', [PlataformaController::class, 'falabellaPicking'])->name('picking');
        Route::get('/picking-pdf', [PlataformaController::class, 'falabellaPickingPdf'])->name('picking.pdf');
        Route::get('/etiquetas', [PlataformaController::class, 'falabellaEtiquetas'])->name('etiquetas');
        Route::get('/etiquetas-pdf', [PlataformaController::class, 'falabellaEtiquetasPdf'])->name('etiquetas.pdf');
        Route::get('/etiquetas-oficiales-pdf', [PlataformaController::class, 'falabellaEtiquetasOficialesPdf'])->name('etiquetas-oficiales.pdf');
        Route::get('/devoluciones', [PlataformaController::class, 'falabellaReturns'])->name('devoluciones');
        Route::post('/sync-devoluciones', [PlataformaController::class, 'syncFalabellaReturns'])->name('sync-devoluciones');
    });

    Route::prefix('reclamos-plataforma')->name('reclamos.')->group(function () {
        Route::get('/', [ReclamoPlataformaController::class, 'index'])->name('index');
        Route::get('/historia', [ReclamoPlataformaController::class, 'historia'])->name('historia');
        Route::get('/search-ajax', [ReclamoPlataformaController::class, 'searchAjax'])->name('search-ajax');
        Route::get('/create', [ReclamoPlataformaController::class, 'create'])->name('create');
        Route::post('/store', [ReclamoPlataformaController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [ReclamoPlataformaController::class, 'edit'])->name('edit');
        Route::post('/update/{id}', [ReclamoPlataformaController::class, 'update'])->name('update');
        Route::post('/seguimiento/{id}', [ReclamoPlataformaController::class, 'addSeguimiento'])->name('seguimiento.add');
        Route::post('/diagnostico/{id}', [ReclamoPlataformaController::class, 'addDiagnostico'])->name('diagnostico.add');
    });

    Route::get('/web', [PublicidadController::class, 'index'])->name('publicidad');
    Route::get('/web/empresa/{idEmpresa}', [PublicidadController::class, 'empresa'])->name('empresa-publicidad');
    Route::post('/web/updatepublicacion', [PublicidadController::class, 'updatePublicaion'])->name('updatepublicacion');

    Route::get('/registro-publicaciones/buscar', [PublicacionController::class, 'buscar'])->name('buscar-publicaciones');
    Route::get('/registro-publicaciones/{date}', [PublicacionController::class, 'index'])->name('publicaciones');
    Route::get('/crear-publicacion/{idPlataforma}', [PublicacionController::class, 'create'])->name('createpublicacion');
    Route::post('/insert-publicacion', [PublicacionController::class, 'insertPublicacion'])->name('insertpublicacion');
    Route::post('/update-estado-publicacion', [PublicacionController::class, 'updateEstado'])->name('update-estado-publicacion');
    Route::get('/searchpublicacion', [PublicacionController::class, 'searchPublicacion'])->name('searchpublicacion');

    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes');
    Route::get('/cliente/searchcliente', [ClienteController::class, 'searchCliente'])->name('searchcliente');
    Route::post('/cliente/create', [ClienteController::class, 'createCliente'])->name('createcliente');
    Route::post('/cliente/update/{id}', [ClienteController::class, 'updateCliente'])->name('updatecliente');

    //Configuracion-WEB
    Route::get('/configuracion/web', [ConfiguracionController::class, 'web'])->name('configweb');
    Route::post('/configuracion/updatecorreos', [ConfiguracionController::class, 'updateCorreos'])->name('updatecorreos');
    Route::post('/configuracion/updatecuentasbancarias', [ConfiguracionController::class, 'updateCuentasBancarias'])->name('updatecuentasbancarias');
    Route::post('/configuracion/insertcuentasbancarias', [ConfiguracionController::class, 'insertCuentasBancarias'])->name('insertcuentasbancarias');
    Route::post('/configuracion/insertmetodopago', [ConfiguracionController::class, 'insertMetodoPago'])->name('insertmetodopago');
    Route::post('/configuracion/inserttipometodopago', [ConfiguracionController::class, 'insertTipoMetodoPago'])->name('inserttipometodopago');
    Route::post('/configuracion/updatemetodopago', [ConfiguracionController::class, 'updateMetodoPago'])->name('updatemetodopago');
    Route::post('/configuracion/updatetipometodopago', [ConfiguracionController::class, 'updateTipoMetodoPago'])->name('updatetipometodopago');

    //Configuracion-CALCULOS
    Route::get('/configuracion/calculos', [ConfiguracionController::class, 'calculos'])->name('configcalculos');
    Route::post('/configuracion/updatecalculos', [ConfiguracionController::class, 'updateCalculos'])->name('updatecalculos');
    Route::post('/configuracion/updateCalculosTasaFija', [ConfiguracionController::class, 'updateCalculosTasaFija'])->name('updateCalculosTasaFija');
    Route::post('/configuracion/updatecomision', [ConfiguracionController::class, 'updateComision'])->name('updatecomision');
    Route::post('/configuracion/createcomisionplataforma', [ConfiguracionController::class, 'createComisionPlataforma'])->name('createcomisionplataforma');
    Route::post('/configuracion/deletecomisionplataforma', [ConfiguracionController::class, 'deleteComisionPlataforma'])->name('deletecomisionplataforma');
    Route::post('/configuracion/tipo-cambio', [ConfiguracionController::class, 'cambiarTipoCambio'])->name('configuracion.tipoCambio');


    //Configuracion-PRODUCTOS
    Route::get('/configuracion/productos', [ConfiguracionController::class, 'productos'])->name('configproductos');
    Route::post('/configuracion/insertmarca', [ConfiguracionController::class, 'createMarcaProducto'])->name('insertmarca');
    Route::post('/configuracion/insertgrupo', [ConfiguracionController::class, 'createGrupoProducto'])->name('insertgrupo');
    Route::post('/configuracion/updategrupo', [ConfiguracionController::class, 'updateGrupoProducto'])->name('updategrupo');
    Route::post('/configuracion/insertcategoria', [ConfiguracionController::class, 'createCategoriaProducto'])->name('insertcategoria');
    Route::post('/configuracion/updatecategoria', [ConfiguracionController::class, 'updateCategoriaProducto'])->name('updatecategoria');
    Route::post('/configuracion/insertcategoria', [ConfiguracionController::class, 'createCategoriaProducto'])->name('insertcategoria');

    //Configuracion-ESPECIFICACIONES
    Route::get('/configuracion/especificacionesxgeneral', [ConfiguracionController::class, 'especificacionesGeneral'])->name('configespecificacionesgeneral');
    Route::get('/configuracion/especificaciones/{idCategoria}', [ConfiguracionController::class, 'especificaciones'])->name('configespecificaciones');
    Route::get('/configuracion/especificacionesxgrupo/{idCategoria}', [ConfiguracionController::class, 'especificacionesGrupo'])->name('configespecificacionesxgrupo');
    Route::post('/configuracion/insertcaracteristicaxgrupo', [ConfiguracionController::class, 'insertCaracteristicaXGrupo'])->name('insertcaracteristicaxgrupo');
    Route::post('/configuracion/deletecaracteristicaxgrupo', [ConfiguracionController::class, 'deleteCaracteristicaXGrupo'])->name('deletecaracteristicaxgrupo');
    Route::post('/configuracion/createcaracteristica', [ConfiguracionController::class, 'createCaracteristica'])->name('createcaracteristica');
    Route::post('/configuracion/updatecaracteristica', [ConfiguracionController::class, 'updateCaracteristica'])->name('updatecaracteristica');
    Route::post('/configuracion/removesugerencia', [ConfiguracionController::class, 'removeSugerencia'])->name('removesugerencia');

    //Configuracion-INVENTARIO
    Route::get('/configuracion/inventario', [ConfiguracionController::class, 'inventario'])->name('configinventario');
    Route::post('/configuracion/createalamcen', [ConfiguracionController::class, 'createAlmacen'])->name('createalmacen');
    Route::post('/configuracion/createubicacion', [ConfiguracionController::class, 'createUbicacionAlmacen'])->name('createubicacion');
    Route::post('/configuracion/updateubicacion/{id}', [ConfiguracionController::class, 'updateUbicacionAlmacen'])->name('updateubicacion');
    Route::post('/configuracion/deleteubicacion/{id}', [ConfiguracionController::class, 'deleteUbicacionAlmacen'])->name('deleteubicacion');
    Route::post('/configuracion/addfila', [ConfiguracionController::class, 'addFila'])->name('addfila');
    Route::get('/configuracion/deletefila/{id}', [ConfiguracionController::class, 'deleteFila'])->name('deletefila');
    Route::post('/configuracion/createproveedor', [ConfiguracionController::class, 'createProveedor'])->name('createproveedor');

    Route::get('/generateSerialPdf/{idDocumento}', [PdfController::class, 'generateSerialPdf'])->name('generarSeriesPdf');
    Route::get('/pdf/serialbyproduct/{idProducto}/{idAlmacen?}', [PdfController::class, 'seriesByProductPdf'])->name('seriesXProducto');
    Route::get('/pdf/producto-series/{idProducto}/{idAlmacen}', [PdfController::class, 'productoSeriesPdf'])->name('pdf.producto.series');
    Route::get('/reporte/stock/{idAlmacen}', [PdfController::class, 'reportStockPdf'])->name('reportealmacen');
    Route::get('/reporte/estante/{idUbicacion}', [PdfController::class, 'reportEstantePdf'])->name('reporteestante');
    Route::get('/pdf/garantia/{idGarantia}', [PdfController::class, 'garantiaPdf'])->name('garantiaPdf');
    Route::post('/tipos-licencia', [LicenciaController::class, 'storeTipoLicencia'])->name('tiposLicencia.store');
    Route::get('/tipos-licencia/all', [LicenciaController::class, 'getAllTipos'])->name('tiposLicencia.all');
    Route::post('/tipos-licencia/toggle', [LicenciaController::class, 'toggleTipoEstado'])->name('tiposLicencia.toggle');
    Route::post(
        '/licencias/confirmar-importacion',
        [LicenciaController::class, 'confirmarImportacion']
    )->name('licencias.confirmar.importacion');

    Route::get(
        '/licencias/importar',
        [LicenciaController::class, 'vistaImportar']
    )->name('licencias.importar.vista');
    Route::post(
        '/licencias/importar-excel',
        [LicenciaController::class, 'importarExcel']
    )->name('licencias.importar.excel');
});
// Ruta para verificar clave duplicada

Route::post('/verificar-serial-recuperada', function (Request $request) {
    $serial = $request->input('serial_recuperada');

    // Buscar en la tabla correcta
    $existe = DB::table('licencias_recuperadas')
        ->where('serial_recuperada', $serial)
        ->exists();

    return response()->json([
        'existe' => $existe
    ]);
});
// Ruta para verificar clave duplicada
Route::post('/verificar-clave-duplicada', function (Request $request) {
    $claveKey = $request->input('clave_key');

    // Verificar si la clave ya existe en la tabla licencias_usadas
    $existe = DB::table('licencias_usadas')
        ->where('clave_key', $claveKey)
        ->exists();

    return response()->json([
        'existe' => $existe,
        'clave' => $claveKey
    ]);
})->name('verificar.clave.duplicada');
Route::middleware(['validate.session'])->prefix('licencias')->name('licencias.')->group(function () {

    // Mostrar listado
    Route::get('/', [LicenciaController::class, 'index'])->name('index');

    // Formulario de registro
    Route::get('/create', [LicenciaController::class, 'create'])->name('create');

    // Guardar licencia nueva
    Route::post('/store', [LicenciaController::class, 'store'])->name('store');

    // Formulario de cambio de estado
    Route::get('/{serial}/estado/{nuevoEstado}', [LicenciaController::class, 'showFormularioEstado'])
        ->name('showFormularioEstado');

    // Guardar cambio de estado
    Route::post('/{serial}/cambiar-estado', [LicenciaController::class, 'cambiarEstado'])
        ->name('cambiar_estado');

    //Plantilla en ecxel
    Route::get('/plantilla-excel', [LicenciaController::class, 'descargarPlantilla'])
        ->name('plantilla_excel');

    //Licencias Usadas
    Route::get('/usadas', [LicenciaController::class, 'usadas'])->name('usadas');
    //Licencias Usadas
    Route::get('/defectuosas', [LicenciaController::class, 'defectuosas'])->name('defectuosas');
    //Licencias Recuperadas
    Route::get('/recuperadas', [LicenciaController::class, 'recuperadas'])->name('recuperadas');
});
