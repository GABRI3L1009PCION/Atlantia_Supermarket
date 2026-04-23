<?php

use App\Http\Controllers\Cliente\CarritoController;
use App\Http\Controllers\Cliente\CatalogoController;
use App\Http\Controllers\Cliente\CheckoutController;
use App\Http\Controllers\Cliente\DireccionController;
use App\Http\Controllers\Cliente\PedidoController;
use App\Http\Controllers\Cliente\PerfilController;
use App\Http\Controllers\Cliente\ProductoController;
use App\Http\Controllers\Cliente\RecomendacionController;
use App\Http\Controllers\Cliente\ResenaController;
use App\Http\Controllers\Cliente\SeguimientoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Cliente
|--------------------------------------------------------------------------
|
| Experiencia de compra, carrito, checkout, pedidos, direcciones, seguimiento,
| recomendaciones y resenas de clientes.
|
*/

Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/productos/{producto:uuid}', [ProductoController::class, 'show'])->name('productos.show');

Route::prefix('cliente')
    ->as('cliente.')
    ->middleware(['throttle:120,1'])
    ->group(function (): void {
        Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
        Route::post('/carrito/items', [CarritoController::class, 'store'])->name('carrito.items.store');
        Route::put('/carrito/items/{item}', [CarritoController::class, 'update'])->name('carrito.items.update');
        Route::delete('/carrito/items/{item}', [CarritoController::class, 'destroy'])->name('carrito.items.destroy');
    });

Route::prefix('cliente')
    ->as('cliente.')
    ->middleware(['auth', 'verified', 'role:cliente', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->middleware(['checkout.rate', 'throttle:checkout'])
            ->name('checkout.store');

        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::get('/pedidos/{pedido:uuid}/seguimiento', [SeguimientoController::class, 'show'])
            ->name('pedidos.seguimiento');
        Route::get('/pedidos/{pedido:uuid}/seguimiento/live', [SeguimientoController::class, 'live'])
            ->name('pedidos.seguimiento.live');

        Route::get('/direcciones', [DireccionController::class, 'index'])->name('direcciones.index');
        Route::post('/direcciones', [DireccionController::class, 'store'])->name('direcciones.store');
        Route::put('/direcciones/{direccion}', [DireccionController::class, 'update'])->name('direcciones.update');
        Route::delete('/direcciones/{direccion}', [DireccionController::class, 'destroy'])->name('direcciones.destroy');

        Route::get('/resenas', [ResenaController::class, 'index'])->name('resenas.index');
        Route::post('/pedidos/{pedido:uuid}/resenas', [ResenaController::class, 'store'])->name('resenas.store');
        Route::delete('/resenas/{resena:uuid}', [ResenaController::class, 'destroy'])->name('resenas.destroy');

        Route::get('/recomendaciones', [RecomendacionController::class, 'index'])->name('recomendaciones.index');

        Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
        Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    });
