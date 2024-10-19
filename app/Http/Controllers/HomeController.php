<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $info = CmsPage::find(1);

        $randomArticles = Article::where('is_published', true)->where('type', 'normal')->inRandomOrder()->take(3)->get();


        return view('welcome', array_merge(
            $info->to_view,
            [
                'randomArticles' => $randomArticles
            ]
        ));
    }



    public function page(string $slug): View
    {
        $page = Article::with('sections.contents')->where('slug', $slug)->first();
        return view('home.index', compact('page'));
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

        return view('article', [
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

        $randomArticles = Article::where('id', '!=', $article->id)->where('is_published', true)->where('type', 'normal')->inRandomOrder()->take(3)->get();

        return view('article', [
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

        $articles = Article::where('type', 'normal')->where('is_published', true)->orderBy('created_at', 'desc')->paginate(10);
        $info = CmsPage::find(1);

        return view('blog',array_merge(
            $info->to_view,
            [
                'categories' => $categories,
                'articles' => $articles,
                'currentCategory' => null
            ]
        ));
    }


    public function blogListCategory(string $slug): View
    {
        $currentCategory = Category::where('slug', $slug)->first();
        if(!$currentCategory){
            abort(404);
        }

        $articles = Article::where('type', 'normal')
                            ->where('is_published', true)
                            ->where('category_id', $currentCategory->id)
                            ->orderBy('created_at', 'desc')->paginate(10);

        $currentCategory = $currentCategory->name;

        $uniqueCategoryIds = Article::whereNotNull('category_id')
            ->where('is_published', true)
            ->distinct()
            ->pluck('category_id');

        $categories = Category::whereIn('id', $uniqueCategoryIds)->get();
        $info = CmsPage::find(1);


        return view('blog', array_merge(
            $info->to_view,
            [
                'categories' => $categories,
                'articles' => $articles,
                'currentCategory' => $currentCategory
            ]
        ));
    }

    public function sendEmail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'g-recaptcha-response' => 'required|captcha'
        ]);

        $to = 'kurytplagain@gmail.com';
        $subject = "Email Subject";

        $message = "Imię: ". $request->get('first-name')." <br/>";
        $message .= "Nazwisko: ". $request->get('last-name')." <br/>";
        $message .= "Email: ". $request->get('email')." <br/>";
        $message .= "Temat: ". $request->get('topic')." <br/>";
        $message .= "Wiadomość: ". $request->get('message')." <br/>";

        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        // More headers
        $headers .= 'From: <enquiry@example.com>' . "\r\n";
        $headers .= 'Cc: myboss@example.com' . "\r\n";

        mail($to,$subject,$message,$headers);
        return redirect()->back()->with('message', 'Email sent successfully');
    }
}
