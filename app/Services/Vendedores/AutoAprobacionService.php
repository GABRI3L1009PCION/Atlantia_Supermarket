<?php

namespace App\Services\Vendedores;

use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use App\Notifications\PruebaPlanNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Monetizacion\PruebaAperturaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Aprobación automática de tiendas.
 *
 * El formulario ya exige los requisitos (datos del dueño y del negocio, DPI, dirección,
 * logo y cuenta bancaria con comprobante). Aquí se revisan señales de riesgo; si no hay
 * ninguna, la tienda se aprueba sola y recibe su acceso. Si hay alguna, queda pendiente
 * para que Atlantia la revise, con el motivo a la vista. Atlantia puede suspenderla o
 * darla de baja en cualquier momento si no cumple.
 */
class AutoAprobacionService
{
    public function __construct(
        private readonly AccesoVendedorService $acceso,
    ) {}

    public static function activa(): bool
    {
        return (bool) config('atlantia.vendedores.auto_aprobar', true);
    }

    /**
     * Motivos por los que la solicitud necesita revisión de una persona (vacío = se puede aprobar sola).
     *
     * @return array<int, string>
     */
    public function motivosDeRevision(Vendor $vendor): array
    {
        $vendor->loadMissing(['user', 'fiscalProfile']);
        $perfil = $vendor->fiscalProfile;
        $motivos = [];

        $cuenta = preg_replace('/\D+/', '', (string) $perfil?->cuenta_bancaria);
        if ($cuenta !== '' && VendorFiscalProfile::query()->where('vendor_id', '!=', $vendor->id)->get(['vendor_id', 'cuenta_bancaria'])
            ->contains(fn (VendorFiscalProfile $p): bool => preg_replace('/\D+/', '', (string) $p->cuenta_bancaria) === $cuenta)) {
            $motivos[] = 'La cuenta bancaria ya está registrada en otra tienda.';
        }

        if ($perfil?->cuenta_bancaria_titular && ! $this->titularCoincide((string) $perfil->cuenta_bancaria_titular, [
            (string) $vendor->user?->name, (string) $perfil->razon_social, (string) $vendor->business_name,
        ])) {
            $motivos[] = 'El titular de la cuenta bancaria no coincide con el dueño ni con el negocio.';
        }

        $telefono = preg_replace('/\D+/', '', (string) $vendor->telefono_publico);
        $sospechosas = Vendor::query()->where('id', '!=', $vendor->id)->whereIn('status', ['rejected', 'suspended'])
            ->where(function ($q) use ($vendor, $telefono): void {
                $q->where('document_number', $vendor->document_number);
                if ($telefono !== '') {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(telefono_publico, ' ', ''), '+', ''), '-', '') = ?", [$telefono]);
                }
            })->exists();
        if ($sospechosas) {
            $motivos[] = 'El documento o el teléfono pertenecen a una tienda rechazada o suspendida.';
        }

        if ($vendor->document_type === 'pasaporte') {
            $motivos[] = 'Se registró con pasaporte: hay que verificar la identidad a mano.';
        }

        return $motivos;
    }

    /**
     * Revisa la solicitud recién enviada y, si no hay señales de riesgo, la aprueba.
     */
    public function procesar(Vendor $vendor): bool
    {
        $motivos = self::activa() ? $this->motivosDeRevision($vendor) : ['La aprobación automática está apagada.'];
        $aprobar = $motivos === [];

        $vendor->forceFill(['revision_automatica' => [
            'aprobada' => $aprobar,
            'motivos' => $motivos,
            'revisada_at' => now()->toIso8601String(),
        ]])->save();

        if (! $aprobar) {
            return false;
        }

        $base = PlanesVendedorService::datos(PlanesVendedorService::base());
        $elegido = PlanesVendedorService::datos((string) ($vendor->plan ?: PlanesVendedorService::base()));

        DB::transaction(function () use ($vendor, $base, $elegido): void {
            $vendor->forceFill([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => null,
                'status' => 'approved',
                /* Hasta que pague su plan, vende con las condiciones del plan Inicial. */
                'commission_percentage' => $base['comision'],
                'monthly_rent' => (float) ($elegido['precio'] ?? 0),
            ])->save();
            $vendor->user?->forceFill(['status' => 'active'])->save();
        });

        /* Si la promoción de apertura está activa, arranca su prueba gratis con el plan elegido. */
        $apertura = app(PruebaAperturaService::class);
        if ($apertura->disponiblePara($vendor->fresh())) {
            $plan = array_key_exists((string) $vendor->plan, PlanesVendedorService::contratables()) ? (string) $vendor->plan : PlanesVendedorService::base();
            $vendor = $apertura->iniciar($vendor, $plan);
            try {
                $vendor->user?->notify(new PruebaPlanNotification($vendor, 'iniciada'));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $this->acceso->enviarBienvenida($vendor->fresh());

        return true;
    }

    /**
     * El titular coincide si comparte al menos dos palabras (o una, si el nombre es corto)
     * con el nombre del dueño, la razón social o el nombre del negocio.
     *
     * @param  array<int, string>  $referencias
     */
    private function titularCoincide(string $titular, array $referencias): bool
    {
        $palabras = fn (string $texto): array => array_values(array_filter(
            explode(' ', Str::of(Str::ascii(Str::lower($texto)))->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString()),
            fn (string $p): bool => strlen($p) > 2 && ! in_array($p, ['sociedad', 'anonima', 'los', 'las', 'del', 'por'], true),
        ));

        $deTitular = $palabras($titular);
        foreach ($referencias as $referencia) {
            $comunes = count(array_intersect($deTitular, $palabras($referencia)));
            if ($comunes >= min(2, max(1, count($deTitular)))) {
                return true;
            }
        }

        return false;
    }
}
