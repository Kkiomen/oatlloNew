<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request)
    {
        // Pobieranie wartości wyszukiwania z zapytania
        $search = $request->input('search');

        // Filtracja artykułów na podstawie tytułu, jeśli pole wyszukiwania nie jest puste
        $articles = Article::when($search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%')->orWhere('slug', 'like', '%' . $search . '%');
        })->orderBy('created_at', 'desc')->paginate(4);

        return view('pages.index', [
            'pages' => $articles,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('pages.create');
    }

    public function store(Request $request)
    {
        $page = Article::create($request->only(['name', 'slug']));
        $slug = $request->get('slug');
        $page->slug = Str::slug(str_replace('/', '-', strtolower($slug)));
        $page->is_published = $request->get('is_published') ? true : false;
        $page->save();
        return redirect()->route('pages.edit', $page->id);
    }

    public function edit(Article $page)
    {
        return view('pages.edit', compact('page'));
    }

    public function update(Request $request, Article $page)
    {
        $page->update($request->only(['name', 'slug']));
        $slug = $request->get('slug');
        $page->slug = Str::slug(str_replace('/', '-', strtolower($slug)));
        $page->is_published = $request->get('is_published') ? true : false;
        $page->save();
        return Redirect::back();
    }

    public function destroy(Article $page)
    {
        $page->delete();
        return redirect()->route('pages.index');
    }
}
