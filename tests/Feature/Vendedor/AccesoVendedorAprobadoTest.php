<?php

namespace Tests\Feature\Vendedor;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\SolicitudVendedorRechazadaNotification;
use App\Notifications\VendedorAprobadoNotification;
use App\Services\Auth\TotpService;
use App\Services\Vendedores\AccesoVendedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccesoVendedorAprobadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('vendedor', 'web');
        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('super_admin');
    }

    private function solicitudPendiente(): Vendor
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'email' => 'tienda@example.test',
            'email_verified_at' => null,
            'password' => Hash::make('aleatoria-desconocida'),
        ]);
        $user->assignRole('vendedor');

        return Vendor::factory()->pending()->create(['user_id' => $user->id, 'application_code' => 'VND-2026-PRUEBA']);
    }

    public function test_approval_emails_a_single_use_link_to_create_password_then_two_factor(): void
    {
        Notification::fake();
        $vendor = $this->solicitudPendiente();

        $this->actingAs($this->admin)
            ->patch(route('admin.vendedores.approve', $vendor), ['commission_percentage' => 7, 'monthly_rent' => 0])
            ->assertSessionHas('success');

        $enlace = null;
        Notification::assertSentTo($vendor->user, VendedorAprobadoNotification::class, function ($notificacion, $canales, $usuario) use (&$enlace): bool {
            $enlace = $notificacion->toMail($usuario)->viewData['enlace'];

            return in_array('mail', $canales, true);
        });

        auth()->logout();

        $this->get($enlace)->assertOk()->assertSee('Crea tu contraseña');

        $this->post($enlace, ['password' => 'debil', 'password_confirmation' => 'debil'])->assertSessionHasErrors('password');

        $this->post($enlace, [
            'password' => 'Rio#Verde84x',
            'password_confirmation' => 'Rio#Verde84x',
        ])->assertRedirect(route('two-factor.challenge'));

        $user = $vendor->user->fresh();
        $this->assertTrue(Hash::check('Rio#Verde84x', $user->password));
        $this->assertNotNull($user->email_verified_at);

        /* Un solo uso: tras crear la contraseña, el mismo enlace ya no sirve. */
        $this->get($enlace)->assertOk()->assertSee('Este enlace ya no sirve');
    }

    public function test_link_expires_after_72_hours(): void
    {
        $vendor = $this->solicitudPendiente();
        $vendor->update(['status' => 'approved', 'is_approved' => true]);
        $vendor->user->update(['status' => 'active']);
        $enlace = app(AccesoVendedorService::class)->enlaceCrearContrasena($vendor->user);

        $this->travel(73)->hours();

        $this->get($enlace)->assertOk()->assertSee('Este enlace ya no sirve');
    }

    public function test_rejection_requires_reason_and_notifies_applicant(): void
    {
        Notification::fake();
        $vendor = $this->solicitudPendiente();

        $this->actingAs($this->admin)
            ->patch(route('admin.vendedores.reject', $vendor), ['motivo' => 'corto'])
            ->assertSessionHasErrors('motivo');

        $this->actingAs($this->admin)
            ->patch(route('admin.vendedores.reject', $vendor), ['motivo' => 'La foto del DPI no se puede leer, envía una más clara.'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $vendor->fresh()->status);
        Notification::assertSentTo($vendor->user, SolicitudVendedorRechazadaNotification::class);

        $this->get(route('vendedor.solicitud.show', 'VND-2026-PRUEBA'))
            ->assertOk()
            ->assertSee('La foto del DPI no se puede leer')
            ->assertDontSee('tienda@example.test');
    }

    public function test_tracking_page_lets_approved_vendor_request_a_new_link(): void
    {
        Notification::fake();
        $vendor = $this->solicitudPendiente();
        $vendor->update(['status' => 'approved', 'is_approved' => true]);
        $vendor->user->update(['status' => 'active']);

        $this->get(route('vendedor.solicitud.show', 'VND-2026-PRUEBA'))
            ->assertOk()
            ->assertSee('¡Tu tienda fue aprobada!', false)
            ->assertSee('ti***da@example.test')
            ->assertDontSee('tienda@example.test');

        $this->post(route('vendedor.solicitud.reenviar', 'VND-2026-PRUEBA'))->assertSessionHas('status');
        Notification::assertSentTo($vendor->user, VendedorAprobadoNotification::class);
    }

    public function test_vendor_can_sign_in_with_a_backup_code_only_once(): void
    {
        $vendor = $this->solicitudPendiente();
        $user = $vendor->user;
        $user->forceFill(['status' => 'active', 'two_factor_enabled' => true, 'two_factor_confirmed_at' => now()])->save();
        DB::table('two_factor_authentications')->insert([
            'user_id' => $user->id,
            'secret' => encrypt(app(TotpService::class)->generateSecret()),
            'recovery_codes' => encrypt(json_encode(['ABCDE12345', 'FGHIJ67890'])),
            'confirmed_at' => now(),
            'failed_challenges' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['auth.2fa_user_id' => $user->id])
            ->post(route('two-factor.verify'), ['code' => 'abcde-12345'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        auth()->logout();

        $this->withSession(['auth.2fa_user_id' => $user->id])
            ->post(route('two-factor.verify'), ['code' => 'ABCDE-12345'])
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_admin_can_resend_access_and_reset_two_factor(): void
    {
        Notification::fake();
        $vendor = $this->solicitudPendiente();
        $vendor->update(['status' => 'approved', 'is_approved' => true]);
        $vendor->user->forceFill(['status' => 'active', 'two_factor_enabled' => true, 'two_factor_confirmed_at' => now()])->save();

        $this->actingAs($this->admin)->post(route('admin.vendedores.reenviar-acceso', $vendor))->assertSessionHas('success');
        Notification::assertSentTo($vendor->user, VendedorAprobadoNotification::class);

        $this->actingAs($this->admin)->delete(route('admin.vendedores.reiniciar-2fa', $vendor))->assertSessionHas('success');
        $this->assertNull($vendor->user->fresh()->two_factor_confirmed_at);
    }
}
