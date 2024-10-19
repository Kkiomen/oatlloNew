<?php

namespace App\Http\Controllers;

use App\Api\UnsplashApi;
use App\Models\Article;
use App\Models\Category;
use App\Models\CmsPage;
use App\Prompts\Abstract\Enums\OpenApiResultType;
use App\Prompts\GenerateArticlePropertiesPrompt;
use App\Prompts\GenerateArticleQueryImagesPrompt;
use App\Services\Article\ArticleService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request)
    {
        // Pobieranie wartości wyszukiwania z zapytania
        $search = $request->input('search');

        // Filtracja artykułów na podstawie tytułu, jeśli pole wyszukiwania nie jest puste
        $articles = Article::when($search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%')->orWhere('slug', 'like', '%' . $search . '%');
        })->orderBy('created_at', 'desc')->where('type', 'normal')->paginate(10);

        return view('pages.index', [
            'pages' => $articles,
            'search' => $search,
        ]);
    }

    public function createMethods()
    {
        return view('pages.create-methods');
    }

    public function create(Request $request, ArticleService $articleService)
    {
        $article = $articleService->getOrCreateArticleInModeCreate();

        return view('pages.create', [
            'contents' => $article->json_content,
            'article' => $article,
        ]);
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

    public function edit(int $page)
    {
        $article = Article::findOrFail($page);

        return view('pages.create', [
            'contents' => $article->json_content,
            'article' => $article,
        ]);
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

    public function updateArticleKey(Request $request, ?Article $article, ArticleService $articleService): JsonResponse
    {
        if($article === null) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        $result = null;

        foreach ($request->all() as $key => $value) {
            $result = $articleService->updateKey($article, $key, $value);
        }

        return response()->json(['success' => 'Key updated','data' => $request->all(), 'articleId' => $article->id, 'result' => $result]);
    }

    public function updateImage(Request $request, Article $article, ImageService $imageService, ArticleService $articleService): JsonResponse
    {
        $data = $request->all();

        // Przechowywanie obrazu
        $file = $request->file('file');
        if ($file) {
            $filePath = $imageService->uploadImage($file);

            if($filePath == null){
                return response()->json(['status' => 'error']);
            }

            // Update the CMS with the WebP file path
            $articleService->updateKey($article, $data['key'] . '0001000file', $filePath);

            return response()->json(['filePath' => asset($filePath)]);
        }

        return response()->json(['status' => 'error']);
    }

    public function saveContents(Request $request, Article $article)
    {
        $contents = $request->input('contents');
        $article->contents = $contents;
        $article->type = 'normal';
        $article->save();

        return response()->json(['status' => 'success']);
    }

    public function saveContentsImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $url = Storage::url($path);
            return response()->json(['url' => asset($url)]);
        }
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    // =============== GENEROWANIE AI ================
    public function createAi(Request $request): View
    {
        return view('pages.create-ai');
    }

    public function createArticle(Request $request, ArticleService $articleService): JsonResponse
    {
        $article = $articleService->getOrCreateArticleInModeAiGenerate();
        $article->ai_content = $request->input('about');
        $article->save();

        return response()->json(['status' => 'success']);
    }

    public function generateBasicInfo(Request $request, ArticleService $articleService, ImageService $imageService): JsonResponse
    {
        // Get or create article
        $article = $articleService->getOrCreateArticleInModeAiGenerate();

        // Generate basic info
        $content = GenerateArticlePropertiesPrompt::generateContent(
            userContent: $article->ai_content,
            resultType: OpenApiResultType::JSON_OBJECT
        );

        $content = json_decode($content, true);
        foreach ($content as $key => $value) {
            $result = $articleService->updateKey($article, $key .'0001000', $value);
        }

        // Generate image
        $queryImage = GenerateArticleQueryImagesPrompt::generateContent(userContent: $article->name);
        $imagePath = $imageService->generateImageByQuery($queryImage);
        if(!empty($imagePath)){
            $articleService->updateKey($article, 'basic_website_structure_image'. '0001000file', $imagePath);
        }

        return response()->json(['status' => 'success']);
    }

    public function generateContent(Request $request, ArticleService $articleService)
    {
        $article = $articleService->getOrCreateArticleInModeAiGenerate();
        $article->type = 'normal';
        $article->save();


        // TODO: Implement the logic to generate content
        return response()->json(['status' => 'success']);
    }
}
