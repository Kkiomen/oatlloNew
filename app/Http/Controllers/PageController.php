<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::all();
        return view('pages.index', compact('pages'));
    }

    public function create()
    {
        return view('pages.create');
    }

    public function store(Request $request)
    {
        $page = Page::create($request->only(['name', 'slug']));
        $slug = $request->get('slug');
        $page->slug = Str::slug(str_replace('/', '-', strtolower($slug)));
        $page->is_published = $request->get('is_published') ? true : false;
        $page->save();
        return redirect()->route('pages.edit', $page->id);
    }

    public function edit(Page $page)
    {
        return view('pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $page->update($request->only(['name', 'slug']));
        $slug = $request->get('slug');
        $page->slug = Str::slug(str_replace('/', '-', strtolower($slug)));
        $page->is_published = $request->get('is_published') ? true : false;
        $page->save();
        return Redirect::back();
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('pages.index');
    }
}
