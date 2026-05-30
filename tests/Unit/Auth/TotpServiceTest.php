<?php

namespace Tests\Unit\Auth;

use App\Services\Auth\TotpService;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    public function testItMatchesKnownRfcTotpVector(): void
    {
        $service = app(TotpService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $service->codeAt($secret, 59));
    }

    public function testItVerifiesGeneratedCodes(): void
    {
        $service = app(TotpService::class);
        $secret = $service->generateSecret();
        $code = $service->codeAt($secret, time());

        $this->assertTrue($service->verify($secret, $code));
    }
}
