<?php

namespace Tests\Unit\Integrations;

use App\Services\Integrations\ProductionIntegrationReadinessService;
use Tests\TestCase;

class ProductionIntegrationReadinessServiceTest extends TestCase
{
    public function test_complete_external_integrations_are_ready_without_bank_transfer(): void
    {
        $this->configureCompleteIntegrations();
        config()->set('atlantia.payments.transfer', [
            'enabled' => false,
            'bank_name' => null,
            'account_name' => null,
            'account_number' => null,
        ]);

        $report = app(ProductionIntegrationReadinessService::class)->audit();

        $this->assertSame('ok', $report['status']);
        $this->assertSame(7, $report['summary']['ok']);
        $this->assertSame(0, $report['summary']['error']);
        $this->assertCount(7, $report['checks']);
    }

    public function test_report_lists_variable_names_without_exposing_secret_values(): void
    {
        $secretPassword = 'smtp-super-secret-password';
        $privateKey = "-----BEGIN PRIVATE KEY-----\nprivate-super-secret\n-----END PRIVATE KEY-----";

        $this->configureCompleteIntegrations();
        config()->set('mail.mailers.smtp.password', $secretPassword);
        config()->set('services.firebase.private_key', $privateKey);
        config()->set('services.infile.webhook_secret', 'short');
        config()->set('atlantia.payments.pos.terminal_ids', []);

        $report = app(ProductionIntegrationReadinessService::class)->audit();
        $serialized = json_encode($report, JSON_UNESCAPED_UNICODE);

        $this->assertSame('error', $report['status']);
        $this->assertStringContainsString('INFILE_WEBHOOK_SECRET', $serialized);
        $this->assertStringContainsString('ATLANTIA_POS_TERMINAL_IDS', $serialized);
        $this->assertStringNotContainsString($secretPassword, $serialized);
        $this->assertStringNotContainsString('private-super-secret', $serialized);
    }

    private function configureCompleteIntegrations(): void
    {
        config()->set([
            'services.infile' => [
                'mock' => false,
                'base_url' => 'https://api.infile.test',
                'username' => 'atlantia-fel',
                'password' => 'infile-secret',
                'webhook_secret' => str_repeat('a', 40),
            ],
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'host' => 'smtp.atlantia.test',
                'port' => 587,
                'username' => 'mailer',
                'password' => 'smtp-secret',
            ],
            'mail.from.address' => 'no-reply@atlantia.test',
            'filesystems.default' => 's3',
            'filesystems.private_disk' => 's3',
            'filesystems.disks.s3' => [
                'key' => null,
                'secret' => null,
                'region' => 'us-east-1',
                'bucket' => 'atlantia-production',
                'endpoint' => null,
                'use_path_style_endpoint' => false,
                'use_instance_profile' => true,
            ],
            'services.firebase' => [
                'enabled' => true,
                'project_id' => 'atlantia-production',
                'service_account_email' => 'fcm@atlantia.test',
                'private_key' => "-----BEGIN PRIVATE KEY-----\ntest-key\n-----END PRIVATE KEY-----",
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ],
            'services.mapbox' => [
                'token' => 'pk.restricted-token',
                'base_url' => 'https://api.mapbox.com',
            ],
            'services.google_maps.api_key' => 'google-restricted-key',
            'atlantia.support' => [
                'email' => 'soporte@atlantia.test',
                'phone' => '+502 2222 0101',
                'emergency_phone' => '+502 2222 0191',
                'whatsapp' => '+502 2222 0121',
                'channels' => ['app', 'whatsapp', 'phone', 'emergency'],
            ],
            'atlantia.payments.pos' => [
                'enabled' => true,
                'provider' => 'Banco adquirente',
                'support_phone' => '+502 2222 0181',
                'merchant_id' => 'ATLANTIA-MERCHANT',
                'terminal_ids' => ['POS-001', 'POS-002'],
                'currency' => 'GTQ',
                'receipt_required' => true,
            ],
        ]);
    }
}
