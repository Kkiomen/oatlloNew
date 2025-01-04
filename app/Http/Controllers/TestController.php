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
    const TASK = 'photos';
    const API_KEY = '6982ce64-7d13-4d2e-a23a-ba07ba2c8f45';

    const URL_POLIGON_VERIFY = 'https://poligon.aidevs.pl/verify';


    public function test(Request $request, SitemapService $sitemapService)
    {
//        $tags = Tag::where('title_seo', null)->inRandomOrder()->get();
//
//        foreach ($tags as $tag) {
//            TagForArticleGenerator::createSeoInformation($tag);
//        }
        $sitemapService->generateSitemap();
    }

}
