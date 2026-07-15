<?php

namespace App\Services\Social\Review;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * Kolejka "tinderowa": posty, które czekają na werdykt człowieka.
 *
 * W kolejce jest post, który (a) nie ma recenzji w ogóle, albo (b) ma recenzję
 * wystawioną do INNEJ wersji pliku (patrz SocialReviewItem::isStale). Dzięki (b)
 * pętla domyka się sama: poprawiony post automatycznie wraca do obejrzenia.
 *
 * Posty `status: published` są poza kolejką – wiszą już na Instagramie, nie ma
 * czego akceptować.
 */
class SocialReviewQueue
{
    public function __construct(
        private MarkdownSocialPostRepository $posts,
        private SocialReviewRepository $reviews,
    ) {
    }

    /**
     * Wszystkie posty + ich recenzje, w kolejności wystawiania (publish_at,
     * potem slug). Pliki, których nie da się sparsować, są POMIJANE – panel ma
     * pokazywać posty, a nie umierać na literówce; od zgłaszania błędów jest
     * `social:lint`.
     *
     * @return Collection<int, SocialReviewItem>
     */
    public function items(): Collection
    {
        $reviews = $this->reviews->all();

        return collect($this->posts->files())
            ->map(function (string $path) use ($reviews): ?SocialReviewItem {
                try {
                    $post = $this->posts->fromPath($path);
                } catch (\Throwable) {
                    return null;
                }

                return new SocialReviewItem(
                    post: $post,
                    path: $path,
                    fingerprint: SocialReviewRepository::fingerprint(File::get($path)),
                    review: $reviews->get($post->slug),
                );
            })
            ->filter()
            ->sortBy([
                fn (SocialReviewItem $a, SocialReviewItem $b) => $this->compareDates($a->post, $b->post),
                fn (SocialReviewItem $a, SocialReviewItem $b) => strcmp($a->post->slug, $b->post->slug),
            ])
            ->values();
    }

    /**
     * Liczba plików postów, których nie dało się sparsować – panel mówi wtedy
     * wprost, żeby odpalić `social:lint`, zamiast cicho ukryć post.
     */
    public function brokenCount(): int
    {
        $ok = $this->items()->count();

        return max(0, count($this->posts->files()) - $ok);
    }

    /**
     * @return Collection<int, SocialReviewItem>
     */
    public function pending(): Collection
    {
        return $this->items()
            ->reject(fn (SocialReviewItem $i) => $i->post->status === SocialPost::STATUS_PUBLISHED)
            ->reject(fn (SocialReviewItem $i) => $i->isReviewed())
            ->values();
    }

    /**
     * Następny post do obejrzenia.
     */
    public function next(): ?SocialReviewItem
    {
        return $this->pending()->first();
    }

    public function find(string $slug): ?SocialReviewItem
    {
        return $this->items()->first(fn (SocialReviewItem $i) => $i->post->slug === $slug);
    }

    /**
     * Posty, które człowiek odesłał do poprawy – wejście dla skilla `social-review`.
     *
     * @return Collection<int, SocialReviewItem>
     */
    public function needingWork(): Collection
    {
        return $this->items()->filter(fn (SocialReviewItem $i) => $i->needsWork())->values();
    }

    /**
     * @return Collection<int, SocialReviewItem>
     */
    public function approved(): Collection
    {
        return $this->items()->filter(fn (SocialReviewItem $i) => $i->isApproved())->values();
    }

    /**
     * @return array{total:int, pending:int, approved:int, changes:int, stale:int}
     */
    public function summary(): array
    {
        $items = $this->items();

        return [
            'total'    => $items->count(),
            'pending'  => $this->pending()->count(),
            'approved' => $items->filter(fn (SocialReviewItem $i) => $i->isApproved())->count(),
            'changes'  => $items->filter(fn (SocialReviewItem $i) => $i->needsWork())->count(),
            'stale'    => $items->filter(fn (SocialReviewItem $i) => $i->isStale())->count(),
        ];
    }

    /**
     * Post bez `publish_at` idzie na koniec – nie ma terminu, więc nie wyprzedza
     * tych, które mają.
     */
    private function compareDates(SocialPost $a, SocialPost $b): int
    {
        $left  = $a->publishAt?->getTimestamp();
        $right = $b->publishAt?->getTimestamp();

        return match (true) {
            $left === $right   => 0,
            $left === null     => 1,
            $right === null    => -1,
            default            => $left <=> $right,
        };
    }
}
