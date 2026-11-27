<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\StoreSolicitudVendedorRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use App\Notifications\NuevaSolicitudVendedorNotification;
use App\Services\Monetizacion\PlanesVendedorService;
use App\Services\Vendedores\AccesoVendedorService;
use App\Services\Vendedores\AutoAprobacionService;
use App\Support\CategoriasNegocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class SolicitudVendedorController extends Controller
{
    /**
     * Muestra el formulario publico de solicitud.
     */
    public function create(): View
    {
        return view('vendedor.solicitar.create', [
            'municipios' => (new StoreSolicitudVendedorRequest)->municipios(),
            'nombresMunicipio' => ['Santo Tomas' => 'Santo Tomás de Castilla'],
            'coloniasSugeridas' => collect((array) config('atlantia.cobertura_colonias', []))
                ->flatMap(fn ($listas) => (array) ($listas['cubiertas'] ?? []))->unique()->sort()->values()->all(),
            'departamentos' => [
                'Alta Verapaz',
                'Baja Verapaz',
                'Chimaltenango',
                'Chiquimula',
                'El Progreso',
                'Escuintla',
                'Guatemala',
                'Huehuetenango',
                'Izabal',
                'Jalapa',
                'Jutiapa',
                'Peten',
                'Quetzaltenango',
                'Quiche',
                'Retalhuleu',
                'Sacatepequez',
                'San Marcos',
                'Santa Rosa',
                'Solola',
                'Suchitepequez',
                'Totonicapan',
                'Zacapa',
            ],
            'categories' => [...CategoriasNegocio::OPCIONES, 'otro' => 'Otro'],
            'banks' => ['Banco Azteca', 'Banrural', 'Banco Industrial', 'BAM', 'BAC', 'G&T Continental', 'Banco Promerica', 'Otros'],
            'plans' => $this->sellerPlans(),
        ]);
    }

    /**
     * Guarda una solicitud publica de vendedor.
     */
    public function store(StoreSolicitudVendedorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $trackingCode = $this->trackingCode();

        $vendor = DB::transaction(function () use ($request, $data, $trackingCode): Vendor {
            $user = User::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => null,
                'password' => Hash::make(Str::password(16)),
                'phone' => $data['phone'],
                'status' => 'inactive',
                'is_system_user' => false,
                'two_factor_enabled' => false,
            ]);

            if (Role::query()->where('name', 'vendedor')->exists()) {
                $user->assignRole('vendedor');
            }

            $documents = $this->storeDocuments($request, $trackingCode);
            $businessAddress = $this->businessAddress($data);
            $personalAddress = [
                'calle' => $data['address_street'],
                'colonia' => $data['address_colonia'] ?? null,
                'numero' => $data['address_number'] ?? null,
                'apto' => $data['address_suite'] ?? null,
                'municipio' => $data['address_municipio'],
                'departamento' => $data['address_departamento'] ?? 'Izabal',
                'codigo_postal' => $data['address_zip'] ?? null,
            ];

            $vendor = Vendor::query()->create([
                'uuid' => (string) Str::uuid(),
                'application_code' => $trackingCode,
                'user_id' => $user->id,
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'],
                'birthdate' => $data['birthdate'],
                'gender' => $data['gender'] ?? null,
                'personal_address' => $personalAddress,
                'business_name' => $data['business_name'],
                'slug' => Str::slug($data['business_name']).'-'.Str::lower(Str::random(6)),
                'descripcion' => $data['business_description'],
                'business_category' => $data['business_category'] === 'otro'
                    ? ($data['business_category_other'] ?? 'Otro')
                    : $data['business_category'],
                'categorias_negocio' => $data['business_category'] === 'otro' ? null : [$data['business_category']],
                'has_nit' => (bool) $data['has_nit'],
                'logo_path' => $documents['business_logo'] ?? null,
                'cover_path' => null,
                'telefono_publico' => $data['phone'],
                'email_publico' => $data['email'],
                'municipio' => $businessAddress['municipio'] ?? $personalAddress['municipio'],
                'direccion_comercial' => $this->formatAddress($businessAddress ?: $personalAddress),
                'business_address' => $businessAddress,
                'banking_info' => [
                    'banco' => $data['bank'],
                    'tipo_cuenta' => $data['account_type'],
                    'numero_cuenta' => $data['account_number'],
                    'titular' => $data['account_holder'],
                ],
                'payment_frequency' => $data['payment_frequency'],
                'preferred_payment_method' => $data['preferred_payment_method'],
                'documents' => $documents,
                'is_approved' => false,
                'status' => 'pending',
                /* Elige su plan, pero opera con el plan Inicial hasta pagar la primera mensualidad desde su panel. */
                'plan' => $data['seller_plan'],
                'commission_percentage' => PlanesVendedorService::datos(PlanesVendedorService::base())['comision'],
                'monthly_rent' => $this->sellerPlans()[$data['seller_plan']]['price'],
                'accepts_cash' => true,
                'accepts_transfer' => true,
                'accepts_card' => true,
                'terms_accepted_at' => now(),
                'truth_accepted_at' => now(),
                'data_consent_at' => now(),
            ]);

            if ((bool) $data['has_nit']) {
                VendorFiscalProfile::query()->create([
                    'vendor_id' => $vendor->id,
                    'nit' => $data['nit_number'],
                    'razon_social' => $data['razon_social'],
                    'nombre_comercial_sat' => $data['business_name'],
                    'direccion_fiscal' => $this->formatAddress($businessAddress ?: $personalAddress),
                    'regimen_sat' => $this->mapRegimen($data['regimen_sat']),
                    'codigo_establecimiento' => 'PEND-'.$vendor->id,
                    'certificador_fel' => 'infile',
                    'banco_nombre' => $data['bank'],
                    'cuenta_bancaria' => $data['account_number'],
                    'cuenta_bancaria_tipo' => $data['account_type'],
                    'cuenta_bancaria_titular' => $data['account_holder'],
                    'fel_activo' => false,
                ]);
            } else {
                /* Sin NIT: solo se guarda la cuenta donde recibirá sus pagos. */
                VendorFiscalProfile::query()->create([
                    'vendor_id' => $vendor->id,
                    'certificador_fel' => 'infile',
                    'banco_nombre' => $data['bank'],
                    'cuenta_bancaria' => $data['account_number'],
                    'cuenta_bancaria_tipo' => $data['account_type'] === 'corriente' ? 'monetaria' : 'ahorro',
                    'cuenta_bancaria_titular' => $data['account_holder'],
                    'fel_activo' => false,
                ]);
            }

            return $vendor;
        });

        /* Si cumple los requisitos y no hay señales de riesgo, se aprueba sola y recibe su acceso por correo. */
        $aprobada = false;
        try {
            $aprobada = app(AutoAprobacionService::class)->procesar($vendor);
        } catch (Throwable $exception) {
            report($exception);
        }

        $this->avisarAdministradores($vendor->fresh());

        return redirect()
            ->route('vendedor.solicitud.show', $vendor->application_code)
            ->with('status', $aprobada
                ? '¡Tu tienda fue aprobada! Te enviamos a tu correo el enlace para crear tu contraseña y entrar a tu panel.'
                : 'Solicitud enviada correctamente. La revisaremos y te avisaremos por correo.');
    }

    /**
     * Muestra el seguimiento publico de la solicitud.
     */
    public function show(string $codigo): View
    {
        $vendor = Vendor::query()
            ->with('user')
            ->where('application_code', $codigo)
            ->firstOrFail();

        return view('vendedor.solicitar.show', [
            'vendor' => $vendor,
            'correoVisible' => AccesoVendedorService::correoEnmascarado($vendor->user?->email),
            'yaTieneContrasena' => $vendor->user?->email_verified_at !== null,
        ]);
    }

    /**
     * Reenvía el enlace para crear contraseña. Solo va al correo registrado,
     * así que conocer el código de seguimiento no permite tomar la cuenta.
     */
    public function reenviarAcceso(string $codigo, AccesoVendedorService $acceso): RedirectResponse
    {
        $vendor = Vendor::query()->with('user')->where('application_code', $codigo)->firstOrFail();

        if ($vendor->status !== 'approved' || $vendor->user === null) {
            return back()->with('error', 'Tu solicitud todavía no está aprobada.');
        }

        $enviado = $acceso->enviarBienvenida($vendor);

        return back()->with(
            $enviado ? 'status' : 'error',
            $enviado
                ? 'Te enviamos un enlace nuevo a '.AccesoVendedorService::correoEnmascarado($vendor->user->email).'. Revisa también la carpeta de spam.'
                : 'No pudimos enviar el correo en este momento. Inténtalo de nuevo en unos minutos.'
        );
    }

    /**
     * Guarda documentos de la solicitud.
     *
     * @return array<string, string>
     */
    /**
     * Avisa a los administradores que hay una solicitud nueva para revisar.
     * Un fallo al notificar nunca debe impedir que la solicitud quede registrada.
     */
    private function avisarAdministradores(Vendor $vendor): void
    {
        try {
            $roles = Role::query()->whereIn('name', ['super_admin', 'admin'])->pluck('name')->all();

            if ($roles === []) {
                return;
            }

            $administradores = User::query()->role($roles)->get();
            Notification::send($administradores, new NuevaSolicitudVendedorNotification($vendor));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function storeDocuments(Request $request, string $trackingCode): array
    {
        $path = 'vendor-applications/'.$trackingCode;
        $privateDisk = (string) config('filesystems.private_disk', 'local');
        $documents = [
            'document_front' => $request->file('document_front')?->store($path, $privateDisk),
            'document_back' => $request->file('document_back')?->store($path, $privateDisk),
            'business_logo' => $request->file('business_logo')?->store($path, 'public'),
            'bank_proof' => $request->file('bank_proof')?->store($path, $privateDisk),
        ];

        if ($request->hasFile('nit_file')) {
            $documents['nit_file'] = $request->file('nit_file')?->store($path, $privateDisk);
        }

        return array_filter($documents);
    }

    /**
     * Genera un codigo publico de seguimiento.
     */
    private function trackingCode(): string
    {
        do {
            $code = 'VND-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (Vendor::query()->where('application_code', $code)->exists());

        return $code;
    }

    /**
     * Normaliza direccion comercial opcional.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function businessAddress(array $data): array
    {
        if (empty($data['business_street']) && empty($data['business_number']) && empty($data['business_municipio'])) {
            return [];
        }

        return [
            'calle' => $data['business_street'] ?? null,
            'numero' => $data['business_number'] ?? null,
            'municipio' => $data['business_municipio'] ?? null,
        ];
    }

    /**
     * Convierte una direccion a texto compacto.
     *
     * @param  array<string, mixed>  $address
     */
    private function formatAddress(array $address): string
    {
        return collect([
            $address['calle'] ?? null,
            $address['numero'] ?? null,
            $address['apto'] ?? null,
            $address['colonia'] ?? null,
            $address['municipio'] ?? null,
            $address['departamento'] ?? null,
            $address['codigo_postal'] ?? null,
        ])->filter()->implode(', ');
    }

    /**
     * Mapea opciones publicas al enum fiscal interno.
     */
    private function mapRegimen(?string $regimen): string
    {
        return match ($regimen) {
            'simplificado' => 'pequeno_contribuyente',
            'ordinario' => 'general',
            default => 'general',
        };
    }

    /**
     * Planes mensuales (los mismos de la pagina "Vende en Atlantia" y del panel). El Personalizado se cotiza aparte.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sellerPlans(): array
    {
        return collect(PlanesVendedorService::contratables())
            ->map(fn (array $plan): array => [
                'name' => $plan['nombre'],
                'price' => (float) $plan['precio'],
                'commission' => $plan['comision'],
                'products' => 'Hasta '.number_format((int) $plan['max_productos']).' productos',
                'users' => $plan['tipo'],
                'payout' => $plan['liquidacion'],
                'support' => $plan['soporte'],
                'analytics' => $plan['carga_masiva'] ? 'Carga masiva con Excel' : 'Carga de productos uno por uno',
                'description' => $plan['resumen'],
            ])
            ->all();
    }
}
