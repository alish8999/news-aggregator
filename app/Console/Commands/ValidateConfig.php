<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ValidateConfig extends Command
{
    protected $signature = 'config:validate';
    protected $description = 'Validate that all required API keys and configurations are set';

    public function handle()
    {
        $this->info('Validating configuration...');
        $errors = [];

        // Check NewsAPI
        if (empty(config('services.newsapi.key'))) {
            $errors[] = 'NewsAPI key is missing (NEWSAPI_KEY)';
        }
        if (empty(config('services.newsapi.base_url'))) {
            $errors[] = 'NewsAPI base URL is missing (NEWSAPI_BASE_URL)';
        }

        // Check Guardian
        if (empty(config('services.guardian.key'))) {
            $errors[] = 'Guardian API key is missing (GUARDIAN_API_KEY)';
        }
        if (empty(config('services.guardian.base_url'))) {
            $errors[] = 'Guardian base URL is missing (GUARDIAN_BASE_URL)';
        }

        // Check NYT
        if (empty(config('services.nyt.key'))) {
            $errors[] = 'New York Times API key is missing (NYT_API_KEY)';
        }
        if (empty(config('services.nyt.base_url'))) {
            $errors[] = 'New York Times base URL is missing (NYT_BASE_URL)';
        }

        // Check database
        if (empty(config('database.connections.' . config('database.default')))) {
            $errors[] = 'Database configuration is invalid';
        }

        if (empty($errors)) {
            $this->info('✓ All configurations are valid!');
            return self::SUCCESS;
        }

        $this->error('Configuration errors found:');
        foreach ($errors as $error) {
            $this->error("  ✗ {$error}");
        }

        return self::FAILURE;
    }
}
