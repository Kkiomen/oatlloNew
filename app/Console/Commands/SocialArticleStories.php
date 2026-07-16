<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Article\MarkdownArticleRepository;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\Review\SocialVerification;
use App\Services\Social\Review\SocialVerificationStamp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generuje story "nowy artykuł na blogu" – jedno na każdy ZAKOLEJKOWANY artykuł.
 *
 * PO CO: chcemy na Instagram Story informować o każdym publikowanym artykule
 * ("Słuchajcie, jest nowy artykuł, wejdźcie i przeczytajcie"). Artykuły .md
 * publikują się ~3/tydzień z `published_at` w przyszłości, więc to naturalnie
 * daje 3-4 dodatkowe story tygodniowo. Story datujemy na DZIEŃ publikacji
 * artykułu, żeby wychodziło razem z nim.
 *
 * Story to zwykły plik .md w resources/social/ – ta sama architektura co posty,
 * kursy i artykuły: commit + deploy, zero bazy, zero crona. Ten generator NICZEGO
 * NIE PUBLIKUJE; tworzy pliki, które dalej przechodzą przez lint, panel recenzji
 * i (opcjonalnie) autopublikację jak każdy inny post.
 *
 * IDEMPOTENTNY: istniejącego story NIE nadpisuje (chyba że --force), więc ręczne
 * poprawki człowieka przeżywają ponowne uruchomienie. Skorygowanie artykułu i tak
 * nie rusza story – to dwa osobne pliki.
 *
 * Story dostaje od razu pieczątkę `verified: approved`: tytuł i link są WYLICZONE
 * z artykułu (nie przepisane ręcznie), więc fakt jest poprawny z definicji. Odcisk
 * liczy ta sama metoda co `social:verify`, więc panel pokaże zielone "zweryfikowane".
 * Człowiek nadal MUSI zaakceptować story w panelu, zanim tick je opublikuje.
 */
class SocialArticleStories extends Command
{
    protected $signature = 'social:article-stories
                            {--all : Weź też artykuły już opublikowane (domyślnie: tylko zakolejkowane, z datą w przyszłości)}
                            {--force : Nadpisz istniejące pliki story (domyślnie: pomiń, żeby nie kasować ręcznych poprawek)}
                            {--limit= : Maksymalna liczba story do utworzenia}
                            {--dry-run : Pokaż, co powstałoby, ale nic nie zapisuj}';

    protected $description = 'Generuje story "nowy artykuł" (.md) dla zakolejkowanych artykułów';

    public function handle(
        MarkdownArticleRepository $articles,
        MarkdownSocialPostRepository $social,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;

        $queue = $this->queuedArticles($articles);

        if ($queue->isEmpty()) {
            $this->warn($this->option('all')
                ? 'Brak artykułów z tytułem i datą publikacji.'
                : 'Brak zakolejkowanych artykułów (z datą publikacji w przyszłości). Użyj --all, aby wziąć wszystkie.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $rows = [];

        foreach ($queue as $article) {
            if ($limit !== null && $created >= $limit) {
                break;
            }

            $storySlug = $this->storySlug($article->slug);
            $path = $social->pathForSlug($storySlug);

            if (File::exists($path) && ! $force) {
                $skipped++;

                continue;
            }

            $raw = $this->stampVerified(
                $this->buildStory($article),
                $article,
            );

            if (! $dryRun) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $raw);
            }

            $created++;
            $rows[] = [$storySlug, $article->published_at->format('Y-m-d'), Str::limit($article->name, 42)];
        }

        if ($rows !== []) {
            $this->table(['story', 'publish_at', 'artykuł'], array_slice($rows, 0, 50));

            if (count($rows) > 50) {
                $this->line('  ... i ' . (count($rows) - 50) . ' więcej');
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s utworzone: %d  |  pominięte (już istnieją): %d',
            $dryRun ? '[dry-run] do utworzenia' : 'story',
            $created,
            $skipped,
        ));

        if (! $dryRun && $created > 0) {
            $this->line('  Następnie: php artisan social:lint  (bramka)  ->  panel /social/review');
        }

        return self::SUCCESS;
    }

    /**
     * Zakolejkowane artykuły: z tytułem i datą publikacji, domyślnie tylko te
     * z datą w przyszłości (jeszcze nie na stronie). --all bierze wszystkie.
     *
     * Sortujemy po dacie, żeby --limit brał najbliższe publikacje.
     *
     * @return \Illuminate\Support\Collection<int, Article>
     */
    private function queuedArticles(MarkdownArticleRepository $articles): \Illuminate\Support\Collection
    {
        $all = (bool) $this->option('all');

        return $articles->all()
            ->filter(fn (Article $a) => $a->slug && $a->name && $a->published_at
                && ($all || $a->published_at->isFuture()))
            ->sortBy(fn (Article $a) => $a->published_at->getTimestamp())
            ->values();
    }

    private function storySlug(string $articleSlug): string
    {
        return (string) config('social.article_story.slug_prefix', 'story-') . $articleSlug;
    }

    /**
     * Publiczny URL artykułu na PRODUKCJI. Bierzemy ścieżkę z trasy (obsługuje
     * /{slug} i /{categorySlug}/{slug}), ale host wymuszamy na brand.domain –
     * lokalny APP_URL to Herd (oatllo.test) i wpisałby martwy adres do repo.
     */
    private function articleUrl(Article $article): string
    {
        $path = parse_url($article->getRoute(true), PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/' . ltrim($article->slug, '/');

        return 'https://' . config('social.brand.domain', 'oatllo.com') . $path;
    }

    /**
     * Zawartość pliku story BEZ pieczątki weryfikacji – tę dokłada stampVerified().
     */
    private function buildStory(Article $article): string
    {
        $storySlug = $this->storySlug($article->slug);
        $link = $this->articleUrl($article);
        $title = $article->name;
        $publishAt = $article->published_at->format('Y-m-d H:i');
        $intro = (string) config('social.article_story.intro', 'Just published. Link in bio to read the full article.');
        $style = (string) config('social.article_story.style', 'announce-article');

        // caption CELOWO nie ma: story nie ma na Instagramie pola podpisu, a przy
        // formats:[story] niepusty caption to WARNING lintu. Komunikat żyje NA
        // grafice (baner + tytuł), nie w podpisie.
        $frontmatter = [
            'slug: ' . $storySlug,
            'type: story',
            'language: en',
            'title: ' . $this->yamlSingle($title),
            'source_type: article',
            'source: ' . $article->slug,
            'link: ' . $link,
            'publish_at: ' . $publishAt,
            'status: ready',
            'style: ' . $style,
            'formats: [story]',
            'notes: |',
            '  Auto-generated announcement for the new article ' . $this->yamlSingle($title) . '.',
            '  When posting the story, add a link sticker to:',
            '  ' . $link,
            '  The graphic already shows "Link in bio", so it also works standalone.',
        ];

        return "---\n"
            . implode("\n", $frontmatter) . "\n"
            . "---\n\n"
            . '## ' . $this->fitHeadline($title) . "\n\n"
            . $intro . "\n";
    }

    /**
     * Nagłówek NA grafice mieści się w budżecie (`hook_headline_max`, story ogląda
     * się ułamek sekundy). Pełny tytuł SEO artykułu bywa dłuższy, więc na kanwie
     * ucinamy podtytuł na naturalnej granicy (`:` albo `(`), a w ostateczności na
     * granicy słowa. Frontmatter `title` zostaje PEŁNY – to on karmi motyw i jest
     * referencją do artykułu; skracamy tylko to, co widać.
     */
    private function fitHeadline(string $title): string
    {
        $max = (int) config('social.limits.hook_headline_max', 70);
        $title = trim($title);

        if (mb_strlen($title) <= $max) {
            return $title;
        }

        // Podtytuł po ':' albo treść w nawiasie – zwykle da się odciąć bez straty sensu.
        foreach ([' — ', ': ', ' ('] as $sep) {
            $pos = mb_strpos($title, $sep);
            if ($pos !== false && $pos >= 15 && $pos <= $max) {
                return rtrim(mb_substr($title, 0, $pos), " \t:(—");
            }
        }

        // Ostateczność: utnij na ostatniej spacji przed budżetem (nie w środku słowa).
        $cut = mb_substr($title, 0, $max);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space !== false && $space >= 15 ? mb_substr($cut, 0, $space) : $cut, " \t,:-(");
    }

    /**
     * Dokłada `verified: approved` z odciskiem policzonym tą samą metodą co
     * `social:verify` (sha1 pliku bez bloku verified). Dzięki temu odcisk zawsze
     * pasuje do treści i panel świeci na zielono, a nie "NIEAKTUALNA".
     */
    private function stampVerified(string $raw, Article $article): string
    {
        return SocialVerificationStamp::apply(
            raw: $raw,
            verdict: SocialVerification::APPROVED,
            checks: [
                'title matches the article name in resources/articles/' . $article->slug . '.md',
                'link resolves to the article route ' . $this->articleUrl($article),
                'publish date matches the article published_at',
            ],
            notes: "Auto-generated by social:article-stories. Title and link are derived from the source "
                . "article file, so they are correct by construction; a human still approves in the panel.",
        );
    }

    /**
     * Skalar YAML w apostrofach (bez escape'ów backslasha), odporny na apostrofy
     * w tytule (np. "Laravel's Pipeline"). Ta sama zasada co w SocialVerificationStamp.
     */
    private function yamlSingle(string $value): string
    {
        return "'" . str_replace("'", "''", trim($value)) . "'";
    }
}
