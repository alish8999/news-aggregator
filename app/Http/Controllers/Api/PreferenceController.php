<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreferenceController extends Controller
{
    /**
     * Get all available sources
     */
    public function getSources()
    {
        $sources = DB::table('sources')
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sources
        ]);
    }

    /**
     * Get all available categories
     */
    public function getCategories()
    {
        $categories = DB::table('categories')
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get all available authors
     */
    public function getAuthors()
    {
        $authors = DB::table('authors')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $authors
        ]);
    }

    /**
     * Get current user's preferences
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();

        $preferences = [
            'sources' => $user->preferredSources()->select('sources.id', 'sources.name', 'sources.slug')->get(),
            'categories' => $user->preferredCategories()->select('categories.id', 'categories.name', 'categories.slug')->get(),
            'authors' => $user->preferredAuthors()->select('authors.id', 'authors.name')->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'sources' => 'sometimes|array',
            'sources.*' => 'exists:sources,id',
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
            'authors' => 'sometimes|array',
            'authors.*' => 'exists:authors,id',
        ]);

        if ($request->has('sources')) {
            $user->preferredSources()->sync($validated['sources']);
        }
        if ($request->has('categories')) {
            $user->preferredCategories()->sync($validated['categories']);
        }
        if ($request->has('authors')) {
            $user->preferredAuthors()->sync($validated['authors']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully.'
        ]);
    }
}
