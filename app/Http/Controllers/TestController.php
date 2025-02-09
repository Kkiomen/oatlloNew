<?php

namespace App\Http\Controllers;


use App\Magisterka\CodeReviewAnalyzerService;
use App\Magisterka\DocumentationFileLoader;
use App\Models\Tag;
use App\Services\Generator\InternalUrlsGenerator;
use App\Services\Generator\TagForArticleGenerator;
use App\Services\PracaMagisterska;
use Illuminate\Http\Request;

class TestController extends Controller
{
    const TASK = 'photos';
    const API_KEY = '6982ce64-7d13-4d2e-a23a-ba07ba2c8f45';

    const URL_POLIGON_VERIFY = 'https://poligon.aidevs.pl/verify';


    public function test(Request $reques, PracaMagisterska $pracaMagisterska)
    {
        $pracaMagisterska->codeReviewCodeFromFileVersionOne();

        $path = app_path('Magisterka/example_code_sa.txt');

        $fileCode = file_get_contents($path);
        $analyze = CodeReviewAnalyzerService::analyze($fileCode);

        dd(DocumentationFileLoader::loadAllDocByAnalyze($analyze));
        dd(DocumentationFileLoader::servicePresentationPut());
//        $pracaMagisterska->test();

//        InternalUrlsGenerator::generate();
    }
}
