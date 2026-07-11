<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Services\Article\CoverImageService;
use App\Services\Article\MarkdownArticleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArticleCoverTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/cover-articles-' . uniqid());
        File::ensureDirectoryExists($this->dir);
        config()->set('articles.path', $this->dir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->dir)) {
            File::deleteDirectory($this->dir);
        }

        parent::tearDown();
    }

    public function test_cover_endpoint_returns_valid_svg_for_db_article(): void
    {
        $article = Article::create([
            'name' => 'Jak działają atrybuty w PHP 8',
            'slug' => 'atrybuty-php-8',
            'is_published' => true,
            'language' => 'en',
            'type' => 'normal',
            'contents' => [['type' => 'text', 'content' => 'body']],
        ]);

        $response = $this->get('/articles/' . $article->slug . '/cover.svg');

        $response->assertStatus(200);
        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));

        $svg = $response->getContent();
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('oatllo', $svg);
        // Tytuł trafia do okładki (jako komentarz w "kodzie").
        $this->assertStringContainsString('atrybuty', $svg);

        // Poprawny XML.
        $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($svg));
    }

    public function test_theme_is_matched_by_topic(): void
    {
        $service = app(CoverImageService::class);

        $laravel = new Article(['name' => 'Kolejki w Laravel od podstaw', 'slug' => 'kolejki-laravel']);
        $this->assertSame('Laravel', $service->resolveTheme($laravel)['label']);

        $js = new Article(['name' => 'Async await w JavaScript', 'slug' => 'async-await-js']);
        $this->assertSame('JavaScript', $service->resolveTheme($js)['label']);

        $plain = new Article(['name' => 'Zupełnie inny temat bez technologii', 'slug' => 'inny']);
        $this->assertSame(config('covers.default')['label'], $service->resolveTheme($plain)['label']);
    }

    public function test_markdown_article_without_image_gets_generated_cover(): void
    {
        File::put(
            $this->dir . DIRECTORY_SEPARATOR . 'no-image.md',
            "---\nname: \"Bez obrazka\"\nslug: no-image\nlanguage: en\n---\n\nbody"
        );

        $article = app(MarkdownArticleRepository::class)->findBySlug('no-image');

        $this->assertStringContainsString('/articles/no-image/cover.svg', $article->image);
    }

    public function test_long_title_scales_down_instead_of_overflowing(): void
    {
        $service = app(CoverImageService::class);
        $theme = config('covers.default');

        $short = $service->render('PHP 8', $theme);
        $long = $service->render(
            'Kompletny przewodnik po optymalizacji zapytań MySQL: indeksy, EXPLAIN, '
            . 'partycjonowanie i typowe pułapki wydajności w dużych bazach danych',
            $theme
        );

        // Krótki tytuł używa największej czcionki; długi jest zmniejszany.
        $this->assertStringContainsString('font-size="58"', $short);
        $this->assertStringNotContainsString('font-size="58"', $long);

        // Linie tytułu = tspany akcentu minus 1 (tspan ".com" w marce).
        $longTitleLines = substr_count($long, '<tspan') - 1;
        $this->assertLessThanOrEqual(4, $longTitleLines);
        $this->assertGreaterThan(1, $longTitleLines);
    }

    public function test_markdown_article_with_explicit_image_keeps_it(): void
    {
        File::put(
            $this->dir . DIRECTORY_SEPARATOR . 'with-image.md',
            "---\nname: \"Z obrazkiem\"\nslug: with-image\nlanguage: en\nimage: https://example.com/pic.png\n---\n\nbody"
        );

        $article = app(MarkdownArticleRepository::class)->findBySlug('with-image');

        $this->assertSame('https://example.com/pic.png', $article->image);
    }
}
