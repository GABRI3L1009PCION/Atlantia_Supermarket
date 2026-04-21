<?php

namespace App\Providers;

use App\Models\CarritoItem;
use App\Models\Categoria;
use App\Models\Cliente\Direccion;
use App\Models\DeliveryRoute;
use App\Models\DeliveryZone;
use App\Models\Dte\DteFactura;
use App\Models\Empleado;
use App\Models\Inventario;
use App\Models\Ml\FraudAlert;
use App\Models\Ml\RestockSuggestion;
use App\Models\Payment;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Resena;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCommission;
use App\Models\AuditLog;
use App\Policies\AuditLogPolicy;
use App\Observers\PedidoObserver;
use App\Observers\ProductoObserver;
use App\Observers\ResenaObserver;
use App\Observers\UserObserver;
use App\Policies\CarritoItemPolicy;
use App\Policies\CategoriaPolicy;
use App\Policies\DeliveryRoutePolicy;
use App\Policies\DeliveryZonePolicy;
use App\Policies\DireccionPolicy;
use App\Policies\DtePolicy;
use App\Policies\EmpleadoPolicy;
use App\Policies\FraudAlertPolicy;
use App\Policies\InventarioPolicy;
use App\Policies\PedidoPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductoPolicy;
use App\Policies\ResenaPolicy;
use App\Policies\RolePolicy;
use App\Policies\RestockSuggestionPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VendorCommissionPolicy;
use App\Services\Fel\CertificadorFelInterface;
use App\Services\Fel\InfileCertificadorService;
use App\Services\Ml\MlServiceClient;
use App\Services\Ml\MlServiceClientInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

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
        if (method_exists(Passport::class, 'ignoreMigrations')) {
            Passport::ignoreMigrations();
        }

        Gate::policy(Producto::class, ProductoPolicy::class);
        Gate::policy(Pedido::class, PedidoPolicy::class);
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(Resena::class, ResenaPolicy::class);
        Gate::policy(DteFactura::class, DtePolicy::class);
        Gate::policy(CarritoItem::class, CarritoItemPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Direccion::class, DireccionPolicy::class);
        Gate::policy(DeliveryRoute::class, DeliveryRoutePolicy::class);
        Gate::policy(Inventario::class, InventarioPolicy::class);
        Gate::policy(VendorCommission::class, VendorCommissionPolicy::class);
        Gate::policy(FraudAlert::class, FraudAlertPolicy::class);
        Gate::policy(RestockSuggestion::class, RestockSuggestionPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Categoria::class, CategoriaPolicy::class);
        Gate::policy(Empleado::class, EmpleadoPolicy::class);
        Gate::policy(DeliveryZone::class, DeliveryZonePolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('viewAdminDashboard', fn (User $user): bool => $user->isAdministrator());
        Gate::define('viewAdminReports', fn (User $user): bool => $user->isAdministrator());
        Gate::define('monitorMl', fn (User $user): bool => $user->isAdministrator());
        Gate::define('trainMl', fn (User $user): bool => $user->isAdministrator());
        Gate::define('viewRecommendations', fn (User $user): bool => $user->hasRole('cliente'));
        Gate::define('viewVendorDashboard', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('manageFiscalProfile', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('viewOwnPredictions', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('viewVendorReports', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('viewVendorReviews', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('manageVendorZones', fn (User $user): bool => $user->hasRole('vendedor') && $user->vendor !== null);
        Gate::define('viewCourierDashboard', fn (User $user): bool => $user->hasRole('repartidor'));
        Gate::define('sendLocation', fn (User $user): bool => $user->hasRole('repartidor'));
        Gate::define('viewRepartidores', fn (User $user): bool => $user->isAdministrator());
        Gate::define('viewRepartidor', fn (User $user, User $repartidor): bool => $user->isAdministrator()
            && $repartidor->hasRole('repartidor'));

        Producto::observe(ProductoObserver::class);
        Pedido::observe(PedidoObserver::class);
        User::observe(UserObserver::class);
        Resena::observe(ResenaObserver::class);
    }
}
