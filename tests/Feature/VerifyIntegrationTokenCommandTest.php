<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyIntegrationTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_a_matching_token_without_printing_sensitive_values(): void
    {
        $token = 'test-token-that-is-not-a-secret';
        $company = Company::factory()->create(['integration_token_hash' => hash('sha256', $token)]);
        $path = storage_path('framework/verify-n8n.env');
        file_put_contents($path, "LARAVEL_INTEGRATION_TOKEN={$token}\r\n");

        $this->artisan('integration:verify-token', ['companyId' => $company->id, '--n8n-env' => $path])
            ->expectsOutput('TOKEN CONFERE')
            ->doesntExpectOutputToContain($token)
            ->assertSuccessful();

        unlink($path);
    }

    public function test_it_fails_without_disclosing_why_a_token_does_not_match(): void
    {
        $company = Company::factory()->create(['integration_token_hash' => hash('sha256', 'different-test-token')]);
        $path = storage_path('framework/verify-n8n.env');
        file_put_contents($path, "LARAVEL_INTEGRATION_TOKEN=another-test-token\n");

        $this->artisan('integration:verify-token', ['companyId' => $company->id, '--n8n-env' => $path])
            ->expectsOutput('TOKEN NÃO CONFERE')
            ->doesntExpectOutputToContain('another-test-token')
            ->assertFailed();

        unlink($path);
    }
}
