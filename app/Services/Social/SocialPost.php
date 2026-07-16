<?php

namespace App\Services\Social;

use Carbon\CarbonImmutable;

/**
 * Post social media zbudowany z pliku .md. Niepersystowane DTO – moduł social
 * NIE MA TABELI i nigdy nie dotyka bazy.
 *
 * `publishAt` i `status` to metadane workflow człowieka: nie ma crona ani
 * schedulera, `status` filtruje jedynie to, co bierze `social:export`.
 */
final readonly class SocialPost
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_READY     = 'ready';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_READY, self::STATUS_PUBLISHED];

    /**
     * @param list<string>      $hashtags
     * @param list<string>      $formats
     * @param list<SocialSlide> $slides
     */
    public function __construct(
        public string $slug,
        public SocialPostType $type,
        public string $language,
        public string $title,
        public ?string $topic,
        /** Jawna skórka wizualna; null => dobiera ją SocialStyleResolver. */
        public ?string $style,
        public ?string $sourceType,
        public ?string $source,
        public ?string $link,
        public ?CarbonImmutable $publishAt,
        public string $status,
        public array $hashtags,
        /**
         * Co z tego posta publikujesz danego dnia (post / story / reel / video).
         * `type` mówi o KSZTAŁCIE slajdów, `formats` o tym, CO idzie w świat –
         * jedna karuzela bywa i postem w feedzie, i reelem z tych samych slajdów.
         * Metadana planu: niczego nie renderuje, karmi kalendarz.
         */
        public array $formats,
        public string $caption,
        /**
         * Instrukcja dla CZŁOWIEKA na moment wrzucania – nigdy nie idzie w świat.
         *
         * Osobne pole, bo `caption` ma jedno znaczenie: tekst do wklejenia. Notatki
         * mieszkały wcześniej właśnie w `caption` (story nie ma na Instagramie pola
         * podpisu, więc wyglądało na wolne miejsce) i wychodziły w `caption.txt`
         * oraz w panelu recenzji UDAJĄC podpis – czyli w jedynych dwóch miejscach,
         * które mówią „to wklejasz".
         *
         * Tu trafia to, czego renderer z definicji nie zrobi, bo to funkcja apki:
         * ankiety, naklejki, budowanie clustera ze story.
         */
        public string $notes,
        /**
         * Pieczątka weryfikacji merytorycznej (Claude), wstawiana ZANIM post
         * zobaczy człowiek. Lint pilnuje formatu; to pilnuje prawdy.
         */
        public ?\App\Services\Social\Review\SocialVerification $verified,
        public array $slides,
    ) {
    }

    /**
     * Czy post jest na dany dzień zaplanowany w tym formacie.
     */
    public function hasFormat(string $format): bool
    {
        return in_array($format, $this->formats, true);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function slideCount(): int
    {
        return count($this->slides);
    }

    /**
     * Pierwszy slajd (hook) – w feedzie to on jest miniaturą.
     */
    public function hook(): ?SocialSlide
    {
        return $this->slides[0] ?? null;
    }

    public function hasNotes(): bool
    {
        return trim($this->notes) !== '';
    }

    /**
     * Podpis gotowy do wklejenia: treść + pusta linia + hashtagi.
     *
     * `notes` tu NIE wchodzą – to jedyna metoda, której wynik idzie na Instagrama.
     */
    public function captionWithHashtags(): string
    {
        $caption = rtrim($this->caption);

        if ($this->hashtags === []) {
            return $caption;
        }

        $tags = implode(' ', array_map(fn (string $t) => '#' . ltrim($t, '#'), $this->hashtags));

        return $caption === '' ? $tags : $caption . "\n\n" . $tags;
    }

    /**
     * Pierwsza linia podpisu – to ona zatrzymuje scroll i tylko ona jest widoczna
     * przed "... more".
     */
    public function captionHook(): string
    {
        $lines = preg_split('/\R/', trim($this->caption)) ?: [];

        return trim($lines[0] ?? '');
    }

    /**
     * Tekst, po którym dobierany jest motyw technologii (logo + akcent).
     * Jawny `topic:` we frontmatterze wygrywa; inaczej zgadujemy z tytułu,
     * sluga, źródła i hashtagów.
     */
    public function themeHaystack(): string
    {
        if ($this->topic !== null && trim($this->topic) !== '') {
            return $this->topic;
        }

        return implode(' ', array_filter([
            $this->title,
            $this->slug,
            $this->source,
            implode(' ', $this->hashtags),
        ]));
    }

    /**
     * Host linku CTA (np. "oatllo.com") – do wyświetlenia na slajdzie CTA.
     */
    public function linkHost(): ?string
    {
        if ($this->link === null || $this->link === '') {
            return null;
        }

        $host = parse_url($this->link, PHP_URL_HOST);

        return is_string($host) ? preg_replace('/^www\./', '', $host) : null;
    }
}
