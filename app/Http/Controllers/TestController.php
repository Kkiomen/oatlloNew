<?php

namespace App\Http\Controllers;

use App\Api\PixabayApi;
use App\Api\UnsplashApi;
use App\Enums\OpenAiModel;
use App\Models\Article;
use App\Models\CmsPage;
use App\Prompts\Abstract\Enums\OpenApiResultType;
use App\Prompts\GenerateArticleContentPrompt;
use App\Prompts\GenerateArticleDecorateTextPrompt;
use App\Prompts\GenerateArticlePropertiesPrompt;
use App\Prompts\GenerateArticleQueryImagesPrompt;
use App\Prompts\GenerateConspectusArticlePrompt;
use App\Services\Article\ArticleService;
use App\Services\ImageService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use OpenAI\Laravel\Facades\OpenAI;

class TestController extends Controller
{
    public function test(Request $request, ArticleService $articleService, ImageService $imageService)
    {
        $article = Article::find(9);
        $key = '_W2S4Xc3Lt';

        $nextKey = null;
        $heading = null;
        $content = null;

        if ($article->schema_ai !== null) {
            $schemaContents = $article->schema_ai;
            $founded = false;
            $i = 0;
            foreach ($schemaContents as &$schema) {
                if($founded){
                    $nextKey = $schema['id'];
                    break;
                }

                if ($schema['id'] === $key) {
                    $founded = true;
                    $content = $schema['content'];
                    $heading = $schema['heading'];
                    $schema['isGenerated'] = true;

                }

                $i++;
            }

            if($founded){
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
                $content = str_replace(['``','```', '```html'], '', $content);


                // Zapisanie treści
                $currentContents = empty($article->contents) ? [] : $article->contents;
                $currentContents[] = [
                    'type' => 'text',
                    'content' => $content,
                    'id' => '_'.$this->random_password(9)
                ];

                $article->contents = $currentContents;
                $article->schema_ai = $schemaContents;
                $article->save();
            }
        }


//        $topic = 'Sztuczna inteligencja w automatyce – czy maszyny mogą myśleć?';
//
//        $synopsisResult = GenerateConspectusArticlePrompt::generateContent(userContent: $topic, resultType: OpenApiResultType::JSON_OBJECT);
//        $synopsis = json_decode($synopsisResult, true)['outline'];
//
//
//        $countOfArticleParts = count($synopsis);
//        $contentArticle = [];
//        $i = 1;
//        foreach ($synopsis as $element){
//            $prompt = '- Tytuł artykułu: "'. $topic .'"\n';
//            if($i > 1){
//                $prompt .= '- Ostatnie 40 znaków ostatnio wygenerowanej części: "'.  substr($contentArticle[$i-2], -40) .'"\n';
//            }
//            $prompt = '- O czym napisać: "'. $element['heading'] .'" ('. $element['content'] .') \n';
//            $prompt .= '- Aktualna część: '. $i . ' z ' . $countOfArticleParts;
//
//            $content = GenerateArticleContentPrompt::generateContent($prompt);
//            $content = GenerateArticleDecorateTextPrompt::generateContent($content);
//            $content = str_replace(['``','```', '```html'], '', $content);
//
//            $contentArticle[] = $content;
//            $i++;
//        }
//
//        $content = [];
//        foreach ($contentArticle as $item){
//            $content[] = [
//                'type' => 'text',
//                'content' => $item,
//                'id' => '_'.$this->random_password(9)
//            ];
//        }
//        dump($contentArticle, $content, json_encode($content));
    }

    function random_password($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    /**
     * 'response_format' => [
     * 'type' => 'json_object'
     * ],
     */
}
