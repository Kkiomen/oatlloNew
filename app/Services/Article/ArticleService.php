<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article;
use App\Services\CmsPageService;

class ArticleService
{
    public function __construct(
        private readonly CmsPageService $cmsPageService
    ){}

    /**
     * Zwraca artykuł w trybie tworzenia, jeśli istnieje, bądź go tworzy i zwraca
     * @return Article
     */
    public function getOrCreateArticleInModeCreate(): Article
    {
        $article = Article::where('type', 'create')->first();

        if($article === null) {
            $article = Article::create([
                'name' => 'Nazwa artykułu',
                'slug' => 'nowy-artykul',
                'is_published' => false,
                'json_content' => ArticleContentBuilder::getCreateContent(),
                'type' => 'create',
                'view_content' => null
            ]);
        }

        return $article;
    }

    public function updateKey(Article $article, string $key, string $value, bool $saveArticleOnFinish = true): ?array
    {
        $jsonContents = $article->json_content;

        $splitKey = $this->cmsPageService->splitKey($key);
        if(empty($splitKey)){
            return null;
        }

        $info = [
            'changes' => false,
            'skip' => false
        ];

        foreach ($jsonContents as &$element) {
            if ($info['skip']) {
                break;
            }

            $this->changeJson($element, $splitKey, $value, $info);
        }

        if($info['changes'] && $saveArticleOnFinish){
            $article->json_content = $jsonContents;
            $article->save();
        }

        return $info;
    }

    /**
     * Wyszukuje w tablicy JSON odpowiedni klucz i podmienia wartość
     * @param array $element
     * @param array|null $splitKey
     * @param string $value
     * @param array $info
     * @return void
     */
    protected function changeJson(array &$element, ?array $splitKey, string $value, array &$info): void
    {
        if (is_array($element) && isset($element[0])) {
            foreach ($element as &$content) {
                if ($info['skip']) {
                    break;
                }

                $this->changeJson($content, $splitKey, $value, $info);
            }
        }

        if (array_key_exists('content', $element) && is_array($element['content']) && isset($element['content'][0])) {
            foreach ($element['content'] as &$content) {
                if ($info['skip']) {
                    break;
                }

                $this->changeJson($content, $splitKey, $value, $info);
            }
        }


        // Jeśli jest normalnym elementem
        if (isset($element['key']) && isset($element['type']) && !isset($element['content'])) {
            if ($element['key'] === $splitKey['key']) {
                $this->cmsPageService->changeJsonContent($element, $splitKey, $value, $info);
                $info['skip'] = true;
            }
        }
    }
}
