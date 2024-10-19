<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article;
use App\Prompts\GenerateArticleContentPrompt;
use App\Prompts\GenerateArticleDecorateTextPrompt;
use App\Services\CmsPageService;
use App\Services\Helper\GeneratorHelper;
use App\Services\SitemapService;
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
        $type = 'create';
        $article = Article::where('type', $type)->first();

        if($article === null) {
            $article = Article::create([
                'name' => 'Nazwa artykułu',
                'slug' => 'nowy-artykul',
                'is_published' => false,
                'json_content' => ArticleContentBuilder::getCreateContent(),
                'type' => $type,
                'view_content' => null
            ]);
        }

        return $article;
    }

    public function getOrCreateArticleInModeAiGenerate(): Article
    {
        $type = 'ai_generator';
        $article = Article::where('type', $type)->first();

        if($article === null) {
            $article = Article::create([
                'name' => 'Artykuł wygenerowany przy pomocy AI',
                'slug' => 'new-ai-article',
                'is_published' => false,
                'json_content' => ArticleContentBuilder::getCreateContent(),
                'type' => $type,
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
            if(!empty($article->view_content['basic_website_structure_image_img_file'])){
                $article->image = $article->view_content['basic_website_structure_image_img_file'];
            }
            if(!empty($article->view_content['basic_article_information_description'])){
                $article->short_description = $article->view_content['basic_article_information_description'];
            }
            if(isset($article->view_content['basic_website_structure_is_published'])){
                $article->is_published = boolval($article->view_content['basic_website_structure_is_published']);
            }
            $article->save();
        }

        SitemapService::generateSitemap();

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

                $result[$element['key'].'_img_file'] = $currentImage;
                $result[$element['key'].'_img_alt'] = !empty($element['alt']) ? $element['alt'] : '';
                $result[$element['key'].'_img_has_link'] = !empty($element['hasLink']) ? $element['hasLink'] : '';
                $result[$element['key'].'_img_link_redirect'] = !empty($element['linkRedirect']) ? $element['linkRedirect'] : '';
            }

            if($element['type'] == 'button'){

                $url = !empty($element['link']) ? $element['link'] : '#';
                $pattern = "/ route\('(.+?)'\)/";
                if (preg_match($pattern, $url, $matches)) {
                    $url = route($matches[1]);
                }

                $result[$element['key'].'_btn_link'] = $url;
                $result[$element['key'].'_btn_text'] = $element['text'];
                $result[$element['key'].'_btn_has_link'] = !empty($element['hasLink']) ? $element['hasLink'] : '';
                $result[$element['key'].'_btn_link_redirect'] = !empty($element['linkRedirect']) ? $element['linkRedirect'] : '';
            }

            if($element['type'] == 'link'){

                $url = !empty($element['href']) ? $element['href'] : '#';
                $pattern = "/ route\('(.+?)'\)/";
                if (preg_match($pattern, $url, $matches)) {
                    $url = route($matches[1]);
                }

                $result[$element['key'].'_link'] = $url;
            }
        }
    }

    public function generateContentByKey(int $articleId, string $schemaId): array
    {
        $countErrors = 0;

        do{
            $errors = false;
            $article = Article::find($articleId);

            try{
                $nextKey = null;
                $heading = null;
                $content = null;
                $isGenerated = false;

                if ($article->schema_ai !== null) {
                    $schemaContents = $article->schema_ai;
                    $founded = false;
                    $i = 0;
                    foreach ($schemaContents as &$schema) {
                        if($founded){
                            $nextKey = $schema['id'];
                            break;
                        }

                        if ($schema['id'] === $schemaId) {
                            $founded = true;
                            $content = $schema['content'];
                            $heading = $schema['heading'];
                            $isGenerated = $schema['isGenerated'] ?? false;
                            $schema['isGenerated'] = true;

                        }

                        $i++;
                    }

                    if($founded && $isGenerated === false){
                        // Przygotowanie prompta
                        $lastContent = ($i == 1) ? null : substr($article->contents[count($article->contents) - 1]['content'], -80);
                        $prompt = '- Tytuł artykułu: "'. $article->name .'"\n';
                        if($i > 1){
                            $prompt .= '- Ostatnie 40 znaków ostatnio wygenerowanej części: "'.  $lastContent .'"\n';
                        }
                        if($heading !== null && $content !== null){
                            $prompt .= '- O czym napisać: "'. $heading .'" ('. $content .') \n';
                        }
                        $prompt .= '- Aktualna część: '. $i . ' z ' . count($schemaContents);

                        // Generowanie treści
                        $content = GenerateArticleContentPrompt::generateContent($prompt);
                        $content = GenerateArticleDecorateTextPrompt::generateContent($content);
                        $content = str_replace(['```html', '```', '`html', '``', '`'], '', $content);

                        // Zapisanie treści
                        $currentContents = empty($article->contents) ? [] : $article->contents;
                        $currentContents[] = [
                            'type' => 'text',
                            'content' => $content,
                            'id' => '_' . GeneratorHelper::randomPassword(9)
                        ];

                        $article->contents = $currentContents;
                        $article->schema_ai = $schemaContents;
                        $article->type = 'normal';
                        $article->save();
                    }
                }

                if($nextKey === null){
                    $article->type = 'normal';
                    $article->save();
                }


            }catch (\Exception $exception){
                $errors = true;
                $countErrors++;
            }

        }while($errors && $countErrors < 3);

        return [
            'errors' => $countErrors,
            'currentKey' => $schemaId,
            'nextKey' => $nextKey,
        ];
    }
}
