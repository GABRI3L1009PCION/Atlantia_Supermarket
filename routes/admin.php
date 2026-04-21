<?php

use App\Http\Controllers\Admin\AntifraudeController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\ComisionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DteController;
use App\Http\Controllers\Admin\EmpleadoController;
use App\Http\Controllers\Admin\MlMonitorController;
use App\Http\Controllers\Admin\MlReentrenamientoController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\Admin\RepartidorController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\ResenaController;
use App\Http\Controllers\Admin\RolPermisoController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\VendedorController;
use App\Http\Controllers\Admin\ZonaEntregaController;
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
    ->middleware(['auth', 'verified', 'role:admin|super_admin', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/vendedores', [VendedorController::class, 'index'])->name('vendedores.index');
        Route::get('/vendedores/{vendor:uuid}', [VendedorController::class, 'show'])->name('vendedores.show');
        Route::patch('/vendedores/{vendor:uuid}/aprobar', [VendedorController::class, 'approve'])
            ->name('vendedores.approve');
        Route::patch('/vendedores/{vendor:uuid}/suspender', [VendedorController::class, 'suspend'])
            ->name('vendedores.suspend');
        Route::patch('/vendedores/{vendor:uuid}/reactivar', [VendedorController::class, 'reactivate'])
            ->name('vendedores.reactivate');
        Route::delete('/vendedores/{vendor:uuid}', [VendedorController::class, 'destroy'])->name('vendedores.destroy');

        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/{producto:uuid}', [ProductoController::class, 'show'])->name('productos.show');
        Route::put('/productos/{producto:uuid}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto:uuid}/moderar', [ProductoController::class, 'moderate'])
            ->name('productos.moderate');
        Route::delete('/productos/{producto:uuid}', [ProductoController::class, 'destroy'])->name('productos.destroy');

        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');

        Route::get('/repartidores', [RepartidorController::class, 'index'])->name('repartidores.index');
        Route::get('/repartidores/{repartidor:uuid}', [RepartidorController::class, 'show'])
            ->name('repartidores.show');

        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario:uuid}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::put('/usuarios/{usuario:uuid}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario:uuid}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

        Route::get('/roles-permisos', [RolPermisoController::class, 'index'])->name('roles-permisos.index');
        Route::post('/roles-permisos/roles', [RolPermisoController::class, 'store'])->name('roles-permisos.store');
        Route::post('/roles-permisos/permisos', [RolPermisoController::class, 'storePermission'])
            ->name('roles-permisos.permisos.store');
        Route::put('/roles-permisos/{role}', [RolPermisoController::class, 'sync'])->name('roles-permisos.sync');
        Route::delete('/roles-permisos/{role}', [RolPermisoController::class, 'destroy'])
            ->name('roles-permisos.destroy');

        Route::get('/resenas', [ResenaController::class, 'index'])->name('resenas.index');
        Route::patch('/resenas/{resena:uuid}/moderar', [ResenaController::class, 'moderate'])->name('resenas.moderate');

        Route::get('/comisiones', [ComisionController::class, 'index'])->name('comisiones.index');
        Route::put('/comisiones/{comision}', [ComisionController::class, 'update'])->name('comisiones.update');

        Route::get('/dte', [DteController::class, 'index'])->name('dte.index');
        Route::get('/dte/{dte:uuid}', [DteController::class, 'show'])->name('dte.show');

        Route::get('/zonas-entrega', [ZonaEntregaController::class, 'index'])->name('zonas-entrega.index');
        Route::post('/zonas-entrega', [ZonaEntregaController::class, 'store'])->name('zonas-entrega.store');
        Route::put('/zonas-entrega/{zona}', [ZonaEntregaController::class, 'update'])->name('zonas-entrega.update');

        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/{auditLog}', [AuditoriaController::class, 'show'])->name('auditoria.show');

        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

        Route::get('/ml/monitor', [MlMonitorController::class, 'index'])->name('ml.monitor');
        Route::get('/ml/reentrenamiento', [MlReentrenamientoController::class, 'index'])
            ->name('ml.reentrenamiento.index');
        Route::post('/ml/reentrenamiento', [MlReentrenamientoController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('ml.reentrenamiento.store');

        Route::get('/antifraude', [AntifraudeController::class, 'index'])->name('antifraude.index');
        Route::patch('/antifraude/{fraudAlert:uuid}/resolver', [AntifraudeController::class, 'resolve'])
            ->name('antifraude.resolve');
    });
