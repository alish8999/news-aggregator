<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface ArticleRepositoryInterface
{
    /**
     * Get paginated filtered articles with search
     */
    public function getPaginatedFilteredArticles(Request $request): LengthAwarePaginator;

    /**
     * Get cursor-paginated feed for a user or the public (optimized for infinite scroll)
     */
    public function getCursorPaginatedUserFeed(?User $user, Request $request): CursorPaginator;

    /**
     * Get paginated user feed (legacy method)
     */
    public function getPaginatedUserFeed(User $user): LengthAwarePaginator;
}
