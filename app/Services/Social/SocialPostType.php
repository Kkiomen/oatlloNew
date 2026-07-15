<?php

namespace App\Services\Social;

/**
 * Typ posta social media. Steruje kanwą, widokiem i dozwoloną liczbą slajdów.
 *
 * Kanwy: feed Instagrama 4:5 (1080x1350 – najwyższy dozwolony kafelek, zajmuje
 * najwięcej ekranu), story/reel 9:16 (1080x1920).
 */
enum SocialPostType: string
{
    case Carousel = 'carousel';
    case Quote = 'quote';
    case Announce = 'announce';
    case Story = 'story';

    /**
     * @return array{width:int, height:int}
     */
    public function canvas(): array
    {
        return match ($this) {
            self::Story => ['width' => 1080, 'height' => 1920],
            default     => ['width' => 1080, 'height' => 1350],
        };
    }

    public function width(): int
    {
        return $this->canvas()['width'];
    }

    public function height(): int
    {
        return $this->canvas()['height'];
    }

    /**
     * Widok Blade renderujący pojedynczy slajd tego typu.
     */
    public function view(): string
    {
        return 'social.' . $this->value;
    }

    /**
     * Dozwolona liczba slajdów [min, max]. Karuzela Instagrama przyjmuje max 10.
     *
     * @return array{0:int, 1:int}
     */
    public function slideRange(): array
    {
        return match ($this) {
            self::Carousel => [2, 10],
            default        => [1, 1],
        };
    }

    /**
     * Czy ten typ jest wieloslajdowy (karuzela).
     */
    public function isMultiSlide(): bool
    {
        return $this->slideRange()[1] > 1;
    }

    public function label(): string
    {
        return match ($this) {
            self::Carousel => 'Carousel',
            self::Quote    => 'Code / quote',
            self::Announce => 'Announcement',
            self::Story    => 'Story',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
