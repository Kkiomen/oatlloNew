<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function page(string $slug)
    {
        $page = Page::with('sections.contents')->where('slug', $slug)->first();
        return view('home.index', compact('page'));
    }
}
