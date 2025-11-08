<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule article fetching - Every 2 minutes for development
Schedule::command('articles:fetch')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled article fetch completed successfully');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled article fetch failed');
    });

// Optional: Clean up old articles (older than 30 days)
Schedule::command('articles:cleanup')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

// Optional: Generate daily statistics
Schedule::command('articles:stats')
    ->dailyAt('01:00')
    ->withoutOverlapping();
