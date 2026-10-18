<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class VerifyIntegrationTokenCommand extends Command
{
    protected $signature = 'integration:verify-token
        {companyId : The company ID whose integration token will be verified}
        {--n8n-env=infra/n8n/.env : Existing local n8n .env file to read}';

    protected $description = 'Verify that the local n8n integration token matches a company hash';

    public function handle(): int
    {
        $company = Company::query()->find($this->argument('companyId'));
        $token = $company === null ? null : $this->tokenFromEnvironmentFile();

        if (! is_string($token) || $token === '' || ! hash_equals((string) $company->integration_token_hash, hash('sha256', $token))) {
            $this->line('TOKEN NÃO CONFERE');

            return self::FAILURE;
        }

        $this->line('TOKEN CONFERE');

        return self::SUCCESS;
    }

    private function tokenFromEnvironmentFile(): ?string
    {
        $providedPath = (string) $this->option('n8n-env');
        $candidatePath = preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $providedPath) === 1
            ? $providedPath
            : base_path($providedPath);
        $path = realpath($candidatePath);
        if ($path === false || ! str_starts_with($path, base_path().DIRECTORY_SEPARATOR) || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        foreach (preg_split('/\r\n|\n|\r/', $contents) as $line) {
            if (str_starts_with($line, 'LARAVEL_INTEGRATION_TOKEN=')) {
                return substr($line, strlen('LARAVEL_INTEGRATION_TOKEN='));
            }
        }

        return null;
    }
}
