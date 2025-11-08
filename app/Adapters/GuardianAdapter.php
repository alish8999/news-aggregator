<?php
// app/Adapters/GuardianAdapter.php
namespace App\Adapters;

use App\Interfaces\NewsAdapterInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardianAdapter implements NewsAdapterInterface
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.guardian.key');
        $this->baseUrl = config('services.guardian.base_url');
    }

    public function fetchAndAdapt(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/search", [
                'api-key' => $this->apiKey,
                'q' => 'technology', // We must provide a query term
                'show-fields' => 'byline,thumbnail,bodyText', // Get author, image, and description
                'page-size' => 10,
            ]);

            if ($response->failed()) {
                Log::error('The Guardian API request failed', $response->json());
                return [];
            }

            // The "Adapter" logic:
            // Transform The Guardian's "results" array into our standard format.
            return collect($response->json()['response']['results'] ?? [])
                ->map(function ($article) {
                    return [
                        'source_name' => 'The Guardian', // Hardcode the source name
                        'author_name' => $article['fields']['byline'] ?? 'The Guardian',
                        'category_name' => $article['sectionName'] ?? 'General',
                        'title' => $article['webTitle'],
                        'description' => $article['fields']['bodyText'] ?? $article['webTitle'],
                        'article_url' => $article['webUrl'],
                        'image_url' => $article['fields']['thumbnail'] ?? null,
                        'published_at' => Carbon::parse($article['webPublicationDate']),
                    ];
                })
                ->whereNotNull('article_url')
                ->unique('article_url')
                ->all();

        } catch (\Exception $e) {
            Log::error('Error fetching from The Guardian', ['message' => $e->getMessage()]);
            return [];
        }
    }
}
