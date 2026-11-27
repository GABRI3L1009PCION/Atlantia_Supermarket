<?php

use App\Models\Vendor;
use App\Models\VendorFiscalProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El perfil fiscal se guarda por secciones: un vendedor puede registrar su cuenta
 * para cobrar sin tener NIT. Además se recupera la cuenta bancaria que los vendedores
 * sin NIT dieron en su solicitud (antes quedaba solo en vendors.banking_info).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_fiscal_profiles', function (Blueprint $table): void {
            $table->string('nit', 30)->nullable()->change();
            $table->string('razon_social', 220)->nullable()->change();
            $table->text('direccion_fiscal')->nullable()->change();
            $table->string('codigo_establecimiento', 50)->nullable()->change();
            $table->enum('regimen_sat', ['pequeno_contribuyente', 'general', 'exento'])->nullable()->change();
        });

        $tipos = ['ahorros' => 'ahorro', 'ahorro' => 'ahorro', 'corriente' => 'monetaria', 'monetaria' => 'monetaria'];

        Vendor::query()->whereNotNull('banking_info')->with('fiscalProfile')->each(function (Vendor $vendor) use ($tipos): void {
            $banco = (array) $vendor->banking_info;
            if (blank($banco['numero_cuenta'] ?? null) || blank($banco['banco'] ?? null)) {
                return;
            }

            $perfil = $vendor->fiscalProfile;
            if ($perfil !== null && filled($perfil->cuenta_bancaria)) {
                return;
            }

            $datos = [
                'banco_nombre' => $banco['banco'],
                'cuenta_bancaria' => $banco['numero_cuenta'],
                'cuenta_bancaria_tipo' => $tipos[$banco['tipo_cuenta'] ?? ''] ?? null,
                'cuenta_bancaria_titular' => $banco['titular'] ?? null,
            ];

            $perfil === null
                ? VendorFiscalProfile::query()->create(['vendor_id' => $vendor->id, 'certificador_fel' => 'infile', 'fel_activo' => false, ...$datos])
                : $perfil->update($datos);
        });
    }

    public function down(): void
    {
        // Las columnas quedan como opcionales: volver a exigirlas borraría perfiles con solo datos bancarios.
    }
};
