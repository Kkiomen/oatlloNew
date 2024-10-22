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
use App\Services\Generator\GeneratorArticleService;
use App\Services\ImageService;
use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use OpenAI\Laravel\Facades\OpenAI;

class TestController extends Controller
{
    public function test(Request $request, GeneratorArticleService $generatorArticleService, ImageService $imageService)
    {
//        $generatorArticleService->generateContentByKey('32', '_Fn6bMuzmT');
        $title = 'Dlaczego występują błędy podczas programowania?';
        $queryImage = GenerateArticleQueryImagesPrompt::generateContent(userContent: $title);
        $images = UnsplashApi::getImages($queryImage);

        dd($queryImage, $images);
        $imagePath = $imageService->generateImageByQuery($queryImage);
//        $article = Article::find(16);
//        $key = '_lwcWfE3Tx';
//        $result = $generatorArticleService->generateContentByKey($article->id, $key);
//        dd($result);

//        $topic = 'Jak stworzyć prostą stronę internetową?';
//        $generatorArticleService->createArticle($topic);
//        $generatorArticleService->generateBasicInformation();
//        $generatorArticleService->generateContentByKey(15, '_25QEGeh7N');
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
