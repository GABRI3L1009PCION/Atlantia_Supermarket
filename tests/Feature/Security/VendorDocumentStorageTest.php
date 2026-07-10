<?php

namespace Tests\Feature\Security;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pruebas de almacenamiento seguro para documentos de vendedores.
 */
class VendorDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DPI, comprobantes y NIT se guardan en privado; el logo queda publico.
     */
    public function test_vendor_application_stores_sensitive_documents_privately(): void
    {
        config(['filesystems.private_disk' => 'local']);
        Storage::fake('local');
        Storage::fake('public');

        $this->post(route('vendedor.solicitar.store'), [
            'name' => 'Vendedora Atlantia',
            'email' => 'vendedora@example.test',
            'phone' => '+502 1234 5678',
            'birthdate' => '1990-01-01',
            'gender' => 'prefiero_no_decir',
            'address_street' => 'Avenida Principal',
            'address_number' => '12-34',
            'address_suite' => null,
            'address_municipio' => 'Puerto Barrios',
            'address_departamento' => 'Izabal',
            'address_zip' => '18001',
            'document_type' => 'dpi',
            'document_number' => '12345678-1234',
            'document_front' => UploadedFile::fake()->image('dpi-frente.jpg'),
            'document_back' => UploadedFile::fake()->image('dpi-reverso.jpg'),
            'business_name' => 'Tienda Segura Atlantia',
            'business_description' => 'Comercio local con productos de supermercado.',
            'business_category' => 'alimentos_frescos',
            'business_logo' => UploadedFile::fake()->image('logo.jpg'),
            'has_nit' => true,
            'seller_plan' => 'starter',
            'nit_number' => '1234567-8',
            'razon_social' => 'Tienda Segura Atlantia Sociedad',
            'regimen_sat' => 'ordinario',
            'business_street' => 'Avenida Principal',
            'business_number' => '12-34',
            'business_municipio' => 'Puerto Barrios',
            'nit_file' => UploadedFile::fake()->image('nit.jpg'),
            'bank' => 'Banrural',
            'account_type' => 'ahorros',
            'account_number' => '123456789',
            'account_holder' => 'Vendedora Atlantia',
            'bank_proof' => UploadedFile::fake()->image('banco.jpg'),
            'payment_frequency' => 'quincenal',
            'preferred_payment_method' => 'transferencia',
            'terms' => '1',
            'truth' => '1',
            'data_consent' => '1',
        ])->assertRedirect();

        $vendor = Vendor::query()->where('business_name', 'Tienda Segura Atlantia')->firstOrFail();
        $documents = $vendor->documents;

        foreach (['document_front', 'document_back', 'bank_proof', 'nit_file'] as $key) {
            Storage::disk('local')->assertExists($documents[$key]);
            Storage::disk('public')->assertMissing($documents[$key]);
        }

        Storage::disk('public')->assertExists($documents['business_logo']);
    }
}
