<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupArticles extends Command
{
    protected $signature = 'articles:cleanup
                            {--days=30 : Number of days to keep articles}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old articles from the database';

    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up articles older than {$days} days (before {$cutoffDate->toDateString()})...");

        $count = DB::table('articles')
            ->where('published_at', '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->info('No articles to clean up.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} articles to delete.");

        if ($dryRun) {
            $this->info('DRY RUN - No articles were actually deleted.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Do you want to proceed with deletion?', true)) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        try {
            $deleted = DB::table('articles')
                ->where('published_at', '<', $cutoffDate)
                ->delete();

            $this->info("Successfully deleted {$deleted} articles.");

            Log::info('Article cleanup completed', [
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clean up articles: ' . $e->getMessage());

            Log::error('Article cleanup failed', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

            return self::FAILURE;
        }
    }
}
