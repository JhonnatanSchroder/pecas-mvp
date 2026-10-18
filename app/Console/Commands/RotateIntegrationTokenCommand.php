<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RotateIntegrationTokenCommand extends Command
{
    protected $signature = 'integration:rotate-token
        {company : The company ID whose integration token will be rotated}
        {--n8n-env=infra/n8n/.env : Existing n8n .env file to update without displaying the plaintext token}
        {--force : Skip confirmation in local or development environments only}';

    protected $description = 'Rotate a company integration token and safely update the local n8n environment file';

    public function handle(): int
    {
        $company = Company::query()->find($this->argument('company'));
        if ($company === null) {
            $this->error('Company not found.');

            return self::FAILURE;
        }

        $providedPath = (string) $this->option('n8n-env');
        $candidatePath = preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $providedPath) === 1
            ? $providedPath
            : base_path($providedPath);
        $path = realpath($candidatePath);
        if ($path === false || ! str_starts_with($path, base_path().DIRECTORY_SEPARATOR) || ! is_file($path) || ! is_writable($path)) {
            $this->error('The n8n environment file must exist, be writable, and be inside the application directory.');

            return self::FAILURE;
        }

        if ($this->option('force') && ! app()->environment(['local', 'development'])) {
            $this->error('Forced token rotation is only allowed in local or development environments.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Rotate the integration token for company #{$company->id} and update the n8n environment file?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $token = Str::random(64);
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->error('Unable to read the n8n environment file.');

            return self::FAILURE;
        }

        $replacement = 'LARAVEL_INTEGRATION_TOKEN='.$token;
        if (preg_match('/^LARAVEL_INTEGRATION_TOKEN=.*$/m', $contents) === 1) {
            $contents = preg_replace('/^LARAVEL_INTEGRATION_TOKEN=.*$/m', $replacement, $contents, 1) ?? $contents;
        } else {
            $contents = rtrim($contents).PHP_EOL.$replacement.PHP_EOL;
        }

        if (file_put_contents($path, $contents) === false) {
            $this->error('Unable to update the n8n environment file.');

            return self::FAILURE;
        }

        $company->update(['integration_token_hash' => hash('sha256', $token)]);
        $this->info('Integration token rotated and saved to the n8n environment file. Restart n8n to apply it.');

        return self::SUCCESS;
    }
}
