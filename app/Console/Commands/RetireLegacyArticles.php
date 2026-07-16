<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

/**
 * Ręczne wygaszenie starych artykułów SEO-first z bazy (is_published = false).
 *
 * Lista i PEŁNE uzasadnienie: `config/articles.php` → `retired_slugs`.
 *
 * To samo robi tick `/api/cron` przy każdym strzale, więc ta komenda jest tylko
 * dla niecierpliwych i do podglądu (`--dry-run`) – nie trzeba jej pamiętać.
 *
 * `--restore` NIE WYSTARCZY do przywrócenia artykułu: dopóki slug jest w configu,
 * najbliższy tick wygasi go z powrotem. Przywrócenie = usuń slug z `retired_slugs`,
 * deploy, potem `--restore`.
 */
class RetireLegacyArticles extends Command
{
    protected $signature = 'articles:retire-legacy
                            {--dry-run : Pokaż, co by się stało, i nic nie zapisuj}
                            {--restore : Cofnij wygaszenie (is_published = true)}
                            {--force : Nie pytaj o potwierdzenie}';

    protected $description = 'Wygasza 44 stare artykuły SEO-first z bazy (odwracalne przez --restore)';

    public function handle(): int
    {
        /** @var array<int, string> $slugs */
        $slugs = config('articles.retired_slugs', []);

        if ($slugs === []) {
            $this->warn('config(articles.retired_slugs) jest puste – nie ma czego wygaszać.');

            return self::SUCCESS;
        }

        $restore = (bool) $this->option('restore');
        $dryRun = (bool) $this->option('dry-run');

        // Stan docelowy: wygaszamy => false, --restore => true.
        $target = $restore;

        $found = Article::whereIn('slug', $slugs)->get(['id', 'slug', 'is_published']);
        $missing = array_diff($slugs, $found->pluck('slug')->all());
        $toChange = $found->where('is_published', '!=', $target);

        $this->newLine();
        $this->line($restore ? '<fg=yellow>PRZYWRACANIE</> starych artykułów' : '<fg=yellow>WYGASZANIE</> starych artykułów');
        $this->line(sprintf(
            'Na liście: %d | w bazie: %d | do zmiany: %d | już w docelowym stanie: %d',
            count($slugs),
            $found->count(),
            $toChange->count(),
            $found->count() - $toChange->count(),
        ));

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Brak w bazie (' . count($missing) . ') – prawdopodobnie już usunięte:');
            foreach ($missing as $slug) {
                $this->line("  - {$slug}");
            }
        }

        if ($toChange->isEmpty()) {
            $this->newLine();
            $this->info('Nie ma nic do zrobienia.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['ID', 'Slug', 'is_published: teraz', '=> po'],
            $toChange->map(fn (Article $a) => [
                $a->id,
                $a->slug,
                $a->is_published ? '<fg=green>true</>' : '<fg=gray>false</>',
                $target ? '<fg=green>true</>' : '<fg=red>false</>',
            ])->all(),
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('--dry-run: nic nie zapisano.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Zapisać zmianę dla {$toChange->count()} artykułów?", false)) {
            $this->warn('Przerwane – nic nie zapisano.');

            return self::SUCCESS;
        }

        $changed = Article::whereIn('slug', $toChange->pluck('slug')->all())
            ->update(['is_published' => $target]);

        $this->newLine();
        $this->info("Zmieniono {$changed} artykułów.");
        $this->newLine();

        if ($restore) {
            return self::SUCCESS;
        }

        $this->line('Dalej:');
        $this->line('  1. <fg=cyan>php artisan indexnow:submit-sitemap --regenerate</> – przebuduj sitemapę i zgłoś zmiany');
        $this->line('  2. Sprawdź, że wygaszony URL zwraca 404, np. <fg=cyan>curl -I https://oatllo.com/it-freelancing-pros-cons</>');
        $this->line('  3. Cofnięcie w razie czego: <fg=cyan>php artisan articles:retire-legacy --restore</>');

        return self::SUCCESS;
    }
}
