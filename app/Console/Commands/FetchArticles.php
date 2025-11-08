<?php

namespace App\Console\Commands;

use App\Interfaces\ArticleRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FetchArticles extends Command
{
    protected $signature = 'articles:fetch
                            {--source= : Fetch from specific source only}
                            {--force : Force fetch even if recently updated}';

    protected $description = 'Fetches articles from all registered news adapters';

    public function handle()
    {
        // Check if we should skip this run
        if (!$this->option('force') && !$this->shouldRun()) {
            $this->info('Skipping fetch - too soon since last run');
            return self::SUCCESS;
        }

        $this->info('Starting article fetch process...');
        $startTime = now();

        Cache::put('last_article_fetch', now(), now()->addDay());

        $adapters = app()->tagged('news.adapters');
        $totalFetched = 0;
        $totalStored = 0;
        $errors = [];

        foreach ($adapters as $adapter) {
            $adapterName = class_basename($adapter);

            // Skip if specific source requested and this isn't it
            if ($this->option('source') && !str_contains($adapterName, $this->option('source'))) {
                continue;
            }

            $this->info("Fetching from {$adapterName}...");

            try {
                $articles = $adapter->fetchAndAdapt();
                $fetchedCount = count($articles);
                $totalFetched += $fetchedCount;

                if ($fetchedCount === 0) {
                    $this->warn("  No articles fetched from {$adapterName}");
                    continue;
                }

                $this->info("  Fetched {$fetchedCount} articles");

                DB::beginTransaction();
                try {
                    $storedCount = 0;
                    foreach ($articles as $article) {
                        $sourceId = $this->findOrCreate('sources', $article['source_name']);
                        $authorId = $this->findOrCreate('authors', $article['author_name']);
                        $categoryId = $this->findOrCreate('categories', $article['category_name']);

                        DB::table('articles')->updateOrInsert(
                            ['article_url' => $article['article_url']], // Condition to find
                            [ // Data to insert or update
                                'source_id' => $sourceId,
                                'author_id' => $authorId,
                                'category_id' => $categoryId,
                                'title' => $article['title'],
                                'description' => $article['description'],
                                'image_url' => $article['image_url'],
                                'published_at' => $article['published_at'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $storedCount++;
                    }
                    DB::commit();

                    $totalStored += $storedCount;
                    $this->info("  Stored {$storedCount} articles");

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            } catch (\Exception $e) {
                $error = "Error with {$adapterName}: " . $e->getMessage();
                $errors[] = $error;
                $this->error("  " . $error);
                Log::error($error, ['exception' => $e]);
            }
        }

        $duration = now()->diffInSeconds($startTime);

        // Summary
        $this->newLine();
        $this->info('=== Fetch Summary ===');
        $this->info("Total fetched: {$totalFetched}");
        $this->info("Total stored: {$totalStored}");
        $this->info("Duration: {$duration} seconds");

        if (!empty($errors)) {
            $this->error("Errors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        // Log summary
        Log::info('Article fetch completed', [
            'total_fetched' => $totalFetched,
            'total_stored' => $totalStored,
            'duration_seconds' => $duration,
            'errors' => $errors,
        ]);

        // Store metrics in cache for monitoring
        Cache::put('article_fetch_metrics', [
            'last_run' => now(),
            'total_fetched' => $totalFetched,
            'total_stored' => $totalStored,
            'duration' => $duration,
            'errors_count' => count($errors),
        ], now()->addDay());

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Check if enough time has passed since last run
     */
    private function shouldRun(): bool
    {
        $lastRun = Cache::get('last_article_fetch');

        if (!$lastRun) {
            return true;
        }

        // Don't run if last run was less than 20 minutes ago
        return now()->diffInMinutes($lastRun) >= 2;
    }

    /**
     * Helper function to find or create a related record
     * using only Query Builder and return its ID.
     */
    private function findOrCreate(string $table, string $name): int
    {
        // Handle empty or "Unknown" names
        if (empty($name) || $name === 'Unknown') {
            $name = 'Unknown';
        }

        // 1. Try to find the record
        $record = DB::table($table)->where('name', $name)->first();

        if ($record) {
            return $record->id;
        }

        // 2. Not found, so create it
        $slug = str($name)->slug()->value();
        $data = [
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($table === 'sources' || $table === 'categories') {
            // Check if slug exists, if so, make it unique
            $baseSlug = $slug;
            $counter = 1;
            while (DB::table($table)->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $data['slug'] = $slug;
        }

        return DB::table($table)->insertGetId($data);
    }
}
