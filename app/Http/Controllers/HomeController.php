<?php

namespace App\Http\Controllers;

use App\Mail\InformationContact;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Article;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use App\Services\Course\CourseHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if(env('LANGUAGE_MODE') == 'strict'){
            $defaultLangue = env('APP_LOCALE');
            $lessonsNotIn = [];
            foreach (CourseCategoryLesson::get() as $lesson){
                $lessonsNotIn[] = $lesson->lesson_id;
            }

            $randomArticles = Article::where('is_published', true)->where('language', $defaultLangue)->whereNotIn('id', $lessonsNotIn)->inRandomOrder()->take(3)->get();
        }else{
            $randomArticles = Article::where('is_published', true)->inRandomOrder()->take(3)->get();
        }


        return view('views_basic.welcome', [
            'randomArticles' => $randomArticles
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

    public function coursesEn(): View
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
        $article = Article::where('slug', $articleSlug)->first();
        if(!$article || $article->contents == null){
            abort(404);
        }

        if(env('LANGUAGE_MODE') == 'strict'){
            $defaultLangue = env('APP_LOCALE');
            $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->where('language', $defaultLangue)->inRandomOrder()->take(3)->get();

        }else{
            $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->inRandomOrder()->take(3)->get();
        }

        return view('views_basic.article', [
            'article' => $article,
            'category' => null,
            'randomArticles' => $randomArticles
        ]);
    }




    // ============== BLOG ==============
    public function blog(): View
    {
        $uniqueCategoryIds = Article::whereNotNull('category_id')->where('is_published', true)
            ->distinct()
            ->pluck('category_id');

        $categories = Category::whereIn('id', $uniqueCategoryIds)->get();

        if(env('LANGUAGE_MODE') == 'strict') {
            $lessonsNotIn = [];
            foreach (CourseCategoryLesson::get() as $lesson){
                $lessonsNotIn[] = $lesson->lesson_id;
            }

            $articles = Article::where('is_published', true)
                ->where('language', env('APP_LOCALE'))
                ->whereNotIn('id', $lessonsNotIn)
                ->orderBy('created_at', 'desc')->paginate(10);
        }else{
            $articles = Article::where('is_published', true)
                ->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('views_basic.blog',[
            'categories' => $categories,
            'articles' => $articles,
            'currentCategory' => null
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
        $defaultLangue = env('APP_LOCALE');
        $course = Course::where('slug', $courseName)->where('lang', $defaultLangue)->first();

        if(!$course){
            abort(404);
        }

        if($defaultLangue == 'pl'){
            $urlToCourse = route('course_pl', ['courseName' => $course->slug ]);
        }else{
            $urlToCourse = route('course_en', ['courseName' => $course->slug ]);
        }

        $firstLesson = null;

        foreach ($course->categories as $category) {
            foreach ($category->lessons as $lesson) {
                $firstLesson = $lesson->getRouteCourse($category);
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
        $defaultLangue = env('APP_LOCALE');
        $course = Course::where('slug', $courseName)->where('lang', $defaultLangue)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('lang', $defaultLangue)->where('course_id', $course->id)->first();

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
        $defaultLangue = env('APP_LOCALE');
        $course = Course::where('slug', $courseName)->where('lang', $defaultLangue)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('lang', $defaultLangue)->where('course_id', $course->id)->first();

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = null;
        foreach ($courseCategory->lessons as $categoryLesson){
            if($categoryLesson->slug == $lesson){
                $currentLesson = $categoryLesson;
            }
        }

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
        $defaultLangue = env('APP_LOCALE');
        $course = Course::where('slug', $courseName)->where('lang', $defaultLangue)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('lang', $defaultLangue)->where('course_id', $course->id)->first();

        if(!$courseCategory){
            abort(404);
        }

        $currentLesson = null;
        foreach ($courseCategory->lessons as $categoryLesson){
            if($categoryLesson->slug == $lesson){
                $currentLesson = $categoryLesson;
            }
        }

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
        $defaultLangue = env('APP_LOCALE');
        $course = Course::where('slug', $courseName)->where('lang', $defaultLangue)->first();

        if(!$course){
            abort(404);
        }

        $courseCategory = CourseCategory::where('slug', $chapter)->where('lang', $defaultLangue)->where('course_id', $course->id)->first();

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
