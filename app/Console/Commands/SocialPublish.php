<?php

namespace App\Console\Commands;

use App\Services\Social\Export\SocialExporter;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\Publisher\SocialPublisher;
use App\Services\Social\Rasterizer\RasterizationFailed;
use Illuminate\Console\Command;

/**
 * "Publikuje" posta przez skonfigurowany publisher.
 *
 * Dziś to FolderPublisher: eksportuje grafiki i wypisuje checklistę ręcznego
 * wrzucenia. Komenda istnieje JUŻ TERAZ, żeby dołożenie Instagram Graph API było
 * podmianą jednej pozycji w config/social.php – bez nowej komendy i bez zmiany
 * nawyku.
 */
class SocialPublish extends Command
{
    protected $signature = 'social:publish
                            {slug : Slug posta}
                            {--dry-run : Pokaż plan, nie eksportuj i nie publikuj}
                            {--out= : Katalog docelowy eksportu}';

    protected $description = 'Przygotowuje post do publikacji przez skonfigurowany publisher';

    public function handle(
        MarkdownSocialPostRepository $repository,
        SocialExporter $exporter,
        SocialPublisher $publisher,
    ): int {
        $slug = (string) $this->argument('slug');
        $post = $repository->findBySlug($slug);

        if ($post === null) {
            $this->error("Nie ma posta '{$slug}' w {$repository->directory()}.");

            return self::FAILURE;
        }

        if (! $publisher->supports($post->type)) {
            $this->error("Publisher '{$publisher->name()}' nie obsługuje typu '{$post->type->value}'.");

            return self::FAILURE;
        }

        $this->line("Publisher: <info>{$publisher->name()}</info>");

        if ($this->option('dry-run')) {
            $this->warn('--dry-run: nic nie zostało wyeksportowane ani opublikowane.');
            $this->line("  Post:    {$post->slug} [{$post->type->value}], {$post->slideCount()} slajd(ów)");
            $this->line('  Status:  ' . $post->status);
            $this->line('  Termin:  ' . ($post->publishAt?->format('Y-m-d H:i') ?? '-'));

            return self::SUCCESS;
        }

        try {
            $export = $exporter->export($post, $this->option('out') ?: null);
        } catch (RasterizationFailed $e) {
            $this->error('Eksport padł: ' . $e->getMessage());

            return self::FAILURE;
        }

        $result = $publisher->publish($post, $export);

        $this->newLine();
        $this->line($result->published ? "<info>{$result->summary}</info>" : "<comment>{$result->summary}</comment>");

        if ($result->instructions !== []) {
            $this->newLine();
            foreach ($result->instructions as $i => $step) {
                $this->line('  ' . ($i + 1) . '. ' . $step);
            }
        }

        if ($result->url !== null) {
            $this->newLine();
            $this->info('URL: ' . $result->url);
        }

        return self::SUCCESS;
    }
}
