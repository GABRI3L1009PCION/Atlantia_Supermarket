<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TotpService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_shows_provisioning_key_for_pending_setup(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->withSession([
            'auth.2fa_user_id' => $user->id,
            'auth.2fa_remember' => false,
        ])->get(route('two-factor.challenge'));

        $response->assertOk();
        $response->assertSee('Clave manual');
        $response->assertSee($user->email);
    }

    public function test_pending_setup_can_be_confirmed_with_real_totp_code(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => null,
        ]);

        $this->withSession([
            'auth.2fa_user_id' => $user->id,
            'auth.2fa_remember' => false,
        ])->get(route('two-factor.challenge'));

        $authentication = DB::table('two_factor_authentications')->where('user_id', $user->id)->first();
        $this->assertNotNull($authentication);

        $secret = decrypt($authentication->secret);
        $code = app(TotpService::class)->codeAt($secret, time());

        $response = $this->withSession([
            'auth.2fa_user_id' => $user->id,
            'auth.2fa_remember' => false,
        ])->post(route('two-factor.verify'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('catalogo.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_admin_without_confirmed_two_factor_is_redirected_to_challenge_on_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'email' => 'admin@atlantia.test',
            'password' => 'Atlantia2026!',
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);
        $admin->assignRole('admin');

        $response = $this->post(route('login.store'), [
            'email' => 'admin@atlantia.test',
            'password' => 'Atlantia2026!',
        ]);

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->assertSame($admin->id, session('auth.2fa_user_id'));
    }
}
