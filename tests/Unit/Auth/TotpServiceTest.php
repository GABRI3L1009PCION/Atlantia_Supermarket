<?php

namespace Tests\Unit\Auth;

use App\Services\Auth\TotpService;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    public function test_it_matches_known_rfc_totp_vector(): void
    {
        $service = app(TotpService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $service->codeAt($secret, 59));
    }

    public function test_it_verifies_generated_codes(): void
    {
        $service = app(TotpService::class);
        $secret = $service->generateSecret();
        $code = $service->codeAt($secret, time());

        $this->assertTrue($service->verify($secret, $code));
    }
}
