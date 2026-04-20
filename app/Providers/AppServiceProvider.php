<?php

namespace App\Providers;

use App\Models\CarritoItem;
use App\Models\Dte\DteFactura;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
use App\Models\Vendor;
use App\Observers\PedidoObserver;
use App\Observers\ProductoObserver;
use App\Observers\ResenaObserver;
use App\Observers\UserObserver;
use App\Policies\CarritoItemPolicy;
use App\Policies\DtePolicy;
use App\Policies\PedidoPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\ResenaPolicy;
use App\Policies\VendorPolicy;
use App\Services\Fel\CertificadorFelInterface;
use App\Services\Fel\InfileCertificadorService;
use App\Services\Ml\MlServiceClient;
use App\Services\Ml\MlServiceClientInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CertificadorFelInterface::class, InfileCertificadorService::class);
        $this->app->bind(MlServiceClientInterface::class, MlServiceClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::ignoreMigrations();

        Gate::policy(Producto::class, ProductoPolicy::class);
        Gate::policy(Pedido::class, PedidoPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(Resena::class, ResenaPolicy::class);
        Gate::policy(DteFactura::class, DtePolicy::class);
        Gate::policy(CarritoItem::class, CarritoItemPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });

        Producto::observe(ProductoObserver::class);
        Pedido::observe(PedidoObserver::class);
        User::observe(UserObserver::class);
        Resena::observe(ResenaObserver::class);
    }
}
