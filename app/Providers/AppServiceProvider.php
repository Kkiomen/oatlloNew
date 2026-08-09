<?php

namespace App\Providers;

use App\Services\Clip\Tts\ElevenLabsTtsProvider;
use App\Services\Clip\Tts\MockTtsProvider;
use App\Services\Clip\Tts\TtsProvider;
use App\Services\Social\Publisher\SocialPublisher;
use App\Services\Social\Rasterizer\HeadlessBrowserRasterizer;
use App\Services\Social\Rasterizer\SocialRasterizer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Moduł social media. Rasteryzator i publisher siedzą za interfejsami:
        // testy podmieniają rasteryzator na NullRasterizer (żeby nie odpalać
        // przeglądarki w CI), a publisher jest szwem pod Instagram Graph API.
        $this->app->bind(SocialRasterizer::class, HeadlessBrowserRasterizer::class);

        $this->app->bind(
            SocialPublisher::class,
            fn ($app) => $app->make((string) config('social.publisher')),
        );

        // Moduł clip (narrowane wideo). Provider TTS wybierany driverem: `mock`
        // (cisza — pipeline renderuje bez klucza) albo `elevenlabs` (realny głos).
        // Podmiana driver'a nie rusza reszty pipeline'u — o to chodzi w interfejsie.
        $this->app->bind(TtsProvider::class, function ($app) {
            return match ((string) config('clip.tts.driver', 'mock')) {
                'elevenlabs' => $app->make(ElevenLabsTtsProvider::class),
                default      => $app->make(MockTtsProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forceCanonicalUrlRoot();
    }

    /**
     * Przybija korzeń generowanych URL-i do kanonicznego hosta.
     *
     * MINA, którą to zamyka: `route()` bierze host z BIEŻĄCEGO ŻĄDANIA, a sparsowane
     * artykuły `.md` są cache'owane razem z polem `image`, które powstaje z
     * `route('article.cover', ...)`. Wystarczyło JEDNO wejście robota na
     * `www.oatllo.com`, żeby do cache'u wpadła okładka na hoście `www.` - i tak
     * właśnie w sitemapie wylądowało 14 `<image:loc>` na `https://www.oatllo.com`
     * przy `<loc>` na `https://oatllo.com`. Sitemap generowany z CLI czyta ten sam
     * cache, więc zatrucie przeżywa i wychodzi na zewnątrz.
     *
     * Środowisko NIE WYSTĘPUJE w tym warunku i to jest celowe. Poprzednia wersja tej
     * metody (samo `forceScheme`) wisiała na `environment('production')` i nie odpaliła
     * się ani razu; pierwsza poprawka z 09.08 wymieniła to na bail-out
     * `environment('local','testing')` i **po wdrożeniu na produkcję dalej nie działała**,
     * bo `APP_ENV` na tym serwerze najwyraźniej nie jest tym, czym się wydaje.
     * Pytamy więc o TOŻSAMOŚĆ WDROŻENIA: czy `APP_URL` wskazuje na kanoniczną domenę
     * (lub jej wariant `www.`). Lokalny `http://localhost` do rodziny nie należy, więc
     * dev jest nietknięty - i jest to ochrona, której żaden wpis w `.env` nie wyłączy
     * przez przypadek.
     */
    private function forceCanonicalUrlRoot(): void
    {
        $canonicalHost = strtolower(trim((string) config('app.canonical_host')));

        if ($canonicalHost === '') {
            return;
        }

        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($appHost !== $canonicalHost && $appHost !== 'www.' . $canonicalHost) {
            return;
        }

        URL::forceRootUrl('https://' . $canonicalHost);
        URL::forceScheme('https');
    }
}
