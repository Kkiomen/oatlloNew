<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleContent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function create()
    {
        return view('article.create');
    }

    // Store a new article
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:articles,slug',
            'language' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'open_graph_title' => 'nullable|string|max:255',
            'open_graph_description' => 'nullable|string|max:255',
            'open_graph_image' => 'nullable|url',
            'is_published' => 'boolean',
            'contents' => 'nullable|array',
            'contents.*.type' => 'required|string|in:text,image',
            'contents.*.content' => 'required|string',
            'contents.*.order_column' => 'required|integer',
        ]);

        // Create the new article
        $article = Article::create([
            'title' => $validated['title'],
            'slug' => Str::slug(str_replace('/', '-', strtolower($validated['slug']))),
            'language' => $validated['language'],
            'seo_title' => $validated['seo_title'],
            'seo_description' => $validated['seo_description'],
            'open_graph_title' => $validated['open_graph_title'],
            'open_graph_description' => $validated['open_graph_description'],
            'open_graph_image' => $validated['open_graph_image'],
            'is_published' => $validated['is_published'] ?? false,
            'published_at' => $validated['is_published'] ? now() : null,
        ]);

        // Save the content blocks
        if (isset($validated['contents'])) {
            foreach ($validated['contents'] as $content) {
                ArticleContent::create([
                    'article_id' => $article->id,
                    'type' => $content['type'],
                    'content' => $content['content'],
                    'order_column' => $content['order_column'],
                ]);
            }
        }

        return redirect()->route('articles.list')->with('success', 'Article created successfully.');
    }

    public function list(Request $request): View
    {
        // Pobieranie wartości wyszukiwania z zapytania
        $search = $request->input('search');

        // Filtracja artykułów na podstawie tytułu, jeśli pole wyszukiwania nie jest puste
        $articles = Article::when($search, function ($query, $search) {
            return $query->where('title', 'like', '%' . $search . '%');
        })->paginate(4);

        return view('article.list', [
            'articles' => $articles,
            'search' => $search,
        ]);
    }

    public function edit($id): View
    {
        $article = Article::with('contents')->findOrFail($id);

        return view('article.edit', compact('article'));
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        // Validate article fields
        $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string',
            'language' => 'nullable|string',
            'seo_title' => 'nullable|string',
            'seo_description' => 'nullable|string',
            'open_graph_title' => 'nullable|string',
            'open_graph_description' => 'nullable|string',
            'open_graph_image' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        // Update article fields
        $article->update($request->only([
            'title',
            'slug',
            'language',
            'seo_title',
            'seo_description',
            'open_graph_title',
            'open_graph_description',
            'open_graph_image',
            'is_published',
        ]));

        // Update publication date if published
        if ($article->is_published && !$article->published_at) {
            $article->published_at = now();
            $article->save();
        }

        // Handle ArticleContent updates
        $contents = $request->input('contents', []);
        foreach ($contents as $contentData) {
            ArticleContent::updateOrCreate(
                ['id' => $contentData['id'] ?? null, 'article_id' => $article->id],
                [
                    'type' => $contentData['type'],
                    'content' => $contentData['content'],
                    'order_column' => $contentData['order_column'],
                ]
            );
        }

        return redirect()->route('article.edit', $article->id)->with('success', 'Article updated successfully.');
    }
}
