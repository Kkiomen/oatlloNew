<?php

namespace App\Http\Controllers;

use App\Mail\InformationContact;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Article;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use App\Models\InstagramPost;
use App\Models\Tag;
use App\Models\TagArticle;
use App\Services\Article\MarkdownArticleRepository;
use App\Services\Course\CourseHelper;
use App\Services\Course\MarkdownCourseRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $defaultLangue = env('APP_LOCALE');
        $strict = env('LANGUAGE_MODE') == 'strict';

        // Artykuły z bazy (opublikowane, zwykłe, najnowsze).
        $dbQuery = Article::with(['category', 'tags'])
            ->where('is_published', true)
            ->where('type', 'normal');

        if ($strict) {
            $dbQuery->where('language', $defaultLangue);
        }

        $dbArticles = $dbQuery->orderBy('published_at', 'desc')->orderBy('id', 'desc')->take(12)->get();

        // Artykuły z plików .md (drugie źródło). Scalamy: .md ma pierwszeństwo przy tym samym slug.
        $mdArticles = app(MarkdownArticleRepository::class)->published($strict ? $defaultLangue : null);

        $merged = collect();
        foreach ($dbArticles as $a) {
            $merged->put($a->slug, $a);
        }
        foreach ($mdArticles as $a) {
            $merged->put($a->slug, $a);
        }

        $sorted = $merged->values()
            ->sortByDesc(fn ($a) => $a->getPublishedDate())
            ->values();

        // Pierwszy = wyróżniony, kolejne = siatka najnowszych.
        $featuredArticle = $sorted->first();
        $latestArticles = $sorted->slice(1, 6)->values();

        // Zachowujemy zmienną używaną w starych fragmentach widoku.
        $randomArticles = $sorted->take(6);

        // Kursy (opublikowane, w bieżącym języku) do sekcji "Courses" – baza + pliki .md.
        $mergedCourses = $this->mergedCourses($strict, $defaultLangue);
        $courses = $mergedCourses->take(3)->values();

        return view('views_basic.welcome', [
            'featuredArticle' => $featuredArticle,
            'latestArticles' => $latestArticles,
            'randomArticles' => $randomArticles,
            'courses' => $courses,
            // Realne liczby (baza + .md) do statystyk w hero.
            'articlesCount' => $sorted->count(),
            'coursesCount' => $mergedCourses->count(),
        ]);
    }



    public function page(string $slug): View
    {
        $page = Article::with('sections.contents')->where('slug', $slug)->first();
        return view('home.index', compact('page'));
    }

    public function courses(): View
    {
        $defaultLangue = env('APP_LOCALE');
        // Kursy z bazy + z plików .md (plik wygrywa przy tym samym slug).
        $courses = $this->mergedCourses(true, $defaultLangue);

        return view('home.courses', [
            'courses' => $courses,
            'defaultLangue' => $defaultLangue,
        ]);
    }

    /**
     * Mapa strony (HTML) – hub linkujący do wszystkich kategorii, tagów,
     * artykułów (baza + .md) i kursów. Poprawia crawlowalność i linkowanie
     * wewnętrzne (ratuje "osierocone" strony).
     */
    public function siteMap(): View
    {
        $strict = env('LANGUAGE_MODE') == 'strict';
        $lang = env('APP_LOCALE');

        // Artykuły z plików .md (drugie źródło).
        $mdArticles = app(MarkdownArticleRepository::class)->published($strict ? $lang : null);

        // Kategorie z opublikowanymi artykułami (baza + .md).
        $catIds = Article::whereNotNull('category_id')->where('is_published', true)
            ->distinct()->pluck('category_id')->all();
        foreach ($mdArticles as $a) {
            if (!empty($a->category_id)) {
                $catIds[] = $a->category_id;
            }
        }
        $categories = Category::whereIn('id', array_unique($catIds))->orderBy('name')->get();

        // Tagi (baza + wyłącznie-.md), scalone po slug.
        $tags = collect();
        $dbTagIds = TagArticle::query()->distinct()->pluck('tag_id');
        Tag::whereIn('id', $dbTagIds)
            ->when($strict, fn ($q) => $q->where('language', $lang))
            ->orderBy('name')->get()
            ->each(fn ($t) => $tags->put($t->slug ?: Str::slug($t->name), (object) ['name' => $t->name, 'slug' => $t->slug ?: Str::slug($t->name)]));
        foreach ($mdArticles as $a) {
            foreach ($a->tags as $t) {
                $slug = $t->slug ?: Str::slug($t->name);
                $tags->put($slug, (object) ['name' => $t->name, 'slug' => $slug]);
            }
        }
        // Pomijamy tagi bez poprawnego sluga (route('blogTag') wymaga niepustego).
        $tags = $tags->values()
            ->filter(fn ($t) => !empty($t->slug))
            ->sortBy(fn ($t) => mb_strtolower($t->name))
            ->values();

        // Artykuły (baza + .md), scalone po slug, alfabetycznie.
        $dbArticles = Article::where('is_published', true)->where('type', 'normal')
            ->when($strict, fn ($q) => $q->where('language', $lang))->get();
        $merged = collect();
        foreach ($dbArticles as $a) {
            $merged->put($a->slug, $a);
        }
        foreach ($mdArticles as $a) {
            $merged->put($a->slug, $a);
        }
        $articles = $merged->values()->sortBy(fn ($a) => mb_strtolower($a->name))->values();

        // Kursy.
        $courses = Course::where('is_published', true)
            ->when($strict, fn ($q) => $q->where('lang', $lang))->get();

        return view('views_basic.sitemap', compact('categories', 'tags', 'articles', 'courses'));
    }

    /**
     * Strona autora (E-E-A-T). Statyczna – bez zapytań do bazy.
     */
    public function aboutUs(): View
    {
        return view('views_basic.about');
    }

    // ============== ARTICLE ==============

    public function articleWithCategory(Request $request, string $categorySlug, string $articleSlug): View
    {
        // Źródło 1: plik .md (ma pierwszeństwo przed bazą przy tym samym slug).
        $article = app(MarkdownArticleRepository::class)->findBySlug($articleSlug);
        if (!($article && $article->isLive())) {
            // Źródło 2: baza danych.
            $article = Article::where('slug', $articleSlug)->first();
        }

        if(!$article || $article->contents == null){
            abort(404);
        }
        $randomArticles = Article::randomPublished(3, $article->id);
        $category = Category::where('slug', $categorySlug)->first();

        return view('views_basic.article', [
            'article' => $article,
            'category' => $category,
            'randomArticles' => $randomArticles,
            'nextArticle' => $article->getNextArticle(),
            'previousArticle' => $article->getPreviousArticle(),
            'relatedArticles' => $article->getRelatedArticles(6),
            'popularArticles' => Article::getPopularArticles(4),
            'categoryArticles' => $article->getCategoryArticles(6),
            'latestArticles' => Article::getLatestArticles(6),
        ]);
    }
    public function article(Request $request, string $articleSlug): View
    {
        $defaultLangue = env('APP_LOCALE');

        // Źródło 1: plik .md (ma pierwszeństwo przed bazą przy tym samym slug).
        $article = app(MarkdownArticleRepository::class)->findBySlug($articleSlug);
        if ($article && (env('LANGUAGE_MODE') != 'strict' || $article->language === $defaultLangue) && $article->isLive()) {
            // artykuł z pliku .md
        } else {
            // Źródło 2: baza danych.
            $article = Article::where('slug', $articleSlug)->where('language', $defaultLangue)->first();
        }

        if(!$article || $article->contents == null){
            abort(404);
        }

        $randomArticles = Article::randomPublished(
            3,
            $article->id,
            env('LANGUAGE_MODE') == 'strict' ? $defaultLangue : null
        );

        if (!$article) {
            abort(404, 'Artykuł nie został znaleziony');
        }

        // Pobieranie artykułów do nawigacji
        $nextArticle = $article->getNextArticle();
        $previousArticle = $article->getPreviousArticle();

        // Pobieranie powiązanych artykułów
        $relatedArticles = $article->getRelatedArticles(6);

        // Pobieranie popularnych artykułów
        $popularArticles = Article::getPopularArticles(4);

        // Pobieranie artykułów z tej samej kategorii
        $categoryArticles = $article->getCategoryArticles(6);

        // Pobieranie najnowszych artykułów
        $latestArticles = Article::getLatestArticles(6);

        return view('views_basic.article',[
            'article' => $article,
            'nextArticle' => $nextArticle,
            'previousArticle' => $previousArticle,
            'relatedArticles' => $relatedArticles,
            'popularArticles' => $popularArticles,
            'categoryArticles' => $categoryArticles,
            'latestArticles' => $latestArticles
        ]);

    }




    // ============== BLOG ==============
    public function blog(Request $request): View
    {
        // Pobierz parametr wyszukiwania
        $searchQuery = $request->get('q');

        // Buduj query dla artykułów
        $articlesQuery = Article::with(['category', 'tags'])->where('is_published', true);

        // Dodaj filtrowanie według języka jeśli jest włączone
        if(env('LANGUAGE_MODE') == 'strict') {
            $articlesQuery->where('language', env('APP_LOCALE'));
        }

        // Dodaj wyszukiwanie jeśli podano query
        if ($searchQuery) {
            $articlesQuery->where(function($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('short_description', 'like', '%' . $searchQuery . '%');
            });
        }

        $dbArticles = $articlesQuery->orderBy('published_at', 'desc')->get();

        // Źródło 2: artykuły z plików .md.
        $language = env('LANGUAGE_MODE') == 'strict' ? env('APP_LOCALE') : null;
        $mdArticles = app(MarkdownArticleRepository::class)->published($language);

        if ($searchQuery) {
            $needle = mb_strtolower($searchQuery);
            $mdArticles = $mdArticles->filter(function ($a) use ($needle) {
                return str_contains(mb_strtolower($a->name), $needle)
                    || str_contains(mb_strtolower((string) $a->short_description), $needle);
            });
        }

        // Scalanie: pliki .md mają pierwszeństwo przed bazą przy tym samym slug.
        $merged = collect();
        foreach ($dbArticles as $a) {
            $merged->put($a->slug, $a);
        }
        foreach ($mdArticles as $a) {
            $merged->put($a->slug, $a);
        }

        $sorted = $merged->values()
            ->sortByDesc(fn ($a) => $a->getPublishedDate())
            ->values();

        // Ręczna paginacja scalonej kolekcji (zachowuje działanie widoku).
        $perPage = 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $articles = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Kategorie z opublikowanymi artykułami – do chipsów (wewnętrzne linkowanie SEO).
        $categoryIds = Article::whereNotNull('category_id')
            ->where('is_published', true)
            ->distinct()
            ->pluck('category_id');
        $categories = Category::whereIn('id', $categoryIds)->orderBy('name')->get();

        return view('views_basic.blog', [
            'articles' => $articles,
            'searchQuery' => $searchQuery,
            'categories' => $categories,
            'currentCategory' => null,
        ]);
    }

    public function blogTag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->where('language', env('APP_LOCALE'))->first();

        if(!$tag){
            $tags = Tag::where('language', env('APP_LOCALE'))->get();

            foreach ($tags as $currentTag){
                if(Str::slug($currentTag->name) == $slug){
                    $currentTag->slug = $slug;
                    $currentTag->save();

                    $tag = $currentTag;
                    break;
                }
            }

        }

        // Fallback: tag może istnieć wyłącznie w artykułach z plików .md (nie ma go w bazie).
        if(!$tag){
            $mdHasTag = app(MarkdownArticleRepository::class)->published()
                ->contains(fn ($a) => $a->tags->contains(fn ($t) => $t->slug === $slug));

            if($mdHasTag){
                $tag = new Tag();
                $tag->name = Str::title(str_replace('-', ' ', $slug));
                $tag->slug = $slug;
                $tag->language = env('APP_LOCALE');
                $tag->exists = false;
            }
        }

        if(!$tag){
            abort(404);
        }

        $articleTagIds = $tag->id
            ? TagArticle::where('tag_id', $tag->id)->pluck('article_id')
            : collect();

        $uniqueCategoryIds = Article::whereNotNull('category_id')->whereIn('id', $articleTagIds->toArray())->where('is_published', true)
            ->distinct()
            ->pluck('category_id');


        $categories = Category::whereIn('id', $uniqueCategoryIds)->get();
        $coursesLesson = [];
        $normalArticles = [];

        // Źródło 1: artykuły z bazy powiązane z tagiem.
        $dbQuery = Article::where('is_published', true)
            ->whereIn('id', $articleTagIds->toArray());

        if(env('LANGUAGE_MODE') == 'strict') {
            $dbQuery->where('language', env('APP_LOCALE'));
        }

        $dbArticles = $dbQuery->orderBy('created_at', 'desc')->get();

        // CourseCategoryLesson nie ma już pola lesson_id i nie powiązuje się z Article,
        // więc wszystkie artykuły z bazy traktujemy jako zwykłe artykuły.
        foreach ($dbArticles as $article){
            $normalArticles[] = $article;
        }

        // Źródło 2: artykuły z plików .md oznaczone tym tagiem.
        $language = env('LANGUAGE_MODE') == 'strict' ? env('APP_LOCALE') : null;
        $mdArticles = app(MarkdownArticleRepository::class)->published($language)
            ->filter(fn ($a) => $a->tags->contains(fn ($t) => $t->slug === $tag->slug));

        foreach ($mdArticles as $article){
            $normalArticles[] = $article;
        }

        // Scalone, posortowane malejąco po dacie publikacji.
        $normalArticles = collect($normalArticles)
            ->sortByDesc(fn ($a) => $a->getPublishedDate())
            ->values()
            ->all();

        return view('views_basic.blog_tag',[
            'categories' => $categories,
            'articles' => $normalArticles,
            'coursesLesson' => $coursesLesson,
            'currentCategory' => null,
            'tag' => $tag
        ]);
    }


    public function blogListCategory(Request $request, string $slug): View
    {
        $currentCategory = Category::where('slug', $slug)->first();
        if(!$currentCategory){
            abort(404);
        }

        // Źródło 1: artykuły z bazy w tej kategorii.
        $dbQuery = Article::where('type', 'normal')
            ->where('is_published', true)
            ->where('category_id', $currentCategory->id);

        if(env('LANGUAGE_MODE') == 'strict') {
            $dbQuery->where('language', env('APP_LOCALE'));
        }

        $dbArticles = $dbQuery->orderBy('created_at', 'desc')->get();

        // Źródło 2: artykuły z plików .md przypisane do tej kategorii.
        $language = env('LANGUAGE_MODE') == 'strict' ? env('APP_LOCALE') : null;
        $mdArticles = app(MarkdownArticleRepository::class)->published($language)
            ->filter(fn ($a) => (int) $a->category_id === (int) $currentCategory->id);

        // Scalanie: pliki .md mają pierwszeństwo przed bazą przy tym samym slug.
        $merged = collect();
        foreach ($dbArticles as $a) {
            $merged->put($a->slug, $a);
        }
        foreach ($mdArticles as $a) {
            $merged->put($a->slug, $a);
        }

        $sorted = $merged->values()
            ->sortByDesc(fn ($a) => $a->getPublishedDate())
            ->values();

        $perPage = 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $articles = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $currentCategory = $currentCategory->name;

        $uniqueCategoryIds = Article::whereNotNull('category_id')
            ->where('is_published', true)
            ->distinct()
            ->pluck('category_id');

        $categories = Category::whereIn('id', $uniqueCategoryIds)->get();
        $info = CmsPage::find(1);


        return view('views_basic.blog', [
            'categories' => $categories,
            'articles' => $articles,
            'currentCategory' => $currentCategory,
            'searchQuery' => null
        ]);
    }

    public function sendEmail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'first-name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'topic' => 'required|string|max:255',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required|captcha'
        ]);

//        $to = 'kurytplagain@gmail.com';
        $to = 'kontakt@serwis-elektroniki-bartlomiej-biernat.pl';

        Mail::to($to)->send(new InformationContact([
            'first-name' => $request->get('first-name'),
            'last-name' => $request->get('last-name'),
            'email' => $request->get('email'),
            'topic' => $request->get('topic'),
            'message' => $request->get('message')
        ]));


        return response()->json(['success' => true]);
    }

    // ============== COURSES ==============

    /**
     * Znajduje kurs po slug: najpierw plik .md (pierwszeństwo), potem baza.
     * Zwrócony model ma załadowane relacje categories.lessons (obie źródła).
     */
    private function resolveCourse(string $slug): ?Course
    {
        $file = app(MarkdownCourseRepository::class)->findCourse($slug);
        if ($file && $file->isLive()) {
            return $file;
        }

        return Course::where('slug', $slug)->with(['categories.lessons'])->first();
    }

    /**
     * Scala kursy z plików .md i z bazy (plik wygrywa przy tym samym slug).
     *
     * @return \Illuminate\Support\Collection<int, Course>
     */
    private function mergedCourses(bool $strict, string $lang): \Illuminate\Support\Collection
    {
        $db = Course::where('is_published', true)
            ->when($strict, fn ($q) => $q->where('lang', $lang))
            ->get();

        $md = app(MarkdownCourseRepository::class)->published($strict ? $lang : null);

        $merged = collect();
        foreach ($db as $c) {
            $merged->put($c->slug, $c);
        }
        foreach ($md as $c) {
            $merged->put($c->slug, $c);
        }

        return $merged->values();
    }

    public function course(Request $request, string $courseName): View
    {
        $course = $this->resolveCourse($courseName);

        if(!$course){
            abort(404);
        }

        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            $urlToCourse = route('course_pl', ['courseName' => $course->slug ]);
        }else{
            $urlToCourse = route('course_en', ['courseName' => $course->slug ]);
        }

        $firstLesson = null;

        foreach ($course->categories as $category) {
            foreach ($category->lessons as $lesson) {
                $firstLesson = $lesson->getRoute();
                break;
            }

            if($firstLesson !== null){
                break;
            }
        }

        return view('home.course', [
            'course' => $course,
            'urlToCourse' => $urlToCourse,
            'firstLessonRoute' => $firstLesson
        ]);
    }



    public function chapterPl(Request $request, string $courseName, string $chapter): View
    {
        $course = $this->resolveCourse($courseName);

        if(!$course){
            abort(404);
        }

        $courseCategory = $course->categories->firstWhere('slug', $chapter);

        if(!$courseCategory){
            abort(404);
        }

        return view('home.chapter', [
            'courseName' => $courseName,
            'chapter' => $chapter,
            'course' => $course,
            'courseCategory' => $courseCategory,
            'category' => $courseCategory,
        ]);
    }

    public function courseLessonPl(Request $request, string $courseName, string $chapter, string $lesson): View
    {
        $course = $this->resolveCourse($courseName);

        if(!$course){
            abort(404);
        }

        $courseCategory = $course->categories->firstWhere('slug', $chapter);

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = $courseCategory->lessons->firstWhere('slug', $lesson);

        if(!$currentLesson){
            abort(404);
        }

        return view('home.lesson', [
            'courseName' => $courseName,
            'chapter' => $chapter,
            'course' => $course,
            'courseCategory' => $courseCategory,
            'category' => $courseCategory,
            'article' => $currentLesson,
            'lessonSkip' => CourseHelper::lessonGo($course, $currentLesson),
            'faqItems' => \App\Services\Course\LessonFaqExtractor::extract($currentLesson->getDisplayContentHtml()),
        ]);
    }

    public function courseLessonEn(Request $request, string $courseName, string $chapter, string $lesson): View
    {
        $course = $this->resolveCourse($courseName);

        if(!$course){
            abort(404);
        }

        $courseCategory = $course->categories->firstWhere('slug', $chapter);

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = $courseCategory->lessons->firstWhere('slug', $lesson);

        if(!$currentLesson){
            abort(404);
        }

        return view('home.lesson', [
            'courseName' => $courseName,
            'chapter' => $chapter,
            'course' => $course,
            'courseCategory' => $courseCategory,
            'category' => $courseCategory,
            'article' => $currentLesson,
            'lessonSkip' => CourseHelper::lessonGo($course, $currentLesson),
            'faqItems' => \App\Services\Course\LessonFaqExtractor::extract($currentLesson->getDisplayContentHtml()),
        ]);
    }

    public function chapterEn(Request $request, string $courseName, string $chapter): View
    {
        $course = $this->resolveCourse($courseName);

        if(!$course){
            abort(404);
        }

        $courseCategory = $course->categories->firstWhere('slug', $chapter);

        if(!$courseCategory){
            abort(404);
        }

        return view('home.chapter', [
            'courseName' => $courseName,
            'chapter' => $chapter,
            'course' => $course,
            'courseCategory' => $courseCategory,
            'category' => $courseCategory,
        ]);
    }
}
