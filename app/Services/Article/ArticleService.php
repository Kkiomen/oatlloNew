<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article;
use App\Services\CmsPageService;
use Illuminate\Support\Str;

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
            $article->view_content = $this->prepareViewContentForArticle($jsonContents);

            if(!empty($article->view_content['basic_article_information_category'])){
                $article->category_id = intval($article->view_content['basic_article_information_category']);
            }
            if(!empty($article->view_content['basic_article_information_title'])){
                $article->name = $article->view_content['basic_article_information_title'];
            }
            if(!empty($article->view_content['basic_article_information_slug'])){
                $article->slug = Str::slug($article->view_content['basic_article_information_slug']);
            }
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

    /**
     * Przygotowuje tablice kluczy dla widoku
     * @param array $jsonData
     * @return array
     */
    protected function prepareViewContentForArticle(array $jsonData): array
    {
        $result = [];
        foreach ($jsonData as $element) {
            $this->prepareViewContentForElement($element, $result);
        }

        return $result;
    }


    /**
     * Przeszukuje i dopisuje klucze (stworzona do celów rekurencyjnych)
     * @param array $element
     * @param array $result
     * @return void
     */
    public function prepareViewContentForElement(array $element, array &$result): void
    {
        if (is_array($element) && isset($element[0])) {
            foreach ($element as &$content) {
                $this->prepareViewContentForElement($content, $result);
            }
        }

        if (array_key_exists('content', $element) && is_array($element['content']) && isset($element['content'][0])) {
            foreach ($element['content'] as &$content) {
                $this->prepareViewContentForElement($content, $result);
            }
        }


        // Jeśli jest normalnym elementem
        if (isset($element['key']) && isset($element['type']) && !isset($element['content'])) {

            if($element['type'] == 'string' || $element['type'] == 'textarea' || $element['type'] == 'text' || $element['type'] == 'boolean' || $element['type'] == 'category'){
                $result[$element['key']] = empty($element['value']) ? '' : $element['value'];
            }

            if($element['type'] == 'image'){

                $currentImage = empty($element['file']) ? 'storage/uploads/empty_image.jpg' : $element['file'];
                $pattern = "/asset\('(.+?)'\)/";
                if (preg_match($pattern, $currentImage, $matches)) {
                    $currentImage = $matches[1];
                }
                $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);

                $listData[$element['key'].'_img_file'] = $currentImage;
                $listData[$element['key'].'_img_alt'] = !empty($element['alt']) ? $element['alt'] : '';
                $listData[$element['key'].'_img_has_link'] = !empty($element['hasLink']) ? $element['hasLink'] : '';
                $listData[$element['key'].'_img_link_redirect'] = !empty($element['linkRedirect']) ? $element['linkRedirect'] : '';
            }

            if($element['type'] == 'button'){

                $url = !empty($element['link']) ? $element['link'] : '#';
                $pattern = "/ route\('(.+?)'\)/";
                if (preg_match($pattern, $url, $matches)) {
                    $url = route($matches[1]);
                }

                $listData[$element['key'].'_btn_link'] = $url;
                $listData[$element['key'].'_btn_text'] = $element['text'];
                $listData[$element['key'].'_btn_has_link'] = !empty($element['hasLink']) ? $element['hasLink'] : '';
                $listData[$element['key'].'_btn_link_redirect'] = !empty($element['linkRedirect']) ? $element['linkRedirect'] : '';
            }

            if($element['type'] == 'link'){

                $url = !empty($element['href']) ? $element['href'] : '#';
                $pattern = "/ route\('(.+?)'\)/";
                if (preg_match($pattern, $url, $matches)) {
                    $url = route($matches[1]);
                }

                $listData[$element['key'].'_link'] = $url;
            }
        }
    }
}
