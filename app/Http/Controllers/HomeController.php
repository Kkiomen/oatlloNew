<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\Page;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $info = CmsPage::find(1);

        return view('welcome', $info->to_view);
    }



    public function page(string $slug)
    {
        $page = Page::with('sections.contents')->where('slug', $slug)->first();
        return view('home.index', compact('page'));
    }
}
