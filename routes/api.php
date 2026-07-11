<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AutoPublishController;
use App\Http\Controllers\Api\ArticleImportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Endpoint do automatycznej publikacji artykułów
Route::post('/auto-publish', [AutoPublishController::class, 'publishOldestUnpublished'])
    ->name('api.auto-publish');

// Import artykułów w formacie Markdown (dla lokalnego narzędzia / Claude).
// Autoryzacja: nagłówek "Authorization: Bearer <ARTICLE_API_TOKEN>".
Route::middleware('article.token')->prefix('articles')->group(function () {
    Route::get('/', [ArticleImportController::class, 'index'])->name('api.articles.index');
    Route::post('/', [ArticleImportController::class, 'store'])->name('api.articles.store');
    Route::get('/{slug}', [ArticleImportController::class, 'show'])->name('api.articles.show');
    Route::delete('/{slug}', [ArticleImportController::class, 'destroy'])->name('api.articles.destroy');
});
