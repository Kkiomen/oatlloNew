<?php

namespace App\Console\Commands;

use App\Services\Social\Review\SocialReviewItem;
use App\Services\Social\Review\SocialReviewQueue;
use Illuminate\Console\Command;

/**
 * Odczyt werdyktów z panelu /social/review.
 *
 * To jest wejście dla skilla `social-review`: `--changes --json` zwraca dokładnie
 * te posty, które człowiek odesłał do poprawy, razem z powodem i ścieżką pliku.
 * Komenda niczego nie zapisuje – werdykty stawia człowiek w panelu.
 */
class SocialReviewList extends Command
{
    protected $signature = 'social:review
                            {--changes : Tylko posty odesłane do poprawy}
                            {--approved : Tylko posty z zielonym światłem}
                            {--pending : Tylko posty bez aktualnego werdyktu}
                            {--json : Wypisz JSON (dla skilla / skryptów)}';

    protected $description = 'Pokazuje werdykty recenzji postów social media (resources/social/reviews)';

    public function handle(SocialReviewQueue $queue): int
    {
        $items = match (true) {
            (bool) $this->option('changes')  => $queue->needingWork(),
            (bool) $this->option('approved') => $queue->approved(),
            (bool) $this->option('pending')  => $queue->pending(),
            default                          => $queue->items(),
        };

        if ($this->option('json')) {
            $this->line(json_encode(
                $items->map(fn (SocialReviewItem $i) => $this->toArray($i))->values()->all(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));

            return self::SUCCESS;
        }

        if ($items->isEmpty()) {
            $this->warn('Nic nie pasuje do filtrów.');

            return self::SUCCESS;
        }

        $this->table(
            ['Slug', 'Werdykt', 'Obejrzano', 'Powód'],
            $items->map(fn (SocialReviewItem $i) => [
                $i->post->slug,
                $this->colorState($i->state()),
                $i->review?->reviewedAt?->format('Y-m-d H:i') ?? '-',
                $i->needsWork() ? \Illuminate\Support\Str::limit(str_replace("\n", ' ', $i->review->reason), 60) : '-',
            ])->all(),
        );

        $summary = $queue->summary();
        $this->newLine();
        $this->line(sprintf(
            'Razem: %d  |  zielone: %d  |  do poprawy: %d  |  do przejrzenia: %d',
            $summary['total'],
            $summary['approved'],
            $summary['changes'],
            $summary['pending'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(SocialReviewItem $item): array
    {
        return [
            'slug'        => $item->post->slug,
            'state'       => $item->state(),
            'type'        => $item->post->type->value,
            'status'      => $item->post->status,
            'post_path'   => $item->path,
            'reason'      => $item->review?->reason,
            'reviewed_at' => $item->review?->reviewedAt?->format('Y-m-d H:i'),
        ];
    }

    private function colorState(string $state): string
    {
        return match ($state) {
            'approved' => '<fg=green>approved</>',
            'changes'  => '<fg=red>do poprawy</>',
            'stale'    => '<fg=yellow>zmieniony po werdykcie</>',
            default    => '<fg=gray>do przejrzenia</>',
        };
    }
}
