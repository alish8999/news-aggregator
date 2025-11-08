<?php

namespace App\Providers;

use App\Adapters\GuardianAdapter;
use App\Adapters\NewsApiAdapter;
use App\Adapters\NytAdapter;
use App\Interfaces\ArticleRepositoryInterface;
use App\Repositories\DbArticleRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->tag([
            NewsApiAdapter::class,
            GuardianAdapter::class,
            NytAdapter::class,
            // Add new adapters here, e.g., BbcAdapter::class
        ], 'news.adapters');
        $this->app->bind(
            ArticleRepositoryInterface::class,
            DbArticleRepository::class
        );
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
