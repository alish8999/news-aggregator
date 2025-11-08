<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\NewsApiAdapter;
use App\Services\GuardianAdapter;
use App\Services\NytAdapter;

class DiagnoseApis extends Command
{
    protected $signature = 'diagnose:apis';
    protected $description = 'Diagnose API connectivity and configuration issues';

    public function handle()
    {
        $this->info('=== API Diagnostics ===');
        $this->newLine();

        // Check environment variables
        $this->checkEnvironmentVariables();
        $this->newLine();

        // Test each API
        $this->testNewsApi();
        $this->newLine();

        $this->testGuardianApi();
        $this->newLine();

        $this->testNytApi();
        $this->newLine();

        return Command::SUCCESS;
    }

    private function checkEnvironmentVariables()
    {
        $this->info('📋 Checking Environment Variables:');

        $vars = [
            'NEWS_API_KEY' => env('NEWS_API_KEY'),
            'NEWS_API_BASE_URL' => env('NEWS_API_BASE_URL', 'https://newsapi.org/v2'),
            'GUARDIAN_API_KEY' => env('GUARDIAN_API_KEY'),
            'GUARDIAN_API_BASE_URL' => env('GUARDIAN_API_BASE_URL', 'https://content.guardianapis.com'),
            'NYT_API_KEY' => env('NYT_API_KEY'),
            'NYT_API_BASE_URL' => env('NYT_API_BASE_URL', 'https://api.nytimes.com/svc/search/v2'),
        ];

        foreach ($vars as $key => $value) {
            if (empty($value)) {
                $this->error("  ❌ {$key}: NOT SET");
            } else {
                $masked = substr($value, 0, 8) . '...' . substr($value, -4);
                $this->info("  ✅ {$key}: {$masked}");
            }
        }
    }

    private function testNewsApi()
    {
        $this->info('🔍 Testing NewsAPI:');

        $apiKey = config('services.newsapi.key');
        $baseUrl = config('services.newsapi.base_url');

        if (empty($apiKey)) {
            $this->error('  ❌ API Key not configured');
            return;
        }

        try {
            $response = Http::get("{$baseUrl}/top-headlines", [
                'apiKey' => $apiKey,
                'country' => 'us',
                'pageSize' => 5,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalResults = $data['totalResults'] ?? 0;
                $articles = count($data['articles'] ?? []);

                $this->info("  ✅ Connection successful");
                $this->info("  📊 Total available: {$totalResults}");
                $this->info("  📰 Fetched: {$articles} articles");

                if ($articles > 0) {
                    $this->info("  📝 Sample: " . ($data['articles'][0]['title'] ?? 'N/A'));
                }
            } else {
                $this->error("  ❌ API Error: " . $response->status());
                $this->error("  Message: " . $response->json('message', 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Exception: " . $e->getMessage());
        }
    }

    private function testGuardianApi()
    {
        $this->info('🔍 Testing Guardian API:');

        $apiKey = config('services.guardian.key');
        $baseUrl = config('services.guardian.base_url');

        if (empty($apiKey)) {
            $this->error('  ❌ API Key not configured');
            return;
        }

        try {
            $response = Http::get("{$baseUrl}/search", [
                'api-key' => $apiKey,
                'page-size' => 5,
                'show-fields' => 'thumbnail,bodyText',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalResults = $data['response']['total'] ?? 0;
                $articles = count($data['response']['results'] ?? []);

                $this->info("  ✅ Connection successful");
                $this->info("  📊 Total available: {$totalResults}");
                $this->info("  📰 Fetched: {$articles} articles");

                if ($articles > 0) {
                    $this->info("  📝 Sample: " . ($data['response']['results'][0]['webTitle'] ?? 'N/A'));
                }
            } else {
                $this->error("  ❌ API Error: " . $response->status());
                $this->error("  Message: " . $response->json('message', 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Exception: " . $e->getMessage());
        }
    }

    private function testNytApi()
    {
        $this->info('🔍 Testing New York Times API:');

        $apiKey = config('services.nyt.key');
        $baseUrl = config('services.nyt.base_url');

        if (empty($apiKey)) {
            $this->error('  ❌ API Key not configured');
            return;
        }

        try {
            $response = Http::get("{$baseUrl}/articlesearch.json", [
                'api-key' => $apiKey,
                'page' => 0,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $totalResults = $data['response']['meta']['hits'] ?? 0;
                $articles = count($data['response']['docs'] ?? []);

                $this->info("  ✅ Connection successful");
                $this->info("  📊 Total available: {$totalResults}");
                $this->info("  📰 Fetched: {$articles} articles");

                if ($articles > 0) {
                    $this->info("  📝 Sample: " . ($data['response']['docs'][0]['headline']['main'] ?? 'N/A'));
                }
            } else {
                $this->error("  ❌ API Error: " . $response->status());
                $this->error("  Message: " . $response->json('message', 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Exception: " . $e->getMessage());
        }
    }
}
