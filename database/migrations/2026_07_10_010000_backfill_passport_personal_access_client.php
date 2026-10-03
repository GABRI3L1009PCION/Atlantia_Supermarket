<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

return new class extends Migration
{
    /**
     * Completa el cliente personal de Passport usado por la app movil.
     */
    public function up(): void
    {
        if (! Schema::hasTable('oauth_clients')) {
            return;
        }

        $client = DB::table('oauth_clients')
            ->where('revoked', false)
            ->where(function ($query): void {
                $query->where('provider', 'users')
                    ->orWhereNull('provider');
            })
            ->where('personal_access_client', true)
            ->latest('created_at')
            ->first(['id']);

        if (! $client) {
            $client = app(ClientRepository::class)->createPersonalAccessGrantClient(
                'Atlantia Mobile Personal Access',
                'users'
            );
        }

        if (Schema::hasTable('oauth_personal_access_clients')) {
            DB::table('oauth_personal_access_clients')->updateOrInsert(
                ['client_id' => (string) $client->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * No revoca clientes porque pueden existir tokens activos.
     */
    public function down(): void
    {
        //
    }
};
