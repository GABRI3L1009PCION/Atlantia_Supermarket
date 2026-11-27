<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\VerificarWebhookHmac;
use App\Services\Pagos\VerificadorHmacService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class WebhookReliabilityTest extends TestCase
{
    public function test_failed_response_can_be_retried_but_success_cannot_be_replayed(): void
    {
        $request = $this->signedRequest();
        $middleware = app(VerificarWebhookHmac::class);
        $this->assertSame(500, $middleware->handle($request, fn () => response('', 500))->getStatusCode());
        $this->assertSame(200, $middleware->handle($request, fn () => response('', 200))->getStatusCode());
        $this->assertSame(409, $middleware->handle($request, fn () => response('', 200))->getStatusCode());
    }

    public function test_exception_releases_the_processing_lock(): void
    {
        $request = $this->signedRequest();
        $middleware = app(VerificarWebhookHmac::class);
        try {
            $middleware->handle($request, fn () => throw new RuntimeException('Temporary failure'));
            $this->fail('The controller exception should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Temporary failure', $exception->getMessage());
        }
        $this->assertSame(200, $middleware->handle($request, fn () => response('', 200))->getStatusCode());
    }

    public function test_concurrent_delivery_cannot_enter_the_controller(): void
    {
        $request = $this->signedRequest();
        $key = 'webhook-replay:'.sha1($request->path().':'.$request->header('X-Atlantia-Event-Id'));
        $lock = Cache::lock($key.':processing', 600);
        $this->assertTrue($lock->get());
        try {
            $response = app(VerificarWebhookHmac::class)->handle($request, function () {
                $this->fail('Concurrent delivery entered the controller.');
            });
            $this->assertSame(409, $response->getStatusCode());
        } finally {
            $lock->release();
        }
    }

    private function signedRequest(): Request
    {
        $secret = 'security-regression-test-secret';
        config(['services.payment_gateway.webhook_secret' => $secret]);
        $body = '{"status":"succeeded"}';
        $timestamp = (string) now()->timestamp;

        return Request::create('/webhooks/pasarela-pago', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ATLANTIA_SIGNATURE' => app(VerificadorHmacService::class)->sign($body, $secret, $timestamp),
            'HTTP_X_ATLANTIA_TIMESTAMP' => $timestamp,
            'HTTP_X_ATLANTIA_EVENT_ID' => (string) Str::uuid(),
        ], $body);
    }
}
