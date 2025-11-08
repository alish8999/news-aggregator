<?php

namespace App\Repositories;

use App\Interfaces\ArticleRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DbArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Base query with all necessary joins
     */
    private function baseQuery()
    {
        return DB::table('articles')
            ->join('sources', 'articles.source_id', '=', 'sources.id')
            ->leftJoin('categories', 'articles.category_id', '=', 'categories.id')
            ->leftJoin('authors', 'articles.author_id', '=', 'authors.id')
            ->select(
                'articles.*',
                'sources.name as source_name',
                'sources.slug as source_slug',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'authors.name as author_name'
            );
    }

    /**
     * Get paginated filtered articles (for search/filter)
     */
    public function getPaginatedFilteredArticles(Request $request): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        // 1. Handle Search Query (PostgreSQL full-text search)
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereRaw(
                "search_vector @@ plainto_tsquery('english', ?)",
                [$keyword]
            );
        }

        // 2. Handle Filtering by Date
        if ($request->filled('date')) {
            $query->whereDate('articles.published_at', $request->input('date'));
        }

        // 3. Handle Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('articles.published_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('articles.published_at', '<=', $request->input('date_to'));
        }

        // 4. Handle Filtering by Category
        if ($request->filled('category')) {
            $query->where('categories.slug', $request->input('category'));
        }

        // 5. Handle Filtering by Source
        if ($request->filled('source')) {
            $query->where('sources.slug', $request->input('source'));
        }

        // 6. Handle Filtering by Author
        if ($request->filled('author')) {
            $query->where('authors.name', $request->input('author'));
        }

        $perPage = $request->input('per_page', 20);
        $perPage = min(max($perPage, 1), 100); // Clamp between 1-100

        return $query->orderBy('articles.published_at', 'desc')
            ->orderBy('articles.id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get cursor-paginated feed for a user or the public (optimized for infinite scroll)
     */
    public function getCursorPaginatedUserFeed(?User $user, Request $request): CursorPaginator
    {
        $query = $this->baseQuery();

        // If a user is provided, filter by their preferences
        if ($user) {
            $preferredSourceIds = $user->preferredSources()->pluck('sources.id')->toArray();
            $preferredCategoryIds = $user->preferredCategories()->pluck('categories.id')->toArray();
            $preferredAuthorIds = $user->preferredAuthors()->pluck('authors.id')->toArray();

            // If user has preferences, filter by them
            if (!empty($preferredSourceIds) || !empty($preferredCategoryIds) || !empty($preferredAuthorIds)) {
                $query->where(function ($q) use ($preferredSourceIds, $preferredCategoryIds, $preferredAuthorIds) {
                    if (!empty($preferredSourceIds)) {
                        $q->orWhereIn('articles.source_id', $preferredSourceIds);
                    }
                    if (!empty($preferredCategoryIds)) {
                        $q->orWhereIn('articles.category_id', $preferredCategoryIds);
                    }
                    if (!empty($preferredAuthorIds)) {
                        $q->orWhereIn('articles.author_id', $preferredAuthorIds);
                    }
                });
            }
        }

        $perPage = $request->input('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        return $query
            ->orderBy('articles.published_at', 'desc')
            ->orderBy('articles.id', 'desc')
            ->cursorPaginate($perPage);
    }

    /**
     * Legacy method - kept for backward compatibility
     */
    public function getPaginatedUserFeed(User $user): LengthAwarePaginator
    {
        $preferredSourceIds = $user->preferredSources()->pluck('sources.id')->toArray();
        $preferredCategoryIds = $user->preferredCategories()->pluck('categories.id')->toArray();
        $preferredAuthorIds = $user->preferredAuthors()->pluck('authors.id')->toArray();

        $query = $this->baseQuery();

        if (!empty($preferredSourceIds) || !empty($preferredCategoryIds) || !empty($preferredAuthorIds)) {
            $query->where(function ($q) use ($preferredSourceIds, $preferredCategoryIds, $preferredAuthorIds) {
                if (!empty($preferredSourceIds)) {
                    $q->orWhereIn('articles.source_id', $preferredSourceIds);
                }
                if (!empty($preferredCategoryIds)) {
                    $q->orWhereIn('articles.category_id', $preferredCategoryIds);
                }
                if (!empty($preferredAuthorIds)) {
                    $q->orWhereIn('articles.author_id', $preferredAuthorIds);
                }
            });
        }

        return $query
            ->orderBy('articles.published_at', 'desc')
            ->orderBy('articles.id', 'desc')
            ->paginate(20);
    }
}
