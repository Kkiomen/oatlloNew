<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function rss(Request $request)
    {
        $feedUrl = url('/feed');
        $siteUrl = url('/');

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"></rss>'
        );

        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Oatllo – programming blog, projects, courses, tips.');
        $channel->addChild('link', $siteUrl);
        $channel->addChild('description', 'Oatllo is a place for programming enthusiasts where you will find articles, projects, courses, and tips. Enhance your skills and explore modern technologies.');
        $channel->addChild('language', 'en-US');
        $channel->addChild('lastBuildDate', now()->toRfc2822String());

        // Atom link element in correct namespace
        $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
        $atomLink->addAttribute('href', $feedUrl);
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');

        $defaultLanguage = env('APP_LOCALE', 'en');

        $articles = Article::where('is_published', true)
            ->where('language', $defaultLanguage)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        foreach ($articles as $article) {
            $item = $channel->addChild('item');
            $item->addChild('title', htmlspecialchars($article->name));
            $item->addChild('link', $article->getRoute());
            $item->addChild('description', htmlspecialchars($article->short_description));
            $item->addChild('pubDate', $article->created_at->toRfc2822String());
            $item->addChild('guid', url('/p/' . $article->slug));
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
