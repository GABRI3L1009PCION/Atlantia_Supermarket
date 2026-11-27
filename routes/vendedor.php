<?php

use App\Http\Controllers\ImportarProductosController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\Vendedor\AparienciaTiendaController;
use App\Http\Controllers\Vendedor\ComisionController;
use App\Http\Controllers\Vendedor\CuponController as CuponVendedorController;
use App\Http\Controllers\Vendedor\DashboardController;
use App\Http\Controllers\Vendedor\DevolucionController as DevolucionVendedorController;
use App\Http\Controllers\Vendedor\SoporteController;
use App\Http\Controllers\Vendedor\DteController;
use App\Http\Controllers\Vendedor\InventarioController;
use App\Http\Controllers\Vendedor\OperacionTiendaController;
use App\Http\Controllers\Vendedor\PagosController;
use App\Http\Controllers\Vendedor\PedidoController;
use App\Http\Controllers\Vendedor\PerfilFiscalController;
use App\Http\Controllers\Vendedor\PlanYPublicidadController;
use App\Http\Controllers\Vendedor\PrediccionDemandaController;
use App\Http\Controllers\Vendedor\ProductoController;
use App\Http\Controllers\Vendedor\ReporteController;
use App\Http\Controllers\Vendedor\ResenaController;
use App\Http\Controllers\Vendedor\SugerenciaReabastoController;
use App\Http\Controllers\Vendedor\ZonaEntregaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Vendedor
|--------------------------------------------------------------------------
|
| Operacion del vendedor local: catalogo propio, inventario, pedidos,
| perfil fiscal FEL, DTE, comisiones, reportes y soporte ML.
|
*/

Route::prefix('vendedor')
    ->as('vendedor.')
    ->middleware(['auth', 'verified', 'role:vendedor', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/importar', [ImportarProductosController::class, 'create'])->name('productos.importar');
        Route::post('/productos/importar', [ImportarProductosController::class, 'store'])->name('productos.importar.store');
        Route::get('/productos/importar/plantilla', [ImportarProductosController::class, 'plantilla'])->name('productos.importar.plantilla');
        Route::put('/productos/{producto:uuid}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/productos/{producto:uuid}', [ProductoController::class, 'destroy'])->name('productos.destroy');

        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::put('/inventario/{producto:uuid}', [InventarioController::class, 'update'])->name('inventario.update');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/devoluciones', [DevolucionVendedorController::class, 'index'])->name('devoluciones.index');
        Route::get('/ayuda', [SoporteController::class, 'index'])->name('soporte.index');
        Route::post('/ayuda', [SoporteController::class, 'store'])->middleware('throttle:5,10')->name('soporte.store');
        Route::post('/devoluciones/{devolucion:uuid}/respuesta', [DevolucionVendedorController::class, 'responder'])->middleware('throttle:20,1')->name('devoluciones.responder');
        Route::get('/pedidos/nuevos', [PedidoController::class, 'nuevos'])->name('pedidos.nuevos');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::get('/pedidos/{pedido:uuid}/comanda', [PedidoController::class, 'comanda'])->name('pedidos.comanda');
        Route::patch('/pedidos/{pedido:uuid}/estado', [PedidoController::class, 'updateEstado'])
            ->name('pedidos.estado');
        Route::post('/pedidos/{pedido:uuid}/factura', [\App\Http\Controllers\Vendedor\FacturaManualController::class, 'store'])->middleware('throttle:20,1')->name('pedidos.factura.store');
        Route::delete('/pedidos/{pedido:uuid}/factura', [\App\Http\Controllers\Vendedor\FacturaManualController::class, 'destroy'])->name('pedidos.factura.destroy');

        Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
        Route::get('/cupones', [CuponVendedorController::class, 'index'])->name('cupones.index');
        Route::post('/cupones', [CuponVendedorController::class, 'store'])->middleware('throttle:20,1')->name('cupones.store');
        Route::put('/cupones/{cupon:uuid}', [CuponVendedorController::class, 'update'])->name('cupones.update');
        Route::patch('/cupones/{cupon:uuid}/estado', [CuponVendedorController::class, 'toggle'])->name('cupones.toggle');
        Route::delete('/cupones/{cupon:uuid}', [CuponVendedorController::class, 'destroy'])->name('cupones.destroy');
        Route::post('/promociones', [PromocionController::class, 'store'])->name('promociones.store');
        Route::patch('/promociones/{promocion}', [PromocionController::class, 'toggle'])->name('promociones.toggle');
        Route::delete('/promociones/{promocion}', [PromocionController::class, 'destroy'])->name('promociones.destroy');

        Route::get('/plan', [PlanYPublicidadController::class, 'index'])->name('plan.index');
        Route::post('/plan', [PlanYPublicidadController::class, 'elegirPlan'])->middleware('throttle:10,1')->name('plan.elegir');
        Route::post('/plan/prueba', [PlanYPublicidadController::class, 'probarGratis'])->middleware('throttle:5,1')->name('plan.prueba');
        Route::post('/plan/cotizacion', [PlanYPublicidadController::class, 'pedirCotizacion'])->middleware('throttle:5,60')->name('plan.cotizacion');
        Route::post('/publicidad/destacado', [PlanYPublicidadController::class, 'comprarDestacado'])->middleware('throttle:10,1')->name('publicidad.destacado');
        Route::post('/publicidad/patrocinado', [PlanYPublicidadController::class, 'comprarPatrocinado'])->middleware('throttle:10,1')->name('publicidad.patrocinado');
        Route::post('/publicidad/banner', [PlanYPublicidadController::class, 'comprarBanner'])->middleware('throttle:10,1')->name('publicidad.banner');
        Route::get('/servicios/{servicio}/retorno', [PlanYPublicidadController::class, 'retorno'])->name('servicios.retorno');

        Route::get('/imagen-tienda', [AparienciaTiendaController::class, 'edit'])->name('apariencia.edit');
        Route::put('/imagen-tienda', [AparienciaTiendaController::class, 'update'])->middleware('throttle:20,1')->name('apariencia.update');
        Route::delete('/imagen-tienda/portada', [AparienciaTiendaController::class, 'quitarPortada'])->name('apariencia.portada.destroy');

        Route::get('/operacion', [OperacionTiendaController::class, 'edit'])->name('operacion.edit');
        Route::put('/operacion', [OperacionTiendaController::class, 'update'])->name('operacion.update');
        Route::post('/operacion/pausar', [OperacionTiendaController::class, 'pausar'])->name('operacion.pausar');
        Route::post('/operacion/reanudar', [OperacionTiendaController::class, 'reanudar'])->name('operacion.reanudar');

        Route::get('/zonas-entrega', [ZonaEntregaController::class, 'index'])->name('zonas-entrega.index');
        Route::put('/zonas-entrega', [ZonaEntregaController::class, 'sync'])->name('zonas-entrega.sync');

        Route::get('/resenas', [ResenaController::class, 'index'])->name('resenas.index');
        Route::post('/resenas/{resena:uuid}/respuesta', [ResenaController::class, 'responder'])->middleware('throttle:30,1')->name('resenas.responder');
        Route::delete('/resenas/{resena:uuid}/respuesta', [ResenaController::class, 'quitarRespuesta'])->name('resenas.respuesta.destroy');

        Route::get('/perfil-fiscal', [PerfilFiscalController::class, 'edit'])->name('perfil-fiscal.edit');
        Route::put('/perfil-fiscal', [PerfilFiscalController::class, 'update'])->name('perfil-fiscal.update');

        Route::get('/dte', [DteController::class, 'index'])->name('dte.index');
        Route::get('/dte/exportar', [DteController::class, 'exportar'])->name('dte.exportar');
        Route::get('/dte/{dte:uuid}', [DteController::class, 'show'])->name('dte.show');
        Route::post('/dte/{dte:uuid}/anular', [DteController::class, 'anular'])
            ->middleware('throttle:10,1')
            ->name('dte.anular');

        Route::get('/ventas-y-pagos', [PagosController::class, 'index'])->name('pagos.index');
        Route::put('/ventas-y-pagos/frecuencia', [PagosController::class, 'frecuencia'])->name('pagos.frecuencia');
        Route::post('/ventas-y-pagos/solicitar', [PagosController::class, 'solicitar'])->middleware('throttle:5,1')->name('pagos.solicitar');
        Route::get('/ventas-y-pagos/{liquidacion}', [PagosController::class, 'show'])->name('pagos.show');

        Route::get('/comisiones', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::get('/comisiones/{comision}', [ComisionController::class, 'show'])->name('comisiones.show');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/descargar', [ReporteController::class, 'descargar'])->middleware('throttle:20,1')->name('reportes.descargar');
        Route::get('/predicciones-demanda', [PrediccionDemandaController::class, 'index'])
            ->name('predicciones.index');

        Route::get('/sugerencias-reabasto', [SugerenciaReabastoController::class, 'index'])
            ->name('reabasto.index');
        Route::post('/sugerencias-reabasto/{producto:uuid}/recibido', [SugerenciaReabastoController::class, 'recibir'])
            ->middleware('throttle:60,1')->name('reabasto.recibir');
        Route::patch('/sugerencias-reabasto/{suggestion}/aceptar', [SugerenciaReabastoController::class, 'accept'])
            ->name('reabasto.accept');
    });
