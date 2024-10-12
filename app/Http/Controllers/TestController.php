<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $c = CmsPage::first();
        dd($c->to_view);


        $jsonFilePath = database_path('json/example_page.json');

        // Odczytaj zawartość pliku
        $jsonData = file_get_contents($jsonFilePath);
        $data = json_decode($jsonData, true);


        $listData = [];

        foreach($data as $section){
            $this->addElementsFromContentElement($section, $listData);
        }
        dd($data, $listData);
    }


}
