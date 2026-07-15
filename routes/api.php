<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AutoPublishController;
use App\Http\Controllers\Api\CronController;
use App\Http\Controllers\Api\SocialMediaController;

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

// "Cron tick" uderzany cyklicznie (np. co godzinę z n8n). Publikuje zaplanowane
// artykuły (których published_at już minął) i regeneruje sitemap. Publiczny GET.
// Publikacja na Instagrama dokłada się do tego samego ticka, ale WYŁĄCZNIE dla
// wołającego z tokenem (SOCIAL_CRON_TOKEN) – patrz CronController::runSocial().
Route::get('/cron', [CronController::class, 'run'])->name('api.cron');

// Mini-cloud na grafiki social: przyjmuje PNG/MP4 wyrenderowane lokalnie i hostuje
// je pod publicznym URL-em, bo Instagram (przez Zernio) nie przyjmuje plików, tylko
// publiczne linki HTTPS – a produkcja tych grafik nie ma i nie umie zrobić.
//
// Trasa jest rejestrowana WARUNKOWO: bez SOCIAL_MEDIA_TOKEN nie ma jej w routingu
// w ogóle (ten sam wzorzec co podgląd social za SOCIAL_PREVIEW). Endpoint zapisuje
// pliki do katalogu serwowanego publicznie, więc "wyłączony" ma znaczyć "nie
// istnieje", a nie "istnieje i broni się sam".
if (trim((string) config('social.media.token')) !== '') {
    Route::post('/social/media/{slug}', [SocialMediaController::class, 'store'])
        ->name('api.social.media');
}
