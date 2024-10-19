<?php

declare(strict_types=1);

namespace App\Api;

use Illuminate\Support\Facades\Http;

class PixabayApi
{
    const API_SEARCH_IMAGES_URL = 'https://pixabay.com/api/';

    public static function getImages(string $query): array
    {
        $query = str_replace([' ', '=', '-','_'], '+', strtolower($query));

        $url = self::API_SEARCH_IMAGES_URL . '?q=' . $query .'&key=' . env('PIXABAY_ACCESS_KEY') . '&image_type=photo';

        $result = Http::get($url);
        $result = $result->body();
        $result = json_decode($result, true);

        if(empty($result['hits'])){
            return [];
        }

        $images = [];
        foreach ($result['hits'] as $item){
            $images[] = [
                'url' => $item['webformatURL'],
                'types' => [
                    'regular' => $item['largeImageURL'],
                    'small' => $item['webformatURL'],
                    'thumb' => $item['previewURL'],
                ],
                'alt' => $item['tags']
            ];
        }

        return $images;
    }
}
