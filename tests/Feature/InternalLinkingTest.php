<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Services\Article\InternalLinker;
use App\Services\Article\MarkdownArticleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InternalLinkingTest extends TestCase
{
    use RefreshDatabase;

    private string $articlesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articlesDir = storage_path('framework/testing/link-articles-' . uniqid());
        File::ensureDirectoryExists($this->articlesDir);

        config()->set('articles.path', $this->articlesDir);
        config()->set('articles.default_language', 'en');
        config()->set('articles.internal_linking', [
            'enabled' => true,
            'max_links_per_article' => 3,
            'max_links_per_target' => 1,
            'min_phrase_length' => 4,
            'stopwords' => [],
            'cache_ttl' => 600,
        ]);

        // Świeży indeks dla każdego testu (cache jest współdzielony w procesie).
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->articlesDir)) {
            File::deleteDirectory($this->articlesDir);
        }

        parent::tearDown();
    }

    private function makeDbArticle(string $name, string $slug, string $bodyHtml, array $extra = []): Article
    {
        return Article::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'is_published' => true,
            'language' => 'en',
            'type' => 'normal',
            'contents' => [['type' => 'text', 'content' => $bodyHtml]],
        ], $extra));
    }

    private function makeMdArticle(string $name, string $slug, string $body): void
    {
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . $slug . '.md',
            "---\nname: \"{$name}\"\nslug: {$slug}\nlanguage: en\n---\n\n{$body}"
        );
    }

    private function html(Article $article): string
    {
        return implode(' ', array_column($article->getDisplayContents(), 'content'));
    }

    public function test_db_article_links_to_markdown_article(): void
    {
        $this->makeMdArticle('Czysty kod', 'clean-code', 'Treść o czystym kodzie.');
        $source = $this->makeDbArticle('Source', 'source-a', '<p>Piszemy Czysty kod codziennie.</p>');

        $html = $this->html($source);

        $this->assertStringContainsString('class="internal-link"', $html);
        $this->assertStringContainsString('clean-code', $html);
        $this->assertStringContainsString('>Czysty kod</a>', $html);
    }

    public function test_markdown_article_links_to_db_article(): void
    {
        $this->makeDbArticle('Programowanie obiektowe', 'oop-basics', '<p>x</p>');
        $this->makeMdArticle('Src', 'src-md', 'Programowanie obiektowe to podstawa.');

        $mdArticle = app(MarkdownArticleRepository::class)->findBySlug('src-md');
        $html = implode(' ', array_column($mdArticle->getDisplayContents(), 'content'));

        $this->assertStringContainsString('class="internal-link"', $html);
        $this->assertStringContainsString('oop-basics', $html);
        $this->assertStringContainsString('>Programowanie obiektowe</a>', $html);
    }

    public function test_does_not_link_to_itself(): void
    {
        // Jedyny artykuł – jego własna nazwa w treści nie może zostać podlinkowana.
        $article = $this->makeDbArticle('Refaktoryzacja kodu', 'refaktoryzacja', '<p>Refaktoryzacja kodu jest ważna.</p>');

        $html = $this->html($article);

        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_respects_max_links_and_one_link_per_target(): void
    {
        foreach (['Alpha Wzorzec', 'Beta Wzorzec', 'Gamma Wzorzec', 'Delta Wzorzec'] as $i => $name) {
            $this->makeDbArticle($name, 'target-' . $i, '<p>x</p>');
        }

        // Każda nazwa w osobnym segmencie (akapicie); "Alpha Wzorzec" dwa razy.
        $body = '<p>Alpha Wzorzec</p><p>Alpha Wzorzec</p><p>Beta Wzorzec</p><p>Gamma Wzorzec</p><p>Delta Wzorzec</p>';
        $source = $this->makeDbArticle('Src', 'src-max', $body);

        $html = $this->html($source);

        // Limit 3 linków na artykuł.
        $this->assertSame(3, substr_count($html, 'class="internal-link"'));
        // "Alpha Wzorzec" tylko raz (jeden link na cel), mimo dwóch wystąpień.
        $this->assertSame(1, substr_count($html, 'target-0'));
    }

    public function test_skips_code_headings_and_existing_links(): void
    {
        $this->makeDbArticle('Kontener zależności', 'di-container', '<p>x</p>');

        $body = '<pre><code>Kontener zależności w kodzie</code></pre>'
              . '<h2>Kontener zależności w nagłówku</h2>'
              . '<p>Zobacz <a href="https://example.com">Kontener zależności</a> tutaj.</p>'
              . '<p>Zwykły akapit: Kontener zależności do podlinkowania.</p>';
        $source = $this->makeDbArticle('Src', 'src-skip', $body);

        $html = $this->html($source);

        // Dokładnie jeden link – tylko w zwykłym akapicie.
        $this->assertSame(1, substr_count($html, 'class="internal-link"'));
        // Kod, nagłówek i istniejący link nietknięte (brak zagnieżdżonych <a>).
        $this->assertStringContainsString('<pre><code>Kontener zależności w kodzie</code></pre>', $html);
        $this->assertStringContainsString('<h2>Kontener zależności w nagłówku</h2>', $html);
        $this->assertStringContainsString('<a href="https://example.com">Kontener zależności</a>', $html);
        $this->assertStringNotContainsString('<a href="https://example.com"><a', $html);
    }

    public function test_respects_min_phrase_length_and_stopwords(): void
    {
        config()->set('articles.internal_linking.stopwords', ['java']);
        Cache::flush();

        // "Go" (2 znaki) < min_phrase_length; "Java" na liście stopwords.
        $this->makeDbArticle('Go', 'go-lang', '<p>x</p>');
        $this->makeDbArticle('Java', 'java-lang', '<p>x</p>');
        $source = $this->makeDbArticle('Src', 'src-filter', '<p>Uczymy się Go oraz Java na kursie.</p>');

        $html = $this->html($source);

        $this->assertStringNotContainsString('class="internal-link"', $html);
    }

    public function test_can_be_disabled(): void
    {
        config()->set('articles.internal_linking.enabled', false);

        $this->makeMdArticle('Czysty kod', 'clean-code-2', 'body');
        $source = $this->makeDbArticle('Source', 'source-disabled', '<p>Piszemy Czysty kod codziennie.</p>');

        $html = $this->html($source);

        $this->assertStringNotContainsString('class="internal-link"', $html);
    }

    // --- Odporność (żeby nigdy nie było 500) -------------------------------

    public function test_non_utf8_content_does_not_crash(): void
    {
        // Nie-UTF-8 nie może trafić do kolumny JSON (baza odrzuca) ani z .md
        // (parser normalizuje kodowanie), ale guard w linkerze i tak musi być
        // odporny — testujemy go bezpośrednio, bez zapisu złych bajtów.
        $this->makeDbArticle('Programowanie obiektowe', 'oop-utf', '<p>x</p>');
        $self = $this->makeDbArticle('Src', 'src-utf', '<p>x</p>');

        $blocks = [['type' => 'text', 'content' => "<p>Zepsute \xFF\xFE bajty i Programowanie obiektowe.</p>"]];
        $result = app(InternalLinker::class)->linkContents($blocks, $self);

        // Brak wyjątku; blok z wadliwym kodowaniem zwrócony bez zmian.
        $this->assertSame($blocks[0]['content'], $result[0]['content']);
    }

    public function test_target_name_with_regex_special_chars_does_not_crash(): void
    {
        $this->makeDbArticle('C++ (zaawansowane) [v2]', 'cpp-adv', '<p>x</p>');
        $source = $this->makeDbArticle('Src', 'src-regex', '<p>Uczymy się C++ (zaawansowane) [v2] dzisiaj.</p>');

        // Nie może rzucić wyjątku; link jest opcjonalny.
        $html = $this->html($source);
        $this->assertIsString($html);
    }

    public function test_symbolic_target_name_is_skipped(): void
    {
        $this->makeDbArticle('***', 'symbolic', '<p>x</p>');
        $this->makeDbArticle('----', 'dashes', '<p>x</p>');
        $source = $this->makeDbArticle('Src', 'src-symbolic', '<p>Tekst z *** i ---- w środku.</p>');

        $html = $this->html($source);
        $this->assertStringNotContainsString('class="internal-link"', $html);
    }

    public function test_garbage_markdown_file_does_not_break_rendering(): void
    {
        // Plik-śmieć (binarny) w katalogu artykułów .md.
        File::put($this->articlesDir . DIRECTORY_SEPARATOR . 'garbage.md', "\x00\xFF\xFE\x01 not a real article \x80\x81");
        $this->makeMdArticle('Wzorce projektowe', 'patterns-ok', 'Treść.');
        $source = $this->makeDbArticle('Src', 'src-garbage', '<p>Poznajemy Wzorce projektowe krok po kroku.</p>');

        // Render nie może się wywalić mimo uszkodzonego pliku obok.
        $html = $this->html($source);
        $this->assertIsString($html);
    }

    public function test_many_targets_render_without_error(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->makeDbArticle('Temat numer ' . $i . ' xyz', 'many-' . $i, '<p>x</p>');
        }
        $body = '<p>Temat numer 1 xyz</p><p>Temat numer 2 xyz</p><p>Temat numer 3 xyz</p>';
        $source = $this->makeDbArticle('Src', 'src-many', $body);

        $html = $this->html($source);
        $this->assertSame(3, substr_count($html, 'class="internal-link"'));
    }

    public function test_image_and_empty_blocks_are_untouched(): void
    {
        $this->makeDbArticle('Programowanie obiektowe', 'oop-img', '<p>x</p>');
        $source = Article::create([
            'name' => 'Src', 'slug' => 'src-img', 'is_published' => true, 'language' => 'en', 'type' => 'normal',
            'contents' => [
                ['type' => 'image', 'content' => 'https://example.com/a.png', 'alt' => 'Programowanie obiektowe'],
                ['type' => 'text', 'content' => ''],
                ['type' => 'text', 'content' => '<p>Programowanie obiektowe rządzi.</p>'],
            ],
        ]);

        $blocks = $source->getDisplayContents();

        // Blok obrazka i pusty nietknięte; link tylko w bloku tekstowym.
        $this->assertSame('https://example.com/a.png', $blocks[0]['content']);
        $this->assertStringContainsString('class="internal-link"', $blocks[2]['content']);
    }
}
