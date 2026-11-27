<?php

use App\Http\Controllers\Cliente\CarritoController;
use App\Http\Controllers\Cliente\CatalogoController;
use App\Http\Controllers\Cliente\CategoriaController;
use App\Http\Controllers\Cliente\CheckoutController;
use App\Http\Controllers\Cliente\ComercioController;
use App\Http\Controllers\Cliente\DevolucionController;
use App\Http\Controllers\Cliente\DireccionController;
use App\Http\Controllers\Cliente\PagoRecurrenteController;
use App\Http\Controllers\Cliente\PedidoController;
use App\Http\Controllers\Cliente\PerfilController;
use App\Http\Controllers\Cliente\ProductoController;
use App\Http\Controllers\Cliente\RecomendacionController;
use App\Http\Controllers\Cliente\ResenaController;
use App\Http\Controllers\Cliente\SaldoController;
use App\Http\Controllers\Cliente\SeguimientoController;
use App\Http\Controllers\Cliente\TarjetaYMembresiaController;
use App\Http\Controllers\Cliente\UbicacionController;
use App\Http\Controllers\Cliente\WishlistController;
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
Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
Route::get('/comercios', [ComercioController::class, 'index'])->name('comercios.index');
Route::get('/comercios/{vendor:slug}', [ComercioController::class, 'show'])->name('comercios.show');
Route::get('/productos/{producto:uuid}', [ProductoController::class, 'show'])->name('productos.show');
Route::post('/cliente/ubicacion', [UbicacionController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('cliente.ubicacion.store');

Route::prefix('cliente')
    ->as('cliente.')
    ->middleware(['throttle:120,1'])
    ->group(function (): void {
        Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
        Route::post('/carrito/items', [CarritoController::class, 'store'])->name('carrito.items.store');
        Route::put('/carrito/items/{item}', [CarritoController::class, 'update'])->name('carrito.items.update');
        Route::delete('/carrito/items/{item}', [CarritoController::class, 'destroy'])->name('carrito.items.destroy');

        Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->middleware(['checkout.rate', 'throttle:checkout'])
            ->name('checkout.store');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{producto:uuid}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::post('/wishlist/agregar-todo', [WishlistController::class, 'addAllToCart'])->name('wishlist.add-all');
        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');

        Route::get('/pedidos/{pedido:uuid}/confirmacion', [PedidoController::class, 'guestShow'])
            ->name('pedidos.guest-show');
        /* Seguimiento sin cuenta: se abre desde el enlace firmado que llega por WhatsApp. */
        Route::get('/pedidos/{pedido:uuid}/seguimiento-invitado', [SeguimientoController::class, 'guestShow'])
            ->name('pedidos.guest-seguimiento');
        Route::get('/pedidos/{pedido:uuid}/seguimiento-invitado/live', [SeguimientoController::class, 'guestLive'])
            ->name('pedidos.guest-seguimiento.live');

        Route::middleware('throttle:30,1')->group(function (): void {
            Route::get('/pagos/recurrente/{payment:uuid}/retorno', [PagoRecurrenteController::class, 'retorno'])
                ->name('pagos.recurrente.retorno');
            Route::get('/pagos/recurrente/{payment:uuid}/cancelado', [PagoRecurrenteController::class, 'cancelado'])
                ->name('pagos.recurrente.cancelado');
            Route::get('/pagos/recurrente/{payment:uuid}/pagar', [PagoRecurrenteController::class, 'pagar'])
                ->name('pagos.recurrente.pagar');
        });
    });

Route::prefix('cliente')
    ->as('cliente.')
    ->middleware(['auth', 'verified', 'role:cliente', 'throttle:120,1'])
    ->group(function (): void {
        Route::get('/pedidos/{pedido:uuid}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::post('/tiendas/{vendor:slug}/denunciar', [\App\Http\Controllers\Cliente\DenunciaController::class, 'store'])->middleware('throttle:5,60')->name('tiendas.denunciar');
        Route::post('/pedidos/{pedido:uuid}/calificar-repartidor', [PedidoController::class, 'calificarRepartidor'])->middleware('throttle:10,1')->name('pedidos.calificar-repartidor');
        Route::post('/pedidos/{pedido:uuid}/cancelar', [PedidoController::class, 'cancelar'])->middleware('throttle:5,1')->name('pedidos.cancelar');
        Route::post('/pedidos/{pedido:uuid}/volver-a-pedir', [PedidoController::class, 'repetir'])->name('pedidos.repetir');
        Route::post('/pedidos/{pedido:uuid}/recurrente', [PedidoController::class, 'recurrente'])->name('pedidos.recurrente');
        Route::get('/pedidos-recurrentes/{recurrente}/cargar', [PedidoController::class, 'cargarRecurrente'])->name('recurrentes.cargar');
        Route::get('/pedidos/{pedido:uuid}/devolucion', [DevolucionController::class, 'create'])
            ->name('devoluciones.create');
        Route::post('/pedidos/{pedido:uuid}/devolucion', [DevolucionController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('devoluciones.store');
        Route::get('/pedidos/{pedido:uuid}/seguimiento', [SeguimientoController::class, 'show'])
            ->name('pedidos.seguimiento');
        Route::get('/pedidos/{pedido:uuid}/seguimiento/live', [SeguimientoController::class, 'live'])
            ->name('pedidos.seguimiento.live');

        Route::get('/saldo', [SaldoController::class, 'index'])->name('saldo.index');
        Route::get('/membresia', [TarjetaYMembresiaController::class, 'index'])->name('membresia.index');
        Route::post('/membresia', [TarjetaYMembresiaController::class, 'suscribir'])->middleware('throttle:6,1')->name('membresia.suscribir');
        Route::get('/membresia/retorno', [TarjetaYMembresiaController::class, 'retornoMembresia'])->name('membresia.retorno');
        Route::post('/membresia/cancelar', [TarjetaYMembresiaController::class, 'cancelarMembresia'])->name('membresia.cancelar');
        Route::post('/tarjeta', [TarjetaYMembresiaController::class, 'guardarTarjeta'])->middleware('throttle:6,1')->name('tarjeta.guardar');
        Route::get('/tarjeta/retorno', [TarjetaYMembresiaController::class, 'retornoTarjeta'])->name('tarjeta.retorno');
        Route::delete('/tarjeta', [TarjetaYMembresiaController::class, 'eliminarTarjeta'])->name('tarjeta.eliminar');

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
        Route::put('/perfil/avisos', [PerfilController::class, 'avisos'])->name('perfil.avisos');
        Route::delete('/perfil', [PerfilController::class, 'destroy'])->middleware('throttle:6,10')->name('perfil.destroy');
    });
