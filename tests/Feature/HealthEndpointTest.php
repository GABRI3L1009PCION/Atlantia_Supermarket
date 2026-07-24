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
            'services.firebase.enabled' => true,
            'services.firebase.project_id' => 'firebase-test-project',
            'services.firebase.service_account_email' => 'firebase@test.iam.gserviceaccount.com',
            'services.firebase.private_key' => "-----BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY-----\n",
            'services.mapbox.token' => 'pk.test.mapbox',
            'atlantia.support.phone' => '+50255550101',
            'atlantia.support.emergency_phone' => '+50255550191',
            'atlantia.support.channels' => ['app', 'phone'],
            'atlantia.payments.pos.provider' => 'POS bancario',
            'atlantia.payments.transfer.bank_name' => 'Banco Industrial',
            'atlantia.payments.transfer.account_number' => '0000-000000-000',
        ]);

        Redis::shouldReceive('connection->ping')->once()->andReturn('PONG');
        Http::fake([
            'http://search.test/health' => Http::response(['status' => 'available']),
            'http://ml.test/api/v1/health' => Http::response(['status' => 'ok']),
        ]);

        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('ml_service', 'ok')
            ->assertJsonPath('firebase_push', 'ok')
            ->assertJsonPath('maps_config', 'ok')
            ->assertJsonPath('support_center', 'ok')
            ->assertJsonPath('onsite_payments', 'ok');

        Http::assertSent(fn ($request): bool => $request->url() === 'http://ml.test/api/v1/health');
    }
}
