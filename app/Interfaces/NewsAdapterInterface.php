<?php

namespace App\Interfaces;

/**
 * Interface for news source adapters
 *
 * Each adapter is responsible for:
 * 1. Fetching data from a specific news API
 * 2. Transforming the API response into a standardized format
 * 3. Handling API-specific errors and rate limits
 *
 * @package App\Interfaces
 */
interface NewsAdapterInterface
{
    /**
     * Fetch articles from the news source and transform them into a standard format
     *
     * @return array Array of standardized article data with keys:
     *               - source_name: string
     *               - author_name: string
     *               - category_name: string
     *               - title: string
     *               - description: string
     *               - article_url: string
     *               - image_url: string|null
     *               - published_at: Carbon
     */
    public function fetchAndAdapt(): array;
}
