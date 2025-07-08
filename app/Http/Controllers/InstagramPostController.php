<?php

namespace App\Http\Controllers;

use App\Models\InstagramPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class InstagramPostController extends Controller
{
    public function index(Request $request)
    {
        $postInstagrams = InstagramPost::orderBy('created_at', 'desc')->paginate(10);

        return view('instagram_posts.index', [
            'posts' => $postInstagrams,
            'title' => __('Instagram posts'),
            'description' => __('List of Instagram posts'),
            'keywords' => __('Instagram, posts, social media')
        ]);
    }


    public function add(Request $request){
        // 1️⃣ Walidacja
        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'], // <= 5 MB
            'url'   => ['required', 'url', 'max:255'],
        ]);

        // 2️⃣ Zapis pliku do dysku "public"
        //    => storage/app/public/instagram_posts/xxxx.jpg
        $path = $validated['image']->store('instagram_posts', 'public');


        // 4️⃣ Wstawienie rekordu do DB
        InstagramPost::create([
            'image_link' => $path,
            'url'        => $validated['url'],
            'language'   => app()->getLocale() ?? 'pl', // lub dowolna logika
        ]);

        // 5️⃣ Redirect / JSON
        return back()->with('success', 'Post został dodany!');
    }

    public function remove(InstagramPost $post)
    {
        $post->delete();

        return Redirect::back();
    }
}
