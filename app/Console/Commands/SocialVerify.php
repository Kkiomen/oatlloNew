<?php

namespace App\Console\Commands;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\Review\SocialVerification;
use App\Services\Social\Review\SocialVerificationStamp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Wstawia do posta pieczątkę weryfikacji merytorycznej i pokazuje jej stan.
 *
 * PO CO, skoro jest `social:lint`: lint pilnuje FORMATU – budżetów znaków, kolumn
 * kodu, znaków spoza fontu. Post może przejść lint idealnie i kłamać. „Xdebug
 * słucha na 9000", „Anthropic ma endpoint embeddings", literówka w nazwie metody:
 * lint nie widzi żadnej z tych rzeczy, a każda kończy się komentarzem pod postem.
 *
 * Sama komenda NICZEGO NIE SPRAWDZA – to Claude czyta post i artykuł źródłowy
 * (skill `social-verify`), a tu tylko utrwala werdykt. Trzymanie pieczątkowania
 * w komendzie, a nie w ręcznej edycji pliku, gwarantuje, że odcisk policzy się
 * tak samo za każdym razem.
 */
class SocialVerify extends Command
{
    protected $signature = 'social:verify
                            {slug? : Slug posta. Puste = pokaż stan wszystkich}
                            {--verdict=approved : approved|issues}
                            {--check=* : Co sprawdzono (można podać wielokrotnie)}
                            {--note= : Uwagi dla recenzenta}
                            {--status : Tylko pokaż stan, nic nie zapisuj}';

    protected $description = 'Stempluje post weryfikacją merytoryczną (Claude) i pokazuje jej stan';

    public function handle(MarkdownSocialPostRepository $repository): int
    {
        $slug = $this->argument('slug');

        if ($slug === null || $this->option('status')) {
            return $this->showStatus($repository, $slug);
        }

        $raw = $repository->raw($slug);

        if ($raw === null) {
            $this->error("Nie ma posta '{$slug}' w " . config('social.path') . '.');

            return self::FAILURE;
        }

        $verdict = (string) $this->option('verdict');

        if (! in_array($verdict, [SocialVerification::APPROVED, SocialVerification::ISSUES], true)) {
            $this->error("--verdict musi być 'approved' albo 'issues'.");

            return self::FAILURE;
        }

        $stamped = SocialVerificationStamp::apply(
            raw: $raw,
            verdict: $verdict,
            checks: (array) $this->option('check'),
            notes: (string) $this->option('note'),
        );

        File::put($repository->pathForSlug($slug), $stamped);

        $this->info(($verdict === SocialVerification::APPROVED ? '✓ zweryfikowany' : '⚠ uwagi') . ": {$slug}");
        $this->line('  odcisk treści: ' . substr(SocialVerificationStamp::contentFingerprint($stamped), 0, 12));
        $this->line('  Poprawka treści unieważni tę pieczątkę – wtedy zweryfikuj ponownie.');

        return self::SUCCESS;
    }

    private function showStatus(MarkdownSocialPostRepository $repository, ?string $only): int
    {
        $rows = [];
        $counts = ['zweryfikowane' => 0, 'uwagi' => 0, 'nieaktualne' => 0, 'brak' => 0];

        foreach ($repository->all() as $post) {
            if ($only !== null && $post->slug !== $only) {
                continue;
            }

            $raw = (string) $repository->raw($post->slug);
            $state = $this->stateOf($post, $raw);
            $counts[$state]++;

            if ($state !== 'zweryfikowane') {
                $rows[] = [$post->slug, $state, $post->verified?->at?->format('Y-m-d H:i') ?? '—'];
            }
        }

        if ($rows !== []) {
            $this->table(['post', 'stan', 'kiedy'], array_slice($rows, 0, 40));

            if (count($rows) > 40) {
                $this->line('  ... i ' . (count($rows) - 40) . ' więcej');
            }
        }

        $this->newLine();
        $this->line(sprintf(
            'zweryfikowane: %d  |  uwagi: %d  |  nieaktualne: %d  |  brak: %d',
            $counts['zweryfikowane'], $counts['uwagi'], $counts['nieaktualne'], $counts['brak'],
        ));

        return self::SUCCESS;
    }

    private function stateOf(\App\Services\Social\SocialPost $post, string $raw): string
    {
        if ($post->verified === null) {
            return 'brak';
        }

        if (! $post->verified->matches(SocialVerificationStamp::contentFingerprint($raw))) {
            return 'nieaktualne';
        }

        return $post->verified->isApproved() ? 'zweryfikowane' : 'uwagi';
    }
}
