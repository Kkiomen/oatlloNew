<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }

    }
}
