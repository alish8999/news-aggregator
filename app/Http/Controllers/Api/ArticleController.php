<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Interfaces\ArticleRepositoryInterface;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected ArticleRepositoryInterface $articleRepository;

    public function __construct(ArticleRepositoryInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    /**
     * Get articles with search and filters
     * GET /api/articles
     *
     * Query params:
     * - keyword: search in title and description
     * - date: filter by specific date (YYYY-MM-DD)
     * - date_from: filter from date (YYYY-MM-DD)
     * - date_to: filter to date (YYYY-MM-DD)
     * - category: filter by category name
     * - source: filter by source name
     * - author: filter by author name
     * - per_page: items per page (1-100, default: 20)
     */
    public function index(Request $request)
    {
        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'category' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $articles = $this->articleRepository->getPaginatedFilteredArticles($request);

        return ArticleResource::collection($articles);
    }

    /**
     * Get personalized feed for authenticated user
     *
     * Query params:
     * - per_page: items per page (1-100, default: 20)
     * - cursor: pagination cursor (for infinite scroll)
     */
    public function userFeed(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();

        // Use cursor pagination for better performance with infinite scroll
        $articles = $this->articleRepository->getCursorPaginatedUserFeed($user, $request);

        return ArticleResource::collection($articles);
    }




}
