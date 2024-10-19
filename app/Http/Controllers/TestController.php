<?php

namespace App\Http\Controllers;

use App\Api\PixabayApi;
use App\Api\UnsplashApi;
use App\Enums\OpenAiModel;
use App\Models\Article;
use App\Models\CmsPage;
use App\Prompts\GenerateArticleContentPrompt;
use App\Prompts\GenerateArticlePropertiesPrompt;
use App\Prompts\GenerateArticleQueryImagesPrompt;
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

        SitemapService::generateSitemap();

//        $queryImage = GenerateArticleQueryImagesPrompt::generateContent(userContent: 'Podstawy automatyki przemysłowej – Kluczowe informacje dla inżynierów');
//        $images = UnsplashApi::getImages($queryImage);
//        $images = '[{"url":"https:\/\/images.unsplash.com\/photo-1522794338816-ee3a17a00ae8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1522794338816-ee3a17a00ae8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1522794338816-ee3a17a00ae8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1522794338816-ee3a17a00ae8?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"eyeglasses and skeleton key on white book"},{"url":"https:\/\/images.unsplash.com\/photo-1584985429926-08867327d3a6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwyfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1584985429926-08867327d3a6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwyfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1584985429926-08867327d3a6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwyfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1584985429926-08867327d3a6?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwyfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"black and silver skeleton key"},{"url":"https:\/\/images.unsplash.com\/photo-1516937941344-00b4e0337589?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwzfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1516937941344-00b4e0337589?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwzfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1516937941344-00b4e0337589?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwzfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1516937941344-00b4e0337589?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwzfHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"factories with smoke under cloudy sky"},{"url":"https:\/\/images.unsplash.com\/photo-1513828646384-e4d8ec30d2bb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw0fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1513828646384-e4d8ec30d2bb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw0fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1513828646384-e4d8ec30d2bb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw0fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1513828646384-e4d8ec30d2bb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw0fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"gray industrial machine"},{"url":"https:\/\/images.unsplash.com\/photo-1469289759076-d1484757abc3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw1fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1469289759076-d1484757abc3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw1fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1469289759076-d1484757abc3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw1fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1469289759076-d1484757abc3?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw1fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"aerial photo of gray metal parts"},{"url":"https:\/\/images.unsplash.com\/photo-1512314889357-e157c22f938d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw2fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1512314889357-e157c22f938d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw2fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1512314889357-e157c22f938d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw2fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1512314889357-e157c22f938d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw2fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"photo of bulb artwork"},{"url":"https:\/\/images.unsplash.com\/photo-1551590192-8070a16d9f67?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw3fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1551590192-8070a16d9f67?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw3fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1551590192-8070a16d9f67?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw3fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1551590192-8070a16d9f67?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw3fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"information kiosk"},{"url":"https:\/\/images.unsplash.com\/photo-1550527882-b71dea5f8089?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw4fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1550527882-b71dea5f8089?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw4fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1550527882-b71dea5f8089?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw4fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1550527882-b71dea5f8089?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw4fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"black skeleton keys"},{"url":"https:\/\/images.unsplash.com\/photo-1503792243040-7ce7f5f06085?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw5fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1503792243040-7ce7f5f06085?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw5fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1503792243040-7ce7f5f06085?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw5fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1503792243040-7ce7f5f06085?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHw5fHwlMjJpbmR1c3RyaWFsJTIwYXV0b21hdGlvbiUyMGJhc2ljcyUyMGtleSUyMGluZm9ybWF0aW9uJTIwZW5naW5lZXJzJTIyfGVufDB8fHx8MTcyOTM0NDI0NHww&ixlib=rb-4.0.3&q=80&w=200"},"alt":"photo of key against black background"},{"url":"https:\/\/images.unsplash.com\/photo-1496247749665-49cf5b1022e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxMHx8JTIyaW5kdXN0cmlhbCUyMGF1dG9tYXRpb24lMjBiYXNpY3MlMjBrZXklMjBpbmZvcm1hdGlvbiUyMGVuZ2luZWVycyUyMnxlbnwwfHx8fDE3MjkzNDQyNDR8MA&ixlib=rb-4.0.3&q=80&w=1080","types":{"regular":"https:\/\/images.unsplash.com\/photo-1496247749665-49cf5b1022e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxMHx8JTIyaW5kdXN0cmlhbCUyMGF1dG9tYXRpb24lMjBiYXNpY3MlMjBrZXklMjBpbmZvcm1hdGlvbiUyMGVuZ2luZWVycyUyMnxlbnwwfHx8fDE3MjkzNDQyNDR8MA&ixlib=rb-4.0.3&q=80&w=1080","small":"https:\/\/images.unsplash.com\/photo-1496247749665-49cf5b1022e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxMHx8JTIyaW5kdXN0cmlhbCUyMGF1dG9tYXRpb24lMjBiYXNpY3MlMjBrZXklMjBpbmZvcm1hdGlvbiUyMGVuZ2luZWVycyUyMnxlbnwwfHx8fDE3MjkzNDQyNDR8MA&ixlib=rb-4.0.3&q=80&w=400","thumb":"https:\/\/images.unsplash.com\/photo-1496247749665-49cf5b1022e9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w2NjY1Nzl8MHwxfHNlYXJjaHwxMHx8JTIyaW5kdXN0cmlhbCUyMGF1dG9tYXRpb24lMjBiYXNpY3MlMjBrZXklMjBpbmZvcm1hdGlvbiUyMGVuZ2luZWVycyUyMnxlbnwwfHx8fDE3MjkzNDQyNDR8MA&ixlib=rb-4.0.3&q=80&w=200"},"alt":"black metal empty building"}]';
//        $images = json_decode($images, true);
        $article = Article::find(6);
        if(!empty($images)) {
            $image = $images[rand(0, count($images) - 1)];
            $fileContents = file_get_contents($image['url']);

            // Ścieżka, gdzie zapiszemy plik tymczasowo
            $tempFilePath = storage_path('app/temp_image.jpg');

            // Zapisanie pliku w systemie plików
            file_put_contents($tempFilePath, $fileContents);

            // Utworzenie obiektu UploadedFile
            $uploadedFile = new UploadedFile(
                $tempFilePath,
                'image.jpg', // Nazwa pliku
                mime_content_type($tempFilePath), // Typ MIME pliku
                null, // Rozmiar pliku - można pominąć, jeśli nie jest wymagane
                true // Czy plik był przesłany przez HTTP (ustawiamy na true)
            );
            $filePath = $imageService->uploadImage($uploadedFile);
            unlink($tempFilePath);


            $r = $articleService->updateKey($article, 'basic_website_structure_image'. '0001000file', $filePath);
            dd($filePath);
        }

//        dd(UnsplashApi::getImages('fruit benefits for employees'));
//        dd(PixabayApi::getImages('fruit benefits for employees'));
//        $result = OpenAI::chat()->create([
//            'model' => OpenAiModel::GPT4O_MINI->value,
//            'response_format' => [
//                'type' => 'json_object'
//            ],
//            'messages' => [
//                ['role' => 'system', 'content' => GenerateArticlePropertiesPrompt::getPrompt()],
//                ['role' => 'user', 'content' => 'Artykuł o przewodach półnapięciowych!'],
//            ],
//        ]);
//
//
//        dump($result->choices[0]->message->content);
//        $r = $result->choices[0]->message->content;
//        $d = json_decode($r, true);


//        $topic = "Konserwacja prewencyjna – jak zapobiegać przestojom w produkcji";
//        $countOfArticleParts = 3;
//        $contentArticle = [];
//        for ($i = 1; $i <= $countOfArticleParts; $i++){
//            $prompt = '- Tytuł artykułu: "'. $topic .'"\n';
//            if($i > 1){
//                $prompt .= '- Ostatnie 40 znaków ostatnio wygenerowanej części: "'.  substr($contentArticle[$i-2], -40) .'"\n';
//            }
//            $prompt .= '- Aktualna część: '. $i . ' z ' . $countOfArticleParts;
//
//            $contentArticle[] = GenerateArticleContentPrompt::generateContent($prompt);
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

    function random_password( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    }

/**
 * 'response_format' => [
 * 'type' => 'json_object'
 * ],
 */
}
