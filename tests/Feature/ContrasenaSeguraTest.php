<?php

namespace Tests\Feature;

use App\Rules\ContrasenaSegura;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContrasenaSeguraTest extends TestCase
{
    private function error(string $clave): ?string
    {
        $validador = Validator::make(['password' => $clave], ['password' => [new ContrasenaSegura]]);

        return $validador->fails() ? $validador->errors()->first('password') : null;
    }

    public function test_accepts_a_strong_eight_character_password(): void
    {
        $this->assertNull($this->error('Rio#Verde84'));
        $this->assertNull($this->error('Kq9!mZt2'));
    }

    public function test_requires_length_and_each_character_type(): void
    {
        $this->assertStringContainsString('al menos 8', (string) $this->error('Ab1!xyz'));
        $this->assertStringContainsString('una mayúscula', (string) $this->error('rio#verde84'));
        $this->assertStringContainsString('una minúscula', (string) $this->error('RIO#VERDE84'));
        $this->assertStringContainsString('un número', (string) $this->error('Rio#Verdeok'));
        $this->assertStringContainsString('un símbolo', (string) $this->error('RioVerde84'));
    }

    public function test_simple_rules_only_no_confusing_extra_checks(): void
    {
        /* Cumple las 4 reglas: se acepta aunque use palabras o números comunes. */
        $this->assertNull($this->error('Atlantia2026!'));
        $this->assertNull($this->error('Tienda#1234'));
    }

    public function test_rejects_passwords_found_in_data_leaks(): void
    {
        config(['atlantia.auth.verificar_contrasenas_filtradas' => true]);
        $hash = strtoupper(sha1('Rio#Verde84'));

        Http::fake([
            'api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).":42\r\nAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA:1"),
        ]);

        $this->assertStringContainsString('robada', (string) $this->error('Rio#Verde84'));

        /* Solo se envía el prefijo de 5 caracteres del hash, nunca la contraseña. */
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/range/'.substr($hash, 0, 5)));
    }

    public function test_does_not_block_users_if_the_leak_service_is_down(): void
    {
        config(['atlantia.auth.verificar_contrasenas_filtradas' => true]);
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 503)]);

        $this->assertNull($this->error('Rio#Verde84'));
    }
}
