<?php

namespace App\Services\Empleados;

use App\Models\ContactMessage;
use App\Models\Ml\ReviewFlag;
use App\Models\Payment;
use App\Models\User;

/**
 * Servicio de metricas del panel empleado.
 */
class DashboardEmpleadoService
{
    /**
     * Devuelve metricas operativas para empleados.
     *
     * @return array<string, mixed>
     */
    public function metrics(User $user): array
    {
        return [
            'transferencias_pendientes' => Payment::query()->where('metodo', 'transferencia')->pending()->count(),
            'mensajes_pendientes' => ContactMessage::query()->where('atendido', false)->count(),
            'resenas_flaggeadas' => ReviewFlag::query()->where('revisada', false)->count(),
        ];
    }
}

