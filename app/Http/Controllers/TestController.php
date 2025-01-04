<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use App\Prompts\Abstract\Enums\OpenApiResultType;
use App\Prompts\GenerateArticleBasicInformationToOtherLanguagePrompt;
use App\Prompts\GenerateArticleContentToOtherLanguagePrompt;
use App\Services\Article\ArticleService;
use App\Services\Generator\GeneratorArticleService;
use App\Services\Generator\TagForArticleGenerator;
use App\Services\Helper\LanguageHelper;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestController extends Controller
{
    public function test(Request $request, ArticleService $articleService, ImageService $imageService)
    {

        $tags = Tag::where('title_seo', null)->inRandomOrder()->get();

        foreach ($tags as $tag) {
            TagForArticleGenerator::createSeoInformation($tag);
        }
    }

}
