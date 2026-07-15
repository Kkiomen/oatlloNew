<?php

namespace App\Console\Commands;

use App\Services\Social\InvalidSocialPost;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostType;
use Illuminate\Console\Command;

/**
 * Przegląd kolejki postów social media z plików .md.
 *
 * To zwykły listing plików – nie ma bazy ani schedulera, więc `publish_at` to
 * tylko notatka autora, a nie termin, o którym coś pamięta.
 */
class SocialList extends Command
{
    protected $signature = 'social:list
                            {--status= : Filtruj po statusie (draft|ready|published)}
                            {--type= : Filtruj po typie (carousel|quote|announce|story)}';

    protected $description = 'Wypisuje posty social media z resources/social (nic nie publikuje)';

    public function handle(MarkdownSocialPostRepository $repository): int
    {
        $files = $repository->files();

        if ($files === []) {
            $this->warn('Brak postów w ' . $repository->directory());

            return self::SUCCESS;
        }

        $status = $this->option('status');
        $type = $this->option('type');

        if ($type !== null && SocialPostType::tryFrom($type) === null) {
            $this->error("Nieznany typ '{$type}'. Dozwolone: " . implode(', ', SocialPostType::values()));

            return self::FAILURE;
        }

        $rows = [];
        $broken = 0;

        foreach ($files as $path) {
            try {
                $post = $repository->fromPath($path);
            } catch (InvalidSocialPost $e) {
                $broken++;
                $rows[] = [
                    '<fg=red>' . pathinfo($path, PATHINFO_FILENAME) . '</>',
                    '<fg=red>BŁĄD</>', '-', '-', '-',
                    '<fg=red>' . $e->getMessage() . '</>',
                ];

                continue;
            }

            if ($status !== null && $post->status !== $status) {
                continue;
            }

            if ($type !== null && $post->type->value !== $type) {
                continue;
            }

            $canvas = $post->type->canvas();

            $rows[] = [
                $post->slug,
                $post->type->value,
                $this->colorStatus($post->status),
                $post->publishAt?->format('Y-m-d H:i') ?? '-',
                $post->slideCount(),
                $canvas['width'] . 'x' . $canvas['height'],
            ];
        }

        if ($rows === []) {
            $this->warn('Żaden post nie pasuje do filtrów.');

            return self::SUCCESS;
        }

        $this->table(['Slug', 'Typ', 'Status', 'Publish at', 'Slajdy', 'Kanwa'], $rows);

        if ($broken > 0) {
            $this->newLine();
            $this->error("{$broken} plik(ów) nie da się sparsować – odpal `php artisan social:lint --all`.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function colorStatus(string $status): string
    {
        return match ($status) {
            SocialPost::STATUS_READY     => '<fg=green>ready</>',
            SocialPost::STATUS_PUBLISHED => '<fg=gray>published</>',
            SocialPost::STATUS_DRAFT     => '<fg=yellow>draft</>',
            default                      => "<fg=red>{$status}</>",
        };
    }
}
