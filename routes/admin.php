<?php

use App\Http\Controllers\Admin\AntifraudeController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ComisionController;
use App\Http\Controllers\Admin\CuponController;
use App\Http\Controllers\Admin\MensajeController;
use App\Http\Controllers\Admin\PreciosPlanesController;
use App\Http\Controllers\Admin\TiendaAtlantiaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatosMercadoController;
use App\Http\Controllers\Admin\DevolucionController;
use App\Http\Controllers\Admin\DteController;
use App\Http\Controllers\Admin\EmpleadoController;
use App\Http\Controllers\Admin\ExternalDeliveryOrderController;
use App\Http\Controllers\Admin\HeroBannerController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LiquidacionesController;
use App\Http\Controllers\Admin\MlMonitorController;
use App\Http\Controllers\Admin\MlReentrenamientoController;
use App\Http\Controllers\Admin\NominaController;
use App\Http\Controllers\Admin\ObservabilityController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\RepartidorController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\ResenaController;
use App\Http\Controllers\Admin\RolPermisoController;
use App\Http\Controllers\Admin\ServiciosContratadosController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\UtilidadController;
use App\Http\Controllers\Admin\VendedorController;
use App\Http\Controllers\Admin\ZonaEntregaController;
use App\Http\Controllers\Cliente\SaldoController;
use App\Http\Controllers\ImportarProductosController;
use App\Http\Controllers\PromocionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Administrador
|--------------------------------------------------------------------------
|
| Gestion global de Atlantia: supervision operativa, vendedores, catalogo,
| pedidos, auditoria, FEL, comisiones, ML y antifraude.
|
*/

Route::prefix('admin')
    ->as('admin.')
    ->middleware(['auth', 'verified', 'role:admin|super_admin', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/vendedores', [VendedorController::class, 'index'])->name('vendedores.index');
        Route::get('/vendedores/reporte/pdf', [VendedorController::class, 'reportPdf'])->name('vendedores.report.pdf');
        Route::get('/vendedores/{vendor:uuid}/documentos/{document}', [VendedorController::class, 'document'])
            ->name('vendedores.documents.show');
        Route::get('/vendedores/{vendor:uuid}', [VendedorController::class, 'show'])->name('vendedores.show');
        Route::patch('/vendedores/{vendor:uuid}/aprobar', [VendedorController::class, 'approve'])
            ->name('vendedores.approve');
        Route::patch('/vendedores/{vendor:uuid}/rechazar', [VendedorController::class, 'reject'])
            ->name('vendedores.reject');
        Route::post('/vendedores/{vendor:uuid}/reenviar-acceso', [VendedorController::class, 'reenviarAcceso'])
            ->middleware('throttle:10,1')
            ->name('vendedores.reenviar-acceso');
        Route::delete('/vendedores/{vendor:uuid}/dos-factores', [VendedorController::class, 'reiniciarDosFactores'])
            ->name('vendedores.reiniciar-2fa');
        Route::patch('/vendedores/{vendor:uuid}/suspender', [VendedorController::class, 'suspend'])
            ->name('vendedores.suspend');
        Route::post('/vendedores/{vendor:uuid}/pausar', [VendedorController::class, 'pausar'])->name('vendedores.pausar');
        Route::post('/vendedores/{vendor:uuid}/plan', [VendedorController::class, 'asignarPlan'])->name('vendedores.plan');
        Route::post('/vendedores/{vendor:uuid}/reanudar', [VendedorController::class, 'reanudar'])->name('vendedores.reanudar');
        Route::patch('/vendedores/{vendor:uuid}/reactivar', [VendedorController::class, 'reactivate'])
            ->name('vendedores.reactivate');
        Route::delete('/vendedores/{vendor:uuid}', [VendedorController::class, 'destroy'])->name('vendedores.destroy');

        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/servicios', [ServiciosContratadosController::class, 'index'])->name('servicios.index');
        Route::post('/servicios/{servicio}/pagado', [ServiciosContratadosController::class, 'marcarPagado'])->name('servicios.pagado');
        Route::post('/servicios/{servicio}/aprobar', [ServiciosContratadosController::class, 'aprobar'])->name('servicios.aprobar');
        Route::post('/servicios/{servicio}/rechazar', [ServiciosContratadosController::class, 'rechazar'])->name('servicios.rechazar');
        Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
        Route::post('/promociones', [PromocionController::class, 'store'])->name('promociones.store');
        Route::patch('/promociones/{promocion}', [PromocionController::class, 'toggle'])->name('promociones.toggle');
        Route::delete('/promociones/{promocion}', [PromocionController::class, 'destroy'])->name('promociones.destroy');
        Route::get('/productos/importar', [ImportarProductosController::class, 'create'])->name('productos.importar');
        Route::post('/productos/importar', [ImportarProductosController::class, 'store'])->name('productos.importar.store');
        Route::get('/productos/importar/plantilla', [ImportarProductosController::class, 'plantilla'])->name('productos.importar.plantilla');
        Route::get('/productos/{producto:uuid}', [ProductoController::class, 'show'])->name('productos.show');
        Route::put('/productos/{producto:uuid}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto:uuid}/moderar', [ProductoController::class, 'moderate'])
            ->name('productos.moderate');
        Route::delete('/productos/{producto:uuid}', [ProductoController::class, 'destroy'])->name('productos.destroy');

        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
        Route::get('/hero-banners', [HeroBannerController::class, 'index'])->name('hero-banners.index');
        Route::post('/hero-banners', [HeroBannerController::class, 'store'])->name('hero-banners.store');
        Route::put('/hero-banners/{heroBanner:uuid}', [HeroBannerController::class, 'update'])->name('hero-banners.update');
        Route::delete('/hero-banners/{heroBanner:uuid}', [HeroBannerController::class, 'destroy'])->name('hero-banners.destroy');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::post('/pedidos/lote/actualizar', [PedidoController::class, 'updateBatch'])->name('pedidos.batch-update');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::put('/pedidos/{pedido:uuid}', [PedidoController::class, 'update'])->name('pedidos.update');
        Route::post('/pedidos/{pedido:uuid}/saldo', [SaldoController::class, 'acreditarDesdePedido'])
            ->name('pedidos.saldo');

        Route::get('/entregas-externas', [ExternalDeliveryOrderController::class, 'index'])->name('entregas-externas.index');
        Route::post('/entregas-externas', [ExternalDeliveryOrderController::class, 'store'])->name('entregas-externas.store');
        Route::get('/entregas-externas/{externalDeliveryOrder:uuid}', [ExternalDeliveryOrderController::class, 'show'])->name('entregas-externas.show');
        Route::patch('/entregas-externas/{externalDeliveryOrder:uuid}/asignar', [ExternalDeliveryOrderController::class, 'assign'])->name('entregas-externas.assign');

        Route::get('/repartidores', [RepartidorController::class, 'index'])->name('repartidores.index');
        Route::post('/repartidores', [RepartidorController::class, 'store'])->name('repartidores.store');
        Route::get('/repartidores/{repartidor:uuid}', [RepartidorController::class, 'show'])
            ->name('repartidores.show');
        Route::put('/repartidores/{repartidor:uuid}', [RepartidorController::class, 'update'])
            ->name('repartidores.update');
        Route::delete('/repartidores/{repartidor:uuid}', [RepartidorController::class, 'destroy'])
            ->name('repartidores.destroy');

        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('/empleados/{empleado:uuid}', [EmpleadoController::class, 'show'])->name('empleados.show');
        Route::put('/empleados/{empleado:uuid}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::delete('/empleados/{empleado:uuid}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');

        Route::get('/nominas', [NominaController::class, 'index'])->name('nominas.index');
        Route::post('/nominas', [NominaController::class, 'store'])->name('nominas.store');
        Route::get('/nominas/{nomina:uuid}', [NominaController::class, 'show'])->name('nominas.show');
        Route::put('/nominas/{nomina:uuid}/detalles/{detalle}', [NominaController::class, 'updateDetail'])
            ->name('nominas.detalles.update');
        Route::patch('/nominas/{nomina:uuid}/pagar', [NominaController::class, 'pay'])->name('nominas.pay');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('/usuarios/lote', [UsuarioController::class, 'batch'])->name('usuarios.batch');
        Route::get('/usuarios/{usuario:uuid}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::put('/usuarios/{usuario:uuid}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario:uuid}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
        Route::post('/impersonar/{usuario:uuid}', [ImpersonationController::class, 'start'])
            ->name('impersonation.start');

        Route::get('/roles-permisos', [RolPermisoController::class, 'index'])->name('roles-permisos.index');
        Route::post('/roles-permisos/roles', [RolPermisoController::class, 'store'])->name('roles-permisos.store');
        Route::post('/roles-permisos/permisos', [RolPermisoController::class, 'storePermission'])
            ->name('roles-permisos.permisos.store');
        Route::put('/roles-permisos/{role}', [RolPermisoController::class, 'sync'])->name('roles-permisos.sync');
        Route::delete('/roles-permisos/{role}', [RolPermisoController::class, 'destroy'])
            ->name('roles-permisos.destroy');

        Route::get('/resenas', [ResenaController::class, 'index'])->name('resenas.index');
        Route::post('/resenas/lote/moderar', [ResenaController::class, 'moderateBatch'])->name('resenas.batch-moderate');
        Route::get('/resenas/{resena:uuid}', [ResenaController::class, 'show'])->name('resenas.show');
        Route::patch('/resenas/{resena:uuid}/moderar', [ResenaController::class, 'moderate'])->name('resenas.moderate');

        Route::get('/devoluciones', [DevolucionController::class, 'index'])->name('devoluciones.index');
        Route::patch('/devoluciones/{devolucion:uuid}', [DevolucionController::class, 'update'])
            ->name('devoluciones.update');

        Route::get('/utilidad', [UtilidadController::class, 'index'])->name('utilidad.index');
        Route::post('/utilidad/gastos', [UtilidadController::class, 'guardarGasto'])->name('utilidad.gastos.store');
        Route::delete('/utilidad/gastos/{gasto}', [UtilidadController::class, 'eliminarGasto'])->name('utilidad.gastos.destroy');
        Route::get('/pagos-vendedores', [LiquidacionesController::class, 'index'])->name('liquidaciones.index');
        Route::get('/pagos-vendedores/{liquidacion}', [LiquidacionesController::class, 'show'])->name('liquidaciones.show');
        Route::post('/pagos-vendedores/{liquidacion}/pagar', [LiquidacionesController::class, 'pagar'])->middleware('throttle:20,1')->name('liquidaciones.pagar');
        Route::post('/pagos-vendedores/{liquidacion}/rechazar', [LiquidacionesController::class, 'rechazar'])->middleware('throttle:20,1')->name('liquidaciones.rechazar');

        Route::get('/comisiones', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::post('/comisiones/recalcular', [ComisionController::class, 'recalcular'])->name('comisiones.recalcular');
        Route::put('/comisiones/{comision}', [ComisionController::class, 'update'])->name('comisiones.update');
        Route::get('/atlantia-store', [TiendaAtlantiaController::class, 'index'])->name('tienda-atlantia.index');
        Route::post('/atlantia-store/entrar', [TiendaAtlantiaController::class, 'entrar'])->middleware('throttle:10,1')->name('tienda-atlantia.entrar');
        Route::post('/atlantia-store/acceso', [TiendaAtlantiaController::class, 'acceso'])->middleware('throttle:5,10')->name('tienda-atlantia.acceso');
        Route::get('/precios-planes', [PreciosPlanesController::class, 'index'])->name('precios-planes.index');
        Route::put('/precios-planes/apertura', [PreciosPlanesController::class, 'actualizarApertura'])->name('precios-planes.apertura');
        Route::put('/precios-planes/publicidad', [PreciosPlanesController::class, 'actualizarPublicidad'])->name('precios-planes.publicidad');
        Route::put('/precios-planes/{plan}', [PreciosPlanesController::class, 'actualizarPlan'])->where('plan', '[a-z_]+')->name('precios-planes.plan');
        Route::post('/precios-planes/{plan}/restaurar', [PreciosPlanesController::class, 'restaurarPlan'])->where('plan', '[a-z_]+')->name('precios-planes.restaurar');
        Route::get('/denuncias', [\App\Http\Controllers\Admin\DenunciaController::class, 'index'])->name('denuncias.index');
        Route::post('/denuncias/{denuncia:uuid}/resolver', [\App\Http\Controllers\Admin\DenunciaController::class, 'resolver'])->name('denuncias.resolver');
        Route::post('/denuncias/tiendas/{vendor:uuid}/reactivar', [\App\Http\Controllers\Admin\DenunciaController::class, 'reactivar'])->name('denuncias.reactivar');
        Route::get('/mensajes', [MensajeController::class, 'index'])->name('mensajes.index');
        Route::post('/mensajes/{mensaje:uuid}/atender', [MensajeController::class, 'atender'])->name('mensajes.atender');
        Route::get('/cupones', [CuponController::class, 'index'])->name('cupones.index');
        Route::post('/cupones', [CuponController::class, 'store'])->name('cupones.store');
        Route::put('/cupones/{cupon}', [CuponController::class, 'update'])->name('cupones.update');
        Route::delete('/cupones/{cupon}', [CuponController::class, 'destroy'])->name('cupones.destroy');

        Route::get('/dte', [DteController::class, 'index'])->name('dte.index');
        Route::get('/dte/{dte:uuid}', [DteController::class, 'show'])->name('dte.show');
        Route::post('/dte/{dte:uuid}/retry', [DteController::class, 'retry'])->name('dte.retry');
        Route::post('/dte/{dte:uuid}/anular', [DteController::class, 'anular'])->name('dte.anular');

        Route::get('/zonas-entrega', [ZonaEntregaController::class, 'index'])->name('zonas-entrega.index');
        Route::post('/zonas-entrega', [ZonaEntregaController::class, 'store'])->name('zonas-entrega.store');
        Route::put('/zonas-entrega/{zona}', [ZonaEntregaController::class, 'update'])->name('zonas-entrega.update');
        Route::delete('/zonas-entrega/{zona}', [ZonaEntregaController::class, 'destroy'])->name('zonas-entrega.destroy');

        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/{auditLog}', [AuditoriaController::class, 'show'])->name('auditoria.show');
        Route::get('/observabilidad', ObservabilityController::class)->name('observabilidad.index');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->middleware('throttle:20,1')->name('reportes.exportar');
        Route::get('/datos-mercado', [DatosMercadoController::class, 'index'])->name('datos-mercado.index');
        Route::get('/datos-mercado/exportar', [DatosMercadoController::class, 'exportar'])->name('datos-mercado.exportar');

        Route::get('/ml/monitor', [MlMonitorController::class, 'index'])->name('ml.monitor');
        Route::post('/ml/activar', [MlMonitorController::class, 'activate'])
            ->middleware('throttle:5,1')
            ->name('ml.activate');
        Route::get('/ml/reentrenamiento', [MlReentrenamientoController::class, 'index'])
            ->name('ml.reentrenamiento.index');
        Route::post('/ml/reentrenamiento', [MlReentrenamientoController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('ml.reentrenamiento.store');

        Route::get('/antifraude', [AntifraudeController::class, 'index'])->name('antifraude.index');
        Route::post('/antifraude/lote/resolver', [AntifraudeController::class, 'resolveBatch'])
            ->name('antifraude.batch-resolve');
        Route::get('/antifraude/{fraudAlert:uuid}', [AntifraudeController::class, 'show'])->name('antifraude.show');
        Route::patch('/antifraude/{fraudAlert:uuid}/resolver', [AntifraudeController::class, 'resolve'])
            ->name('antifraude.resolve');
    });
