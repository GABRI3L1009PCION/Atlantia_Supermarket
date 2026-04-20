<?php

use App\Http\Controllers\Vendedor\ComisionController;
use App\Http\Controllers\Vendedor\DashboardController;
use App\Http\Controllers\Vendedor\DteController;
use App\Http\Controllers\Vendedor\InventarioController;
use App\Http\Controllers\Vendedor\PedidoController;
use App\Http\Controllers\Vendedor\PerfilFiscalController;
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
    ->middleware(['auth', 'verified', 'role:vendedor', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::put('/productos/{producto:uuid}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('/productos/{producto:uuid}', [ProductoController::class, 'destroy'])->name('productos.destroy');

        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::put('/inventario/{producto:uuid}', [InventarioController::class, 'update'])->name('inventario.update');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::patch('/pedidos/{pedido:uuid}/estado', [PedidoController::class, 'updateEstado'])
            ->name('pedidos.estado');

        Route::get('/zonas-entrega', [ZonaEntregaController::class, 'index'])->name('zonas-entrega.index');
        Route::put('/zonas-entrega', [ZonaEntregaController::class, 'sync'])->name('zonas-entrega.sync');

        Route::get('/resenas', [ResenaController::class, 'index'])->name('resenas.index');

        Route::get('/perfil-fiscal', [PerfilFiscalController::class, 'edit'])->name('perfil-fiscal.edit');
        Route::put('/perfil-fiscal', [PerfilFiscalController::class, 'update'])->name('perfil-fiscal.update');

        Route::get('/dte', [DteController::class, 'index'])->name('dte.index');
        Route::get('/dte/{dte:uuid}', [DteController::class, 'show'])->name('dte.show');
        Route::post('/dte/{dte:uuid}/anular', [DteController::class, 'anular'])
            ->middleware('throttle:10,1')
            ->name('dte.anular');

        Route::get('/comisiones', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::get('/comisiones/{comision}', [ComisionController::class, 'show'])->name('comisiones.show');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/predicciones-demanda', [PrediccionDemandaController::class, 'index'])
            ->name('predicciones.index');

        Route::get('/sugerencias-reabasto', [SugerenciaReabastoController::class, 'index'])
            ->name('reabasto.index');
        Route::patch('/sugerencias-reabasto/{suggestion}/aceptar', [SugerenciaReabastoController::class, 'accept'])
            ->name('reabasto.accept');
    });
