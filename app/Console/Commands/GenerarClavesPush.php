<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Genera las claves VAPID de las notificaciones push y las guarda en el .env.
 * Se ejecuta una sola vez por servidor: si cambian, los clientes deben volver a aceptar avisos.
 */
class GenerarClavesPush extends Command
{
    protected $signature = 'push:claves {--force : Reemplaza las claves existentes}';

    protected $description = 'Genera las claves VAPID para las notificaciones push y las escribe en .env';

    public function handle(): int
    {
        $env = base_path('.env');

        if (! is_file($env)) {
            $this->error('No existe el archivo .env.');

            return self::FAILURE;
        }

        $contenido = (string) file_get_contents($env);

        if (preg_match('/^WEBPUSH_PUBLIC_KEY=.+$/m', $contenido) === 1 && ! $this->option('force')) {
            $this->info('Las claves ya existen. Usa --force solo si quieres reemplazarlas.');

            return self::SUCCESS;
        }

        $claves = VAPID::createVapidKeys();

        foreach (['WEBPUSH_PUBLIC_KEY' => $claves['publicKey'], 'WEBPUSH_PRIVATE_KEY' => $claves['privateKey']] as $nombre => $valor) {
            $contenido = preg_match("/^{$nombre}=.*$/m", $contenido) === 1
                ? (string) preg_replace("/^{$nombre}=.*$/m", "{$nombre}={$valor}", $contenido)
                : rtrim($contenido)."\n{$nombre}={$valor}\n";
        }

        file_put_contents($env, $contenido);
        $this->info('Claves de notificaciones push guardadas en .env.');

        return self::SUCCESS;
    }
}
