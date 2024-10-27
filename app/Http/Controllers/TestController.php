<?php

namespace App\Http\Controllers;

use App\Services\Generator\GeneratorArticleService;
use App\Services\ImageService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request, GeneratorArticleService $generatorArticleService, ImageService $imageService)
    {

    }

}
