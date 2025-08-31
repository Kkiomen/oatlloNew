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
use App\Services\Course\CourseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $postInstagrams = [];
        $defaultLangue = env('APP_LOCALE');

        if(env('LANGUAGE_MODE') == 'strict'){
            $lessonsNotIn = [];
            foreach (CourseCategoryLesson::get() as $lesson){
                $lessonsNotIn[] = $lesson->lesson_id;
            }

            $randomArticles = Article::where('is_published', true)->where('language', $defaultLangue)->whereNotIn('id', $lessonsNotIn)->orderBy('id', 'desc')->take(6)->get();
            $postInstagrams = InstagramPost::where('language', $defaultLangue)->orderBy('created_at', 'desc')->take(8)->get();
//            $randomArticles = Article::where('is_published', true)->where('language', $defaultLangue)->whereNotIn('id', $lessonsNotIn)->inRandomOrder()->take(6)->get();
        }else{
            $randomArticles = Article::where('is_published', true)->inRandomOrder()->take(6)->get();
        }

        // Pobierz kursy PHP
        $courses = Course::where('is_published', true)
            ->where('lang', $defaultLangue)
            ->with(['categories' => function($query) {
                $query->withCount('lessons');
            }])
            ->take(3)
            ->get();

        return view('views_basic.welcome', [
            'randomArticles' => $randomArticles,
            'postInstagrams' => $postInstagrams,
            'courses' => $courses,
            'defaultLangue' => $defaultLangue
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
        $courses = Course::where('is_published', true)->where('lang', $defaultLangue)->get();

        return view('home.courses', [
            'courses' => $courses,
            'defaultLangue' => $defaultLangue,
        ]);
    }

    // ============== ARTICLE ==============

    public function articleWithCategory(Request $request, string $categorySlug, string $articleSlug): View
    {
        $article = Article::where('slug', $articleSlug)->first();
        if(!$article || $article->contents == null){
            abort(404);
        }
        $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->where('type', 'normal')->inRandomOrder()->take(3)->get();
        $category = Category::where('slug', $categorySlug)->first();

        return view('views_basic.article', [
            'article' => $article,
            'category' => $category,
            'randomArticles' => $randomArticles
        ]);
    }
    public function article(Request $request, string $articleSlug): View
    {
        $defaultLangue = env('APP_LOCALE');
        $article = Article::where('slug', $articleSlug)->where('language', $defaultLangue)->first();
        if(!$article || $article->contents == null){
            abort(404);
        }

        $lessonsNotIn = [];
        foreach (CourseCategoryLesson::get() as $lesson){
            $lessonsNotIn[] = $lesson->lesson_id;
        }


        if(env('LANGUAGE_MODE') == 'strict'){
            $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->whereNotIn('id', $lessonsNotIn)->where('language', $defaultLangue)->inRandomOrder()->take(3)->get();

        }else{
            $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->whereNotIn('id', $lessonsNotIn)->inRandomOrder()->take(3)->get();
        }

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
            $lessonsNotIn = [];
            foreach (CourseCategoryLesson::get() as $lesson){
                $lessonsNotIn[] = $lesson->lesson_id;
            }

            $articlesQuery->where('language', env('APP_LOCALE'))
                ->whereNotIn('id', $lessonsNotIn);
        }

        // Dodaj wyszukiwanie jeśli podano query
        if ($searchQuery) {
            $articlesQuery->where(function($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('short_description', 'like', '%' . $searchQuery . '%');
            });
        }

        $articles = $articlesQuery->orderBy('published_at', 'desc')->paginate(12);

        return view('views_basic.blog', [
            'articles' => $articles,
            'searchQuery' => $searchQuery
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

        if(!$tag){
            abort(404);
        }



        $articleTagIds = TagArticle::where('tag_id', $tag->id)->pluck('article_id');

        $uniqueCategoryIds = Article::whereNotNull('category_id')->whereIn('id', $articleTagIds->toArray())->where('is_published', true)
            ->distinct()
            ->pluck('category_id');


        $categories = Category::whereIn('id', $uniqueCategoryIds)->get();
        $coursesLesson = [];
        $normalArticles = [];

        if(env('LANGUAGE_MODE') == 'strict') {
            $articles = Article::where('is_published', true)
                ->where('language', env('APP_LOCALE'))
                ->whereIn('id', $articleTagIds->toArray())
                ->orderBy('created_at', 'desc')->paginate(10);

            $lessonsNotIn = [];
            foreach (CourseCategoryLesson::get() as $lesson){
                $lessonsNotIn[] = $lesson->lesson_id;
            }

            foreach ($articles as $article){
                if(in_array($article->id, $lessonsNotIn)){
                    $coursesLesson[] = $article;
                }else{
                    $normalArticles[] = $article;
                }
            }




        }else{
            $articles = Article::where('is_published', true)
                ->whereIn('id', $articleTagIds->toArray())
                ->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('views_basic.blog_tag',[
            'categories' => $categories,
            'articles' => $normalArticles,
            'coursesLesson' => $coursesLesson,
            'currentCategory' => null,
            'tag' => $tag
        ]);
    }


    public function blogListCategory(string $slug): View
    {
        $currentCategory = Category::where('slug', $slug)->first();
        if(!$currentCategory){
            abort(404);
        }

        if(env('LANGUAGE_MODE') == 'strict') {
            $articles = Article::where('type', 'normal')
                ->where('is_published', true)
                ->where('category_id', $currentCategory->id)
                ->where('language', env('APP_LOCALE'))
                ->orderBy('created_at', 'desc')->paginate(10);
        }else{
            $articles = Article::where('type', 'normal')
                ->where('is_published', true)
                ->where('category_id', $currentCategory->id)
                ->orderBy('created_at', 'desc')->paginate(10);
        }

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
            'currentCategory' => $currentCategory
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

    public function course(Request $request, string $courseName): View
    {
        $course = Course::where('slug', $courseName)
            ->with(['categories.lessons'])
            ->first();

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
        $course = Course::where('slug', $courseName)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('course_id', $course->id)->first();

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
        $course = Course::where('slug', $courseName)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('course_id', $course->id)->first();

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = CourseCategoryLesson::where('course_category_id', $courseCategory->id)
            ->where('slug', $lesson)
            ->where('is_published', true)
            ->first();

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
            'lessonSkip' => CourseHelper::lessonGo($course, $currentLesson)
        ]);
    }

    public function courseLessonEn(Request $request, string $courseName, string $chapter, string $lesson): View
    {
        $course = Course::where('slug', $courseName)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('course_id', $course->id)->first();

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = CourseCategoryLesson::where('course_category_id', $courseCategory->id)
            ->where('slug', $lesson)
            ->where('is_published', true)
            ->first();

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
            'lessonSkip' => CourseHelper::lessonGo($course, $currentLesson)
        ]);
    }

    public function chapterEn(Request $request, string $courseName, string $chapter): View
    {
        $course = Course::where('slug', $courseName)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('course_id', $course->id)->first();

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
