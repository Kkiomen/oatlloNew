<?php

declare(strict_types=1);

namespace App\Api;

use Illuminate\Support\Facades\Http;

class UnsplashApi
{
    const API_SEARCH_IMAGES_URL = 'https://api.unsplash.com/search/photos/';

    public static function getImages(string $query): array
    {
        $url = self::API_SEARCH_IMAGES_URL . '?query=' . $query .'&client_id=' . env('UNSPLASH_ACCESS_KEY');

        $result = Http::get($url);
        $result = $result->body();
        $result = json_decode($result, true);

        if(empty($result['results'])){
            return [];
        }

        $images = [];
        foreach ($result['results'] as $item){
            $images[] = [
                'url' => $item['urls']['regular'],
                'types' => [
                    'regular' => $item['urls']['regular'],
                    'small' => $item['urls']['small'],
                    'thumb' => $item['urls']['thumb'],
                ],
                'alt' => $item['alt_description']
            ];
        }

        return $images;
    }
}
