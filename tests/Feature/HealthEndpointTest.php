<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_uses_the_configured_ml_api_base_path(): void
    {
        config([
            'scout.meilisearch.host' => 'http://search.test',
            'services.ml.base_url' => 'http://ml.test/api/v1',
            'services.ml.service_token' => 'test-token',
        ]);

        Redis::shouldReceive('connection->ping')->once()->andReturn('PONG');
        Http::fake([
            'http://search.test/health' => Http::response(['status' => 'available']),
            'http://ml.test/api/v1/health' => Http::response(['status' => 'ok']),
        ]);

        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('ml_service', 'ok');

        Http::assertSent(fn ($request): bool => $request->url() === 'http://ml.test/api/v1/health');
    }
}
