<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La CSP de produccion debe dejar funcionar Alpine/Livewire y las vistas no pueden usar
 * manejadores en linea (onclick, onsubmit...) ni <script> sin nonce, porque la CSP los bloquea.
 */
class CspProduccionTest extends TestCase
{
    public function test_production_policy_allows_alpine_but_not_inline_scripts(): void
    {
        $this->app['env'] = 'production';

        $respuesta = (new SecurityHeaders)->handle(
            Request::create('https://atlantia.test/'),
            fn () => new Response('ok'),
        );
        $csp = (string) $respuesta->headers->get('Content-Security-Policy');
        preg_match('/script-src ([^;]+)/', $csp, $script);

        $this->assertNotEmpty($script, 'La respuesta debe traer script-src.');
        $this->assertStringContainsString("'unsafe-eval'", $script[1]);
        $this->assertStringContainsString("'nonce-", $script[1]);
        $this->assertStringNotContainsString("'unsafe-inline'", $script[1]);
    }

    public function test_views_do_not_use_inline_event_handlers_or_scripts_without_nonce(): void
    {
        $problemas = [];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            $contenido = $archivo->getContents();
            $ruta = $archivo->getRelativePathname();

            if (preg_match_all('/\son(click|submit|change|input|load|error|keydown|keyup|focus|blur|mouseover)="/i', $contenido, $m)) {
                $problemas[] = "{$ruta}: manejador en línea on{$m[1][0]}=";
            }

            // <script> sin nonce (los bloques @script de Livewire los evalúa Livewire).
            $sinScriptLivewire = preg_replace('/@script\s*<script>.*?<\/script>\s*@endscript/s', '', $contenido);
            if (preg_match('/<script>/', (string) $sinScriptLivewire)) {
                $problemas[] = "{$ruta}: <script> sin nonce";
            }
        }

        $this->assertSame([], $problemas, "Usa data-confirm / data-autosubmit / data-print-page y <script @nonce>:\n".implode("\n", $problemas));
    }
}
