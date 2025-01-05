<?php

namespace App\Http\Controllers;


use App\Models\Tag;
use App\Services\Generator\InternalUrlsGenerator;
use App\Services\Generator\TagForArticleGenerator;
use Illuminate\Http\Request;

class TestController extends Controller
{
    const TASK = 'photos';
    const API_KEY = '6982ce64-7d13-4d2e-a23a-ba07ba2c8f45';

    const URL_POLIGON_VERIFY = 'https://poligon.aidevs.pl/verify';


    public function test(Request $request)
    {
        InternalUrlsGenerator::generate();
    }
}
