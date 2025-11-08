<?php
namespace App\Adapters;

use App\Interfaces\NewsAdapterInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NytAdapter implements NewsAdapterInterface
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.nyt.key');
        $this->baseUrl = config('services.nyt.base_url');
    }

    public function fetchAndAdapt(): array
    {
        try {
            $allArticles = [];

            // NYT API allows fetching multiple pages (max 100 pages)
            // Each page can have up to 10 articles
            // Let's fetch 10 pages to get 100 articles
                $response = Http::timeout(30)->get("{$this->baseUrl}/articlesearch.json", [
                    'api-key' => $this->apiKey,
                    'sort' => 'newest',
                    'page' => 0,
                ]);

                if ($response->failed()) {
                    Log::error('NYT API request failed', [
                        'page' => 0,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }

                $data = $response->json();
                $allArticles = $data['response']['docs'] ?? [];

                if (empty($allArticles)) {
                    Log::info("NYT Adapter: No more articles");
                }


            Log::info('NYT API Total Fetched', [
                'total_docs' => count($allArticles),
            ]);

            if (empty($allArticles)) {
                Log::warning('NYT API returned no articles');
                return [];
            }

            $articles = collect($allArticles)
                ->map(function ($article) {
                    // Helper to find the first image
                    $imageUrl = null;
                    if (!empty($article['multimedia'])) {
                        $firstImage = $article['multimedia'][0]['url'] ?? null;
                        if ($firstImage) {
                            $imageUrl = str_starts_with($firstImage, 'http')
                                ? $firstImage
                                : 'https://www.nytimes.com/' . $firstImage;
                        }
                    }

                    $authorName = 'The New York Times';
                    if (!empty($article['byline']['original'])) {
                        $authorName = str_replace('By ', '', $article['byline']['original']);
                    } elseif (!empty($article['byline']['person'])) {
                        $authorName = $article['byline']['person'][0]['firstname'] . ' ' .
                                     $article['byline']['person'][0]['lastname'];
                    }

                    return [
                        'source_name' => 'The New York Times',
                        'author_name' => $authorName,
                        'category_name' => $article['section_name'] ?? 'General',
                        'title' => $article['headline']['main'] ?? 'Untitled',
                        'description' => $article['abstract'] ?? $article['snippet'] ?? $article['lead_paragraph'] ?? '',
                        'article_url' => $article['web_url'],
                        'image_url' => $imageUrl,
                        'published_at' => Carbon::parse($article['pub_date']),
                    ];
                })
                ->filter(function ($article) {
                    return !empty($article['article_url']) &&
                           !empty($article['title']) &&
                           !empty($article['description']);
                })
                ->unique('article_url')
                ->values()
                ->all();

            Log::info('NYT Adapter: Finished processing', [
                'total_processed' => count($articles),
            ]);

            return $articles;

        } catch (\Exception $e) {
            Log::error('Error fetching from NYT', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }
}
