<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Cliente\CatalogoController;
use App\Http\Controllers\Cliente\SaldoController;
use App\Http\Controllers\ComprobanteCompraController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\DtePdfController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PublicidadController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\Vendedor\CrearAccesoController;
use App\Http\Controllers\Vendedor\SolicitudVendedorController;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\Monetizacion\NovedadesService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Web
|--------------------------------------------------------------------------
|
| Punto de entrada web de Atlantia Supermarket. Este archivo mantiene la
| composicion general y delega las rutas por actor a archivos dedicados.
|
*/

Route::middleware(['web', 'security.headers'])->group(function (): void {
    Route::get('/', [CatalogoController::class, 'index'])->name('home');
    Route::view('/contacto', 'cliente.contacto')->name('contacto');
    Route::post('/contacto', [ContactoController::class, 'store'])->middleware('throttle:5,10')->name('contacto.store');
    Route::view('/terminos-y-condiciones', 'legal.terminos')->name('legal.terminos');
    /* Nuestros servicios: envios, eventos, vender en Atlantia y publicidad. */
    Route::view('/servicios', 'servicios.index')->name('servicios');
    Route::view('/vende-en-atlantia', 'servicios.vende')->name('vende');
    Route::view('/anunciate', 'servicios.anunciate')->name('anunciate');
    Route::get('/eventos', function () {
        $categoria = Categoria::query()->where('slug', config('monetizacion.eventos.categoria_slug', 'eventos-y-fiestas'))->first();

        return view('servicios.eventos', [
            'productos' => $categoria === null ? collect() : Producto::query()->publicados()
                ->with(['vendor', 'inventario', 'imagenPrincipal'])
                ->where('categoria_id', $categoria->id)
                ->orderBy('precio_base')
                ->limit(48)
                ->get(),
        ]);
    })->name('eventos');
    Route::get('/envios', [EnvioController::class, 'create'])->name('envios.create');
    Route::post('/envios', [EnvioController::class, 'store'])->middleware('throttle:5,1')->name('envios.store');
    Route::get('/envios/{uuid}', [EnvioController::class, 'show'])->middleware('throttle:60,1')->name('envios.show');
    Route::get('/envios/{uuid}/live', [EnvioController::class, 'live'])->middleware('throttle:30,1')->name('envios.live');

    Route::get('/novedades', function (NovedadesService $novedades) {
        return view('cliente.novedades', [
            'destacados' => $novedades->destacados(),
            'ofertas' => $novedades->ofertas(),
            'nuevos' => $novedades->nuevos(),
        ]);
    })->name('novedades');
    Route::get('/publicidad/banner/{uuid}', [PublicidadController::class, 'banner'])
        ->middleware('throttle:60,1')
        ->name('publicidad.banner');
    Route::get('/publicidad/patrocinado/{servicio}', [PublicidadController::class, 'patrocinado'])
        ->middleware('throttle:60,1')
        ->name('publicidad.patrocinado');
    Route::post('/push/suscribir', [PushController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('push.suscribir');
    Route::post('/push/quitar', [PushController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('push.quitar');
    Route::get('/invitacion/{codigo}', [SaldoController::class, 'invitacion'])
        ->middleware('throttle:30,1')
        ->name('referidos.invitacion');
    Route::view('/politica-de-privacidad', 'legal.privacidad')->name('legal.privacidad');
    Route::get('/health', HealthController::class)->name('health');
    Route::get('/vendedor/solicitar', [SolicitudVendedorController::class, 'create'])
        ->name('vendedor.solicitar.create');
    Route::post('/vendedor/solicitar', [SolicitudVendedorController::class, 'store'])
        ->middleware('throttle:3,1440')
        ->name('vendedor.solicitar.store');
    Route::get('/vendedor/solicitud/{codigo}', [SolicitudVendedorController::class, 'show'])
        ->name('vendedor.solicitud.show');
    /* Reenvía el enlace para crear contraseña al correo registrado (nunca a otro). */
    Route::post('/vendedor/solicitud/{codigo}/reenviar-acceso', [SolicitudVendedorController::class, 'reenviarAcceso'])
        ->middleware('throttle:3,60')
        ->name('vendedor.solicitud.reenviar');
    /* Primer acceso del vendedor aprobado: enlace firmado de un solo uso. */
    Route::get('/vendedor/acceso/{usuario}', [CrearAccesoController::class, 'create'])
        ->middleware('throttle:20,1')
        ->name('vendedor.acceso.crear');
    Route::post('/vendedor/acceso/{usuario}', [CrearAccesoController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('vendedor.acceso.guardar');
    Route::get('/pedidos/{pedido:uuid}/comprobante', ComprobanteCompraController::class)
        ->middleware('throttle:30,1')
        ->name('pedidos.comprobante');
    Route::get('/dte/{dte:uuid}/pdf', DtePdfController::class)
        ->middleware('throttle:30,1')
        ->name('dte.pdf');
    Route::delete('/admin/salir-impersonacion', [ImpersonationController::class, 'stop'])
        ->middleware('auth')
        ->name('admin.impersonation.stop');
    Route::middleware('auth')->group(function (): void {
        Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
        Route::get('/notificaciones/recientes', [NotificacionController::class, 'recientes'])
            ->middleware('throttle:60,1')
            ->name('notificaciones.recientes');
        Route::get('/notificaciones/{notification}/abrir', [NotificacionController::class, 'open'])
            ->name('notificaciones.open');
        Route::post('/notificaciones/{notification}/leer', [NotificacionController::class, 'markAsRead'])
            ->name('notificaciones.read');
        Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'markAllAsRead'])
            ->name('notificaciones.read-all');
    });

    $routeFiles = [
        __DIR__.'/auth.php',
        __DIR__.'/cliente.php',
        __DIR__.'/admin.php',
        __DIR__.'/vendedor.php',
        __DIR__.'/repartidor.php',
        __DIR__.'/empleado.php',
    ];

    foreach ($routeFiles as $routeFile) {
        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }
});
