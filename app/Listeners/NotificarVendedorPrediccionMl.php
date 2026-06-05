<?php

namespace App\Listeners;

use App\Events\PrediccionGenerada;
use App\Services\Notificaciones\NotificadorPrediccionMlService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificarVendedorPrediccionMl implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PrediccionGenerada $event): void
    {
        app(NotificadorPrediccionMlService::class)->demandaMayorAlStock($event->prediction);
    }
}
