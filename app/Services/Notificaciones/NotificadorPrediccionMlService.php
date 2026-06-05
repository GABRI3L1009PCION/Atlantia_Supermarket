<?php

namespace App\Services\Notificaciones;

use App\Contracts\NotificacionContract;
use App\Models\Ml\SalesPrediction;
use App\Models\SentEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class NotificadorPrediccionMlService
{
    public function __construct(private readonly NotificacionContract $notificationService)
    {
    }

    public function demandaMayorAlStock(SalesPrediction $prediction): void
    {
        $prediction->loadMissing(['producto.inventario', 'vendor.user']);
        $producto = $prediction->producto;
        $user = $prediction->vendor?->user;
        $stock = (int) ($producto?->inventario?->stock_actual ?? 0);
        $predicho = (float) $prediction->valor_predicho;

        if ($producto === null || $user === null || $stock <= 0 || $predicho < $stock) {
            return;
        }

        if ($this->wasRecentlySent($user->id, (string) $producto->uuid)) {
            return;
        }

        $data = [
            'titulo' => 'Demanda ML supera tu stock',
            'mensaje' => "La demanda estimada de {$producto->nombre} supera el stock disponible.",
            'producto_uuid' => $producto->uuid,
            'stock_actual' => $stock,
            'valor_predicho' => $predicho,
            'horizonte_dias' => $prediction->horizonte_dias,
        ];

        $this->notificationService->enviar($user, 'ml.prediccion_stock_riesgo', $data);

        $email = SentEmail::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'to' => $user->email,
            'subject' => 'Atlantia ML: demanda mayor que tu stock',
            'template' => 'ml.prediccion_stock_riesgo',
            'status' => 'queued',
            'metadata' => $data,
        ]);

        try {
            Mail::raw($this->emailBody($prediction, $stock), function ($message) use ($user): void {
                $message
                    ->to($user->email, $user->name)
                    ->subject('Atlantia ML: demanda mayor que tu stock');
            });

            $email->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $email->update(['status' => 'failed', 'error' => $exception->getMessage()]);
        }
    }

    private function wasRecentlySent(int $userId, string $productoUuid): bool
    {
        return SentEmail::query()
            ->where('user_id', $userId)
            ->where('template', 'ml.prediccion_stock_riesgo')
            ->where('metadata', 'like', '%' . $productoUuid . '%')
            ->where('created_at', '>=', now()->subHours(12))
            ->exists();
    }

    private function emailBody(SalesPrediction $prediction, int $stock): string
    {
        return implode("\n", [
            'Atlantia Supermarket - Alerta ML de demanda',
            '',
            'Producto: ' . $prediction->producto->nombre,
            "Horizonte: {$prediction->horizonte_dias} dias",
            'Demanda estimada: ' . number_format((float) $prediction->valor_predicho, 0) . ' unidades',
            "Stock actual: {$stock} unidades",
            'Rango estimado: ' . number_format((float) ($prediction->intervalo_inferior ?? 0), 0) . ' - ' . number_format((float) ($prediction->intervalo_superior ?? 0), 0) . ' unidades',
            '',
            'Recomendacion: revisa Prediccion de demanda y Reabasto ML para decidir si debes reponer inventario.',
            route('vendedor.predicciones.index'),
        ]);
    }
}
