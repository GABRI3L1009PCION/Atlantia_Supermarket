<?php

namespace Tests\Unit\Security;

use App\Services\Seguridad\ProductionSecurityGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProductionSecurityGuardTest extends TestCase
{
    public static function unsafeSettings(): array
    {
        return [
            ['session.encrypt', false],
            ['session.secure', false],
            ['session.http_only', false],
            ['app.debug', true],
            ['app.url', 'http://store.example.com'],
            ['cors.allowed_origins', ['*']],
            ['cors.allowed_origins', ['http://store.example.com']],
            ['cors.allowed_origins', []],
            ['cors.allowed_origins_patterns', ['.*']],
        ];
    }

    #[DataProvider('unsafeSettings')]
    public function test_unsafe_production_configuration_fails_closed(string $key, mixed $value): void
    {
        $this->safeConfiguration();
        config([$key => $value]);
        $this->expectException(RuntimeException::class);
        app(ProductionSecurityGuard::class)->enforce();
    }

    public function test_safe_configuration_is_accepted(): void
    {
        $this->safeConfiguration();
        app(ProductionSecurityGuard::class)->enforce();
        $this->assertTrue(true);
    }

    private function safeConfiguration(): void
    {
        app()->instance('env', 'production');
        config([
            'app.url' => 'https://store.example.com', 'app.debug' => false,
            'session.encrypt' => true, 'session.secure' => true, 'session.http_only' => true,
            'cors.allowed_origins' => ['https://store.example.com', 'https://localhost', 'capacitor://localhost'],
            'cors.allowed_origins_patterns' => [],
        ]);
    }
}
