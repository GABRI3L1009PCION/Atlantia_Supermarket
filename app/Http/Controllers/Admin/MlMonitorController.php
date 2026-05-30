<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ml\MonitorDriftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Throwable;

/**
 * Controlador de monitoreo ML.
 */
class MlMonitorController extends Controller
{
    /**
     * Crea una instancia del controlador.
     */
    public function __construct(private readonly MonitorDriftService $monitorDriftService)
    {
    }

    /**
     * Muestra estado de modelos y metricas.
     */
    public function index(Request $request): View
    {
        $this->authorize('monitorMl', $request->user());

        return view('admin.ml.monitor', [
            'monitor' => $this->monitorDriftService->dashboard($request->all()),
            'filters' => $request->only(['drift_threshold']),
        ]);
    }

    /**
     * Activa la automatizacion avanzada de ML.
     */
    public function activate(Request $request): RedirectResponse
    {
        $this->authorize('monitorMl', $request->user());

        try {
            $this->writeEnvironmentValue('ATLANTIA_FEATURE_ADVANCED_ML_AUTOMATION', 'true');
            config(['atlantia.features.advanced_ml_automation' => true]);

            Artisan::call('config:clear');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo activar ML. Revisa permisos del archivo .env.');
        }

        return back()->with('success', 'Machine learning activado correctamente.');
    }

    /**
     * Actualiza una llave del archivo .env preservando el resto del contenido.
     */
    private function writeEnvironmentValue(string $key, string $value): void
    {
        $path = base_path('.env');
        $line = $key . '=' . $value;

        if (! is_file($path) || ! is_writable($path)) {
            throw new \RuntimeException('El archivo .env no esta disponible para escritura.');
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException('No se pudo leer el archivo .env.');
        }

        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $updatedContent = preg_match($pattern, $content) === 1
            ? (string) preg_replace($pattern, $line, $content)
            : rtrim($content) . PHP_EOL . $line . PHP_EOL;

        if (file_put_contents($path, $updatedContent) === false) {
            throw new \RuntimeException('No se pudo escribir el archivo .env.');
        }
    }
}
