<?php

namespace App\Services\Empleados;

use App\Models\ContactMessage;
use App\Models\Ml\ReviewFlag;
use App\Models\Payment;
use App\Models\Resena;
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
            'overview' => [
                'transferencias_pendientes' => Payment::query()->where('metodo', 'transferencia')->pending()->count(),
                'mensajes_pendientes' => ContactMessage::query()->where('atendido', false)->count(),
                'resenas_flaggeadas' => ReviewFlag::query()->where('revisada', false)->count(),
                'resenas_pendientes' => Resena::query()->where('aprobada', false)->count(),
            ],
            'transferencias_recientes' => Payment::query()
                ->with('pedido.cliente')
                ->where('metodo', 'transferencia')
                ->latest()
                ->limit(6)
                ->get(),
            'mensajes_recientes' => ContactMessage::query()
                ->where('atendido', false)
                ->latest()
                ->limit(5)
                ->get(),
            'quick_links' => [
                ['title' => 'Transferencias', 'description' => 'Valida pagos bancarios pendientes.', 'route' => route('empleado.transferencias.index')],
                ['title' => 'Mensajes', 'description' => 'Atiende solicitudes de clientes.', 'route' => route('empleado.mensajes.index')],
                ['title' => 'Resenas', 'description' => 'Modera opiniones y flags ML.', 'route' => route('empleado.resenas.index')],
            ],
        ];
    }
}
