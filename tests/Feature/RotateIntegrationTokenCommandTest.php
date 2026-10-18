<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateIntegrationTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_the_hash_and_updates_the_n8n_environment_file_without_printing_the_token(): void
    {
        config(['app.env' => 'local']);
        app()->detectEnvironment(fn (): string => 'local');
        $company = Company::factory()->create(['integration_token_hash' => hash('sha256', 'old-test-token')]);
        $path = storage_path('framework/testing-n8n.env');
        file_put_contents($path, "N8N_HOST=localhost\nLARAVEL_INTEGRATION_TOKEN=old-test-token\n");

        $this->artisan('integration:rotate-token', ['company' => $company->id, '--n8n-env' => $path])
            ->expectsConfirmation("Rotate the integration token for company #{$company->id} and update the n8n environment file?", 'yes')
            ->expectsOutput('Integration token rotated and saved to the n8n environment file. Restart n8n to apply it.')
            ->assertSuccessful();

        $contents = (string) file_get_contents($path);
        preg_match('/^LARAVEL_INTEGRATION_TOKEN=(.+)$/m', $contents, $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->assertSame(64, strlen($matches[1]));
        $this->assertSame(hash('sha256', $matches[1]), $company->fresh()->integration_token_hash);
        unlink($path);
    }

    public function test_it_rotates_without_a_prompt_when_forced_in_a_local_environment(): void
    {
        config(['app.env' => 'local']);
        app()->detectEnvironment(fn (): string => 'local');
        $company = Company::factory()->create();
        $path = storage_path('framework/testing-n8n.env');
        file_put_contents($path, "LARAVEL_INTEGRATION_TOKEN=old-test-token\n");

        $this->artisan('integration:rotate-token', ['company' => $company->id, '--n8n-env' => $path, '--force' => true])
            ->expectsOutput('Integration token rotated and saved to the n8n environment file. Restart n8n to apply it.')
            ->assertSuccessful();

        unlink($path);
    }

    public function test_it_rejects_forced_rotation_in_production(): void
    {
        config(['app.env' => 'production']);
        app()->detectEnvironment(fn (): string => 'production');
        $company = Company::factory()->create();
        $path = storage_path('framework/testing-n8n.env');
        file_put_contents($path, "LARAVEL_INTEGRATION_TOKEN=old-test-token\n");

        $this->artisan('integration:rotate-token', ['company' => $company->id, '--n8n-env' => $path, '--force' => true])
            ->expectsOutput('Forced token rotation is only allowed in local or development environments.')
            ->assertFailed();

        $this->assertSame("LARAVEL_INTEGRATION_TOKEN=old-test-token\n", file_get_contents($path));
        unlink($path);
    }
}
