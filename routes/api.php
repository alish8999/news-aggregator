<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PreferenceController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public endpoint for searching/filtering
Route::get('/articles', [ArticleController::class, 'index']);

// Public endpoints for getting available options
Route::get('/sources', [PreferenceController::class, 'getSources']);
Route::get('/categories', [PreferenceController::class, 'getCategories']);
Route::get('/authors', [PreferenceController::class, 'getAuthors']);

// Health check
Route::get('/health', [HealthController::class, 'check']);

// Private endpoints for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // Personalized feed
    Route::get('articles/user/feed', [ArticleController::class, 'userFeed']);

    // User preferences - now handled by PreferenceController
    Route::get('/user/preferences', [PreferenceController::class, 'getPreferences']);
    Route::get('/user/sources', [PreferenceController::class, 'getSources']);
    Route::get('/user/categories', [PreferenceController::class, 'getCategories']);
    Route::get('/user/authors', [PreferenceController::class, 'getAuthors']);
    Route::post('/user/preferences', [PreferenceController::class, 'updatePreferences']);
});
