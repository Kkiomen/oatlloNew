<?php

namespace Tests\Feature;

use App\Models\CourseCategoryLesson;
use App\Services\Article\MarkdownArticleRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Link do artykułu .md z datą w przyszłości oddaje 404 aż do publikacji.
 * 17.09.2026 było tak w 7 plikach (m.in. trzy lekcje kursu PHP linkowały
 * `/php-match-vs-switch`, zaplanowany na listopad).
 */
class ScheduledArticleLinkTest extends TestCase
{
    private string $articlesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articlesDir = storage_path('framework/testing/scheduled-links-' . uniqid());
        File::ensureDirectoryExists($this->articlesDir);
        config()->set('articles.path', $this->articlesDir);
        config()->set('articles.internal_linking.enabled', false);
        Cache::flush();

        $this->article('live-article', now()->subDay());
        $this->article('future-article', now()->addMonth());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->articlesDir);

        parent::tearDown();
    }

    private function article(string $slug, $publishedAt): void
    {
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . $slug . '.md',
            "---\nname: \"{$slug}\"\nslug: {$slug}\nlanguage: en\npublished_at: {$publishedAt->format('Y-m-d H:i:s')}\n---\n\nBody."
        );
    }

    private function lesson(string $html): CourseCategoryLesson
    {
        return new CourseCategoryLesson(['content_html' => $html]);
    }

    public function test_lesson_link_to_scheduled_article_becomes_plain_text(): void
    {
        $html = $this->lesson(
            '<p>See <a href="/future-article">the comparison</a> and <a href="/live-article">this one</a>.</p>'
        )->getDisplayContentHtml();

        $this->assertStringNotContainsString('future-article', $html);
        $this->assertStringContainsString('See the comparison and', $html);
        $this->assertStringContainsString('<a href="/live-article">this one</a>', $html);
    }

    public function test_absolute_and_anchored_links_are_stripped_too(): void
    {
        $html = $this->lesson(
            '<p><a href="https://oatllo.com/future-article#intro">a</a> <a href="https://www.oatllo.com/future-article/">b</a></p>'
        )->getDisplayContentHtml();

        $this->assertSame('<p>a b</p>', $html);
    }

    public function test_leaves_lessons_foreign_links_and_prefix_slugs_alone(): void
    {
        $source = '<p><a href="/course/php/future-article">x</a> <a href="https://example.com/future-article">y</a> <a href="/future-article-2">z</a></p>';

        $this->assertSame($source, $this->lesson($source)->getDisplayContentHtml());
    }

    public function test_link_comes_back_on_publication_day(): void
    {
        $this->travel(2)->months();

        $html = $this->lesson('<p><a href="/future-article">x</a></p>')->getDisplayContentHtml();

        $this->assertStringContainsString('<a href="/future-article">x</a>', $html);
    }

    public function test_article_body_strips_links_to_scheduled_articles(): void
    {
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . 'source.md',
            "---\nname: Source\nslug: source\nlanguage: en\n---\n\nRead [the next one](/future-article) and [the old one](/live-article)."
        );

        $article = app(MarkdownArticleRepository::class)->findBySlug('source');
        $html = implode(' ', array_column($article->getDisplayContents(), 'content'));

        $this->assertStringNotContainsString('/future-article', $html);
        $this->assertStringContainsString('/live-article', $html);
    }
}
