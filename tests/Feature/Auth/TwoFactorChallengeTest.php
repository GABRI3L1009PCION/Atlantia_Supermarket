<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function testChallengeShowsProvisioningKeyForPendingSetup(): void
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

    public function testPendingSetupCanBeConfirmedWithRealTotpCode(): void
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
}
