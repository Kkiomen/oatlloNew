<?php

declare(strict_types=1);

namespace App\Services\Helper;

use App\Models\Article;

class LanguageHelper
{
    public static function getLanguagesConfiguration(): array
    {
        $languagesName = env('LANGUAGES');
        $languagesShort = env('LANGUAGES_SHORT');

        if(empty($languagesName) || empty($languagesShort)){
            return [];
        }

        $languagesName = explode('|', $languagesName);
        $languagesShort = explode('|', $languagesShort);

        $result = [];

        foreach ($languagesName as $key => $languageName) {
            if(!isset($languagesShort[$key])){
                continue;
            }

            $result[$languagesShort[$key]] = [
                'name' => $languageName,
                'short' => $languagesShort[$key]
            ];
        }

        return $result;
    }

    public static function getShortFromName(string $name): ?string
    {
        $languagesName = env('LANGUAGES');
        $languagesShort = env('LANGUAGES_SHORT');

        $languagesName = explode('|', $languagesName);
        $languagesShort = explode('|', $languagesShort);

        foreach ($languagesName as $key => $languageName) {
            if(!isset($languagesShort[$key])){
                continue;
            }

            if($languageName === $name){
                return $languagesShort[$key];
            }
        }

        return null;
    }

    public static function getNameFromShort(string $short): ?string
    {
        $languagesName = env('LANGUAGES');
        $languagesShort = env('LANGUAGES_SHORT');

        $languagesName = explode('|', $languagesName);
        $languagesShort = explode('|', $languagesShort);

        foreach ($languagesName as $key => $languageName) {
            if(!isset($languagesShort[$key])){
                continue;
            }

            if($languagesShort[$key] === $short){
                return $languageName;
            }
        }

        return null;
    }

    public static function prepareLanguagesForArticle(Article $article): array
    {
        $languages = self::getLanguagesConfiguration();
        $result = [];

        foreach ($languages as $short => $language) {
            if($article->language === $language['short']){
                continue;
            }

            $articleConnectedByLanguage = Article::where('connection_article_id', $article->id)
                                                ->where('language', $language['short'])
                                                ->first();

            if($articleConnectedByLanguage === null && $article->connection_article_id !== null){
                $articleConnectedByLanguage = Article::where('id', $article->connection_article_id)
                                                    ->where('language', $language['short'])
                                                    ->first();
            }

            $result[$short] = [
                'name' => $language['name'],
                'short' => $language['short'],
                'method' => $articleConnectedByLanguage ? 'redirectToUrl' : 'toGenerate',
                'url' => $articleConnectedByLanguage ? route('pages.edit', $articleConnectedByLanguage) : null
            ];
        }

        return $result;
    }
}
