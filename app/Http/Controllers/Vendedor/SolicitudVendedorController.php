<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\StoreSolicitudVendedorRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SolicitudVendedorController extends Controller
{
    /**
     * Muestra el formulario publico de solicitud.
     */
    public function create(): View
    {
        return view('vendedor.solicitar.create', [
            'municipios' => ['Puerto Barrios', 'Santo Tomas', 'Morales', 'Los Amates', 'Livingston', 'El Estor'],
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
            'categories' => [
                'alimentos_frescos' => 'Alimentos frescos',
                'panaderia_reposteria' => 'Panaderia/Reposteria',
                'bebidas_licores' => 'Bebidas/Licores',
                'artesania' => 'Artesania',
                'ropa_accesorios' => 'Ropa/Accesorios',
                'cosmeticos' => 'Cosmeticos/Cuidado personal',
                'electronica' => 'Electronica',
                'servicios' => 'Servicios',
                'otro' => 'Otro',
            ],
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
                'numero' => $data['address_number'],
                'apto' => $data['address_suite'] ?? null,
                'municipio' => $data['address_municipio'],
                'departamento' => $data['address_departamento'],
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
                'slug' => Str::slug($data['business_name']) . '-' . Str::lower(Str::random(6)),
                'descripcion' => $data['business_description'],
                'business_category' => $data['business_category'] === 'otro'
                    ? ($data['business_category_other'] ?? 'Otro')
                    : $data['business_category'],
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
                'commission_percentage' => $this->sellerPlans()[$data['seller_plan']]['commission'],
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
                    'codigo_establecimiento' => 'PEND-' . $vendor->id,
                    'certificador_fel' => 'infile',
                    'banco_nombre' => $data['bank'],
                    'cuenta_bancaria' => $data['account_number'],
                    'cuenta_bancaria_tipo' => $data['account_type'],
                    'cuenta_bancaria_titular' => $data['account_holder'],
                    'fel_activo' => false,
                ]);
            }

            return $vendor;
        });

        return redirect()
            ->route('vendedor.solicitud.show', $vendor->application_code)
            ->with('status', 'Solicitud enviada correctamente.');
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

        return view('vendedor.solicitar.show', ['vendor' => $vendor]);
    }

    /**
     * Verifica disponibilidad de correo para validacion en vivo.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $email = trim((string) $request->query('email'));
        $exists = $email !== '' && User::query()->where('email', $email)->exists();

        return response()->json([
            'available' => ! $exists,
            'exists' => $exists,
        ]);
    }

    /**
     * Verifica disponibilidad de documento para validacion en vivo.
     */
    public function checkDocument(Request $request): JsonResponse
    {
        $document = trim((string) $request->query('document'));
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', $document);
        $exists = $document !== '' && Vendor::query()
            ->where(function ($query) use ($document, $normalized): void {
                $query->where('document_number', $document);

                if ($normalized !== '') {
                    $query->orWhereRaw("REPLACE(REPLACE(document_number, '-', ''), ' ', '') = ?", [$normalized]);
                }
            })
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'exists' => $exists,
        ]);
    }

    /**
     * Guarda documentos de la solicitud.
     *
     * @return array<string, string>
     */
    private function storeDocuments(Request $request, string $trackingCode): array
    {
        $path = 'vendor-applications/' . $trackingCode;
        $documents = [
            'document_front' => $request->file('document_front')?->store($path, 'public'),
            'document_back' => $request->file('document_back')?->store($path, 'public'),
            'business_logo' => $request->file('business_logo')?->store($path, 'public'),
            'bank_proof' => $request->file('bank_proof')?->store($path, 'public'),
        ];

        if ($request->hasFile('nit_file')) {
            $documents['nit_file'] = $request->file('nit_file')?->store($path, 'public');
        }

        return array_filter($documents);
    }

    /**
     * Genera un codigo publico de seguimiento.
     */
    private function trackingCode(): string
    {
        do {
            $code = 'VND-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        } while (Vendor::query()->where('application_code', $code)->exists());

        return $code;
    }

    /**
     * Normaliza direccion comercial opcional.
     *
     * @param array<string, mixed> $data
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
     * @param array<string, mixed> $address
     */
    private function formatAddress(array $address): string
    {
        return collect([
            $address['calle'] ?? null,
            $address['numero'] ?? null,
            $address['apto'] ?? null,
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
     * Planes comerciales disponibles para emprendedores.
     *
     * @return array<string, array<string, mixed>>
     */
    private function sellerPlans(): array
    {
        return [
            'starter' => [
                'name' => 'Emprendedor Starter',
                'price' => 0,
                'commission' => 18,
                'products' => 'Hasta 10 productos',
                'users' => '1 usuario',
                'payout' => 'Payout mensual',
                'support' => 'Email en 48h',
                'analytics' => 'Sin analytics',
                'description' => 'Empieza gratis y paga solo cuando vendes.',
            ],
            'plus' => [
                'name' => 'Emprendedor Plus',
                'price' => 149,
                'commission' => 15,
                'products' => 'Hasta 50 productos',
                'users' => '1 usuario',
                'payout' => 'Payout mensual',
                'support' => 'Email en 48h',
                'analytics' => 'Analytics basico',
                'description' => 'Mas catalogo y metricas simples para crecer.',
            ],
            'crecimiento' => [
                'name' => 'Crecimiento',
                'price' => 349,
                'commission' => 12,
                'products' => 'Hasta 150 productos',
                'users' => '3 usuarios',
                'payout' => 'Payout quincenal',
                'support' => 'Chat en 24h',
                'analytics' => 'Analytics avanzado y cupones ilimitados',
                'description' => 'Para negocios con ventas constantes.',
            ],
            'profesional' => [
                'name' => 'Profesional',
                'price' => 699,
                'commission' => 10,
                'products' => 'Productos ilimitados',
                'users' => '5 usuarios',
                'payout' => 'Payout semanal',
                'support' => 'Chat + email prioritario',
                'analytics' => 'Email marketing e Instagram basico',
                'description' => 'Para vendedores consolidados y con operacion formal.',
            ],
        ];
    }
}
