<?php

namespace Tests\Feature;

use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\Review\SocialVerificationStamp;
use App\Services\Social\SocialPostLinter;
use App\Services\Social\SocialPostType;
use App\Services\Social\SocialStyleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Generator story "nowy artykuł" (`social:article-stories`).
 *
 * Bez RefreshDatabase – ani artykuły .md, ani moduł social nie mają tabeli.
 * Testy izolują się na katalogach tymczasowych (config articles.path + social.path),
 * więc nie tworzą plików w prawdziwym resources/.
 */
class SocialArticleStoriesTest extends TestCase
{
    private string $articlesDir;

    private string $socialDir;

    protected function setUp(): void
    {
        parent::setUp();

        $base = storage_path('framework/testing/article-stories-' . uniqid());
        $this->articlesDir = $base . '/articles';
        $this->socialDir = $base . '/social';

        File::ensureDirectoryExists($this->articlesDir);
        File::ensureDirectoryExists($this->socialDir);

        config([
            'articles.path'       => $this->articlesDir,
            'social.path'         => $this->socialDir,
            'social.reviews_path' => $this->socialDir . '/reviews',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->articlesDir));

        parent::tearDown();
    }

    private function writeArticle(string $slug, string $name, CarbonImmutable $publishedAt): void
    {
        $frontmatter = implode("\n", [
            'name: ' . '"' . $name . '"',
            'slug: ' . $slug,
            'short_description: "Test article."',
            'language: en',
            'published_at: ' . $publishedAt->format('Y-m-d H:i:s'),
            'is_published: true',
            'tags: [laravel, php]',
        ]);

        File::put($this->articlesDir . '/' . $slug . '.md', "---\n{$frontmatter}\n---\n\nBody of the article.\n");
    }

    private function storyPath(string $articleSlug): string
    {
        return $this->socialDir . '/story-new-' . $articleSlug . '.md';
    }

    public function test_it_generates_a_valid_verified_story_for_a_queued_article(): void
    {
        $this->writeArticle('demo-topic-guide', 'Demo Topic Guide', CarbonImmutable::now()->addMonth());

        $this->artisan('social:article-stories')->assertSuccessful();

        $path = $this->storyPath('demo-topic-guide');
        $this->assertFileExists($path);

        $raw = File::get($path);
        $post = (new MarkdownSocialPostParser())->toPost($raw, 'x');

        $this->assertSame(SocialPostType::Story, $post->type);
        $this->assertSame('announce-article', $post->style);
        $this->assertSame(['story'], $post->formats);
        $this->assertTrue($post->isReady());
        $this->assertSame('article', $post->sourceType);
        $this->assertSame('demo-topic-guide', $post->source);
        $this->assertSame('https://oatllo.com/demo-topic-guide', $post->link);
        $this->assertStringContainsString('Demo Topic Guide', $post->slides[0]->headline ?? '');

        // Bramka formatu: lint bez błędów (i bez ostrzeżeń – caption jest pusty,
        // bo formats:[story]).
        $linter = new SocialPostLinter(new MarkdownSocialPostParser(), new SocialStyleResolver());
        $this->assertSame([], $linter->lintRaw($raw, $post->slug), 'Wygenerowane story nie przechodzi lintu.');

        // Pieczątka weryfikacji: odcisk MUSI pasować do treści, inaczej panel
        // pokazałby "NIEAKTUALNA" zamiast zielonego "zweryfikowane".
        $this->assertNotNull($post->verified);
        $this->assertTrue($post->verified->isApproved());
        $this->assertTrue(
            $post->verified->matches(SocialVerificationStamp::contentFingerprint($raw)),
            'Odcisk pieczątki nie pasuje do treści story.',
        );
    }

    public function test_it_is_idempotent_and_keeps_manual_edits(): void
    {
        $this->writeArticle('keep-my-edits', 'Keep My Edits', CarbonImmutable::now()->addMonth());

        $this->artisan('social:article-stories')->assertSuccessful();

        $path = $this->storyPath('keep-my-edits');
        File::put($path, File::get($path) . "\n<!-- human tweak -->\n");

        // Ponowne uruchomienie NIE nadpisuje istniejącego pliku.
        $this->artisan('social:article-stories')->assertSuccessful();

        $this->assertStringContainsString('<!-- human tweak -->', File::get($path));
    }

    public function test_force_overwrites_an_existing_story(): void
    {
        $this->writeArticle('rewrite-me', 'Rewrite Me', CarbonImmutable::now()->addMonth());

        $this->artisan('social:article-stories')->assertSuccessful();
        $path = $this->storyPath('rewrite-me');
        File::put($path, File::get($path) . "\n<!-- human tweak -->\n");

        $this->artisan('social:article-stories', ['--force' => true])->assertSuccessful();

        $this->assertStringNotContainsString('<!-- human tweak -->', File::get($path));
    }

    public function test_a_past_article_is_queued_only_with_all(): void
    {
        $this->writeArticle('already-live', 'Already Live', CarbonImmutable::now()->subMonth());

        // Domyślnie: tylko przyszłe – już opublikowany artykuł nie dostaje story.
        $this->artisan('social:article-stories')->assertSuccessful();
        $this->assertFileDoesNotExist($this->storyPath('already-live'));

        // --all bierze też opublikowane.
        $this->artisan('social:article-stories', ['--all' => true])->assertSuccessful();
        $this->assertFileExists($this->storyPath('already-live'));
    }
}
