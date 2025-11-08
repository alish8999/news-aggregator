<?php

namespace App\Adapters;

use App\Interfaces\NewsAdapterInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsApiAdapter implements NewsAdapterInterface
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout = 30;
    private int $retryTimes = 3;
    private int $retryDelay = 1000; // milliseconds

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key');
        $this->baseUrl = config('services.newsapi.base_url');
    }

    public function fetchAndAdapt(): array
    {
        $cacheKey = 'newsapi_articles_' . now()->format('Y-m-d-H');

        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retryDelay)
                    ->get("{$this->baseUrl}/everything", [
                        'q' => 'technology',
                        'apiKey' => $this->apiKey,
                        'pageSize' => 10,
                        'language' => 'en',
                        'sortBy' => 'publishedAt',
                    ]);

                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorBody = $response->json();

                    Log::error('NewsAPI request failed', [
                        'status' => $statusCode,
                        'error' => $errorBody,
                        'adapter' => self::class,
                    ]);

                    if ($statusCode === 429) {
                        Log::warning('NewsAPI rate limit exceeded');
                    } elseif ($statusCode === 401) {
                        Log::critical('NewsAPI authentication failed - check API key');
                    }

                    return [];
                }

                $articles = $response->json()['articles'] ?? [];

                if (empty($articles)) {
                    Log::warning('NewsAPI returned no articles');
                    return [];
                }

                return $this->transformArticles($articles);

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('NewsAPI connection failed', [
                    'message' => $e->getMessage(),
                    'adapter' => self::class,
                ]);
                return [];
            } catch (\Exception $e) {
                Log::error('Unexpected error fetching from NewsAPI', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'adapter' => self::class,
                ]);
                return [];
            }
        });
    }

    private function transformArticles(array $articles): array
    {
        return collect($articles)
            ->map(function ($article) {
                if (empty($article['url']) || empty($article['title'])) {
                    return null;
                }
                return [
                    'source_name' => $article['source']['name'] ?? 'Unknown',
                    'author_name' => $this->cleanAuthorName($article['author'] ?? 'Unknown'),
                    'category_name' => 'General',
                    'title' => $this->sanitizeText($article['title']),
                    'description' => $this->sanitizeText($article['description'] ?? ''),
                    'article_url' => $article['url'],
                    'image_url' => $this->validateImageUrl($article['urlToImage'] ?? null),
                    'published_at' => $this->parseDate($article['publishedAt']),
                ];
            })
            ->filter()
            ->whereNotNull('article_url')
            ->unique('article_url')
            ->values()
            ->all();
    }

    private function cleanAuthorName(?string $author): string
    {
        if (empty($author)) {
            return 'Unknown';
        }

        $author = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '', $author);
        $author = preg_replace('/https?:\/\/[^\s]+/', '', $author);
        return trim($author) ?: 'Unknown';
    }

    private function sanitizeText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }

    private function validateImageUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $validExtensions) && !str_contains($url, 'image')) {
            return null;
        }

        return $url;
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning('Failed to parse date', ['date' => $date]);
            return null;
        }
    }
}
