<?php

namespace Tests\Feature\Repartidor;

use App\Models\CourierDevice;
use App\Models\User;
use App\Services\Notificaciones\NotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_notificacion_a_repartidor_envia_push_fcm_a_dispositivo_registrado(): void
    {
        config()->set('services.firebase.enabled', true);
        config()->set('services.firebase.project_id', 'atlantia-firebase-test');
        config()->set('services.firebase.service_account_email', 'firebase-adminsdk@test.iam.gserviceaccount.com');
        config()->set('services.firebase.private_key', $this->test_private_key());

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'firebase-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://fcm.googleapis.com/v1/projects/atlantia-firebase-test/messages:send' => Http::response([
                'name' => 'projects/atlantia/messages/demo',
            ]),
        ]);

        $repartidor = User::factory()->repartidor()->create();
        $repartidor->assignRole('repartidor');

        CourierDevice::query()->create([
            'uuid' => (string) fake()->uuid(),
            'user_id' => $repartidor->id,
            'platform' => 'android',
            'device_uuid' => 'pixel-9-test',
            'device_name' => 'Pixel 9',
            'app_version' => '1.0.0',
            'push_provider' => 'fcm',
            'push_token' => 'fcm-token-123',
            'notifications_enabled' => true,
            'last_seen_at' => now(),
        ]);

        app(NotificationService::class)->enviar($repartidor, 'entrega.oferta', [
            'titulo' => 'Nueva oferta de entrega',
            'mensaje' => 'Tienes una entrega disponible de Atlantia Market.',
            'offer_uuid' => 'offer-demo-001',
            'pedido_uuid' => 'pedido-demo-001',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://oauth2.googleapis.com/token'
                && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer';
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/atlantia-firebase-test/messages:send'
                && $request->hasHeader('Authorization', 'Bearer firebase-access-token')
                && data_get($request->data(), 'message.token') === 'fcm-token-123'
                && data_get($request->data(), 'message.data.title') === 'Nueva oferta de entrega'
                && data_get($request->data(), 'message.data.offer_uuid') === 'offer-demo-001';
        });

        $this->assertDatabaseHas('notifications', [
            'type' => 'entrega.oferta',
            'notifiable_id' => $repartidor->id,
        ]);
    }

    private function test_private_key(): string
    {
        return <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDFeRdkBRBLZEcD
pPs+3t0LIG7mMynQ6gS3BTdr3PTOd+38BywP5Oczc3eNLbBi+QQ+1LhP0HUHFQmk
3k3Q0cEJdjMUDWE1I4l4QqX2TpXrmpWStB+X0xNOtjMzjHLjZKqhuoJO1pxt4nNi
v3A+AN6+xW9K2W0KWeI8KNe0sQDiZ0seU/u5Kn3N26hX9TQ4jMgEi5CknA9DE9kc
Q4q8gXHJGqaQeXjQjmgNVfY5X3UVcnhXsoI+dbyIcP2k0dZh49Myk7D13BQKAtJL
L9wu1A7MFS1C24OtqXyIMl10XNd09RjhHyX7PDU7M7rjwNj7P+Ino58I2Q6AfYwX
cmK/FnP5AgMBAAECggEABdHfJpJz3nK6J9nvVwPryEBP+Ry+yf89ZfXAXkknHJaQ
Okt7z8B1o7i7Wt10Ur9vOnPDKSv6gYZlWB+T4cgqXucg5SUUt1tm7M0t9LygfR8f
YWsbL06a+sKha+5N1MvgY9QTV6ECIioM6nA5mo1SiJRJk9AOp7isD9W+g59w35t5
1jTw3cyzzpn1wX7xTL+qn6Z5aY2H7sl5yP/4uCkLnX6AF6XQCY1hHfFJozu1a+2z
0SmZQ5oy4vEr6mWcD6jrN9G5rMyEIJ0t2eQ24YIpNtw6HLL2D9ip0S9V4gN8y7/X
9CZb4Zb8r3OlT6u4l6rfUMpnbfY8JXvM0IkAn5dU0QKBgQDrK+48wIIN9dbpKqN2
KYr3b8Ijphg6eQj8mJ9w+8Sxv8lAQwC36dI8Qqo8DLBhbWvK6fy3EA8rnhc9aO6Y
nn0mZErLiM9lU2m5qpAwwjlwmrB1VJ5Ohe4DnRZnWN4Y+8ou9n3DXS0zGiA6lQ5S
6C5o82x+coK3ObeCW0lTBHviXQKBgQDZ0ZP8P0/ruXxGv0Q7lWw3atwYwPGZqGlj
V7f3P5VqR8bxOHe8vLrjAKI8dTwRbD1ceW13wpwZB5YdGacxX31dlj8eDcxCxRrw
cZStnEuWl5z+qjL98kts8yxZNCWswMbjv1xC7h3kBXTFnRkR1tA9If4VUu8Paxdy
ckBcvvRafQKBgQC/ENsZK1PwM3Dc9zVc0pwi3QbrlW49l3LZwMO1Vm3D0xyaTqG8
p7bn0xXgV+8zQr6orwvxS7N3ZKU17lmhh5L+nsYkzj6Q9sl8p24WehIOvx7dy0rx
e5YGm9dD0yxL10qwoD0Qd6if6s8x7h4dR9tlL0Ws4K7QgM4CBuk3FJwQFQKBgGKg
6T6n3jvQq2xJ+dJw5I3au0MZRc4mO8vczl29duN6ou5q7uyTGPY2dLQgu1T3d22e
l0m7+o3lkYz3r4E0AOt+sH0HVujfvZux0j5kiY7/OwTtXlcH6f7IUH2KZh/Nvfxu
f4kHfQJ4r9cG9eDABXzeLaY4H7eN7o7Jt6HuH+sxAoGAak5n1/aoF4BK6zULvwpp
lX/fb6tzSNQ2AfQhzLJ3R9CWW0BQo8OtCX7x8Li+CE1No4EWh1eWnYHod89+7xAK
BGfPFPQ1G5GdEcF0J6R8bObzjK6RhUWoSsoH2z7Nwlux3evPQvC49rZqsxG0cf0Q
NdWoY11CQ3tDg7On0hc3VnE=
-----END PRIVATE KEY-----
KEY;
    }
}
