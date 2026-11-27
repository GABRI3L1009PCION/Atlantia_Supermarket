<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\PrivateNetworkAccess;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductionTransportTest extends TestCase
{
    public function test_untrusted_forwarded_proto_cannot_bypass_https(): void
    {
        app()->instance('env', 'production');
        config(['app.url' => 'https://example.com']);
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_PROTO);
        $request = Request::create('http://example.com/login', 'GET', [], [], [], ['HTTP_X_FORWARDED_PROTO' => 'https']);
        app('url')->setRequest($request);
        $response = app(ForceHttps::class)->handle($request, fn () => response('insecure'));
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://example.com/login', $response->headers->get('Location'));
    }

    public function test_production_does_not_reflect_arbitrary_origins_or_allow_private_network_access(): void
    {
        app()->instance('env', 'production');
        config(['app.url' => 'https://localhost']);
        config(['cors.allowed_origins' => ['https://store.example.com']]);
        $response = $this->withHeaders(['Origin' => 'https://untrusted.example.com'])
            ->getJson('https://localhost/api/repartidor/ping')
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Private-Network');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertNotSame('https://untrusted.example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_production_preflight_uses_configured_origin_allowlist(): void
    {
        app()->instance('env', 'production');
        config(['app.url' => 'https://localhost']);
        config(['cors.allowed_origins' => ['https://store.example.com']]);
        $this->withHeaders([
            'Origin' => 'https://store.example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('https://localhost/api/repartidor/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://store.example.com')
            ->assertHeaderMissing('Access-Control-Allow-Private-Network');
    }

    public function test_local_development_retains_private_network_preflight(): void
    {
        app()->instance('env', 'local');
        $request = Request::create('/api/repartidor/login', 'OPTIONS', [], [], [], ['HTTP_ORIGIN' => 'http://localhost']);
        $response = app(PrivateNetworkAccess::class)->handle($request, fn () => response('unexpected', 500));
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Private-Network'));
    }

    public function test_production_rejects_an_untrusted_host(): void
    {
        app()->instance('env', 'production');
        config(['app.url' => 'https://store.example.com']);
        $this->get('https://attacker.example.com/login')->assertStatus(400);
    }
}
