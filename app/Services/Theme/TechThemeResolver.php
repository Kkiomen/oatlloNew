<?php

namespace App\Services\Theme;

use Illuminate\Support\Str;

/**
 * Dobiera motyw technologii (logo + akcent + etykieta) po słowach kluczowych.
 *
 * Wspólny dla okładek KURSÓW (CourseCoverImageService) i grafik SOCIAL MEDIA –
 * dzięki temu kurs o Dockerze i post o Dockerze mają to samo logo i ten sam
 * akcent, a 11 logo istnieje TYLKO w config/course-covers.php.
 *
 * NIE dotyczy okładek ARTYKUŁÓW (App\Services\Article\CoverImageService): tam
 * motyw ma zupełnie inny kształt (filename/header/comment/footer zamiast
 * logo/accent_color), więc wspólny resolver byłby fałszywym DRY – współdzielona
 * jest tylko pętla, nie pojęcie motywu.
 */
class TechThemeResolver
{
    /**
     * Zwraca motyw dla dowolnego tekstu (nazwa, slug, opis, hashtagi – cokolwiek).
     * Pierwsze pasujące słowo kluczowe wygrywa; brak trafienia => motyw 'default'.
     *
     * UWAGA: haystack MUSI być opakowany spacjami. config/course-covers.php ma
     * słowa kluczowe z prefiksem spacji (' oop', ' js', ' ts'), które udają
     * granicę słowa – bez opakowania przestają matchować PO CICHU (temat spada
     * do 'default' i nikt tego nie zauważa).
     *
     * @return array{accent:string, accent_color:string, label:string, logo:string}
     */
    public function fromText(string $text): array
    {
        $key = $this->keyFromText($text);

        return $key === null
            ? config('course-covers.default')
            : config('course-covers.themes.' . $key);
    }

    /**
     * Klucz motywu (np. 'docker') albo null, gdy NIC nie pasowało.
     *
     * Rozróżnienie "trafiono" vs "brak trafienia" jest potrzebne wywołującym,
     * którzy mają własny fallback: motyw 'default' to czapka absolwenta i etykieta
     * "Free course" – sensowne dla okładki kursu, ale na poście na Instagramie
     * o cachingu byłoby zwyczajnym kłamstwem. `fromText()` samo w sobie nie
     * potrafi tego zgłosić, bo zwraca gotowy motyw.
     */
    public function keyFromText(string $text): ?string
    {
        $haystack = ' ' . Str::lower($text) . ' ';

        foreach ((array) config('course-covers.themes', []) as $key => $theme) {
            foreach ((array) ($theme['keywords'] ?? []) as $keyword) {
                if ($keyword !== '' && str_contains($haystack, Str::lower((string) $keyword))) {
                    return (string) $key;
                }
            }
        }

        return null;
    }

    /**
     * Sama nazwa palety Tailwind (np. 'sky', 'emerald') – akcent całej strony.
     */
    public function accentColorFromText(string $text): string
    {
        return $this->fromText($text)['accent_color']
            ?? config('course-covers.default.accent_color', 'emerald');
    }

    /**
     * Sam kolor akcentu w hex (np. '#2496ed') – poświata, pigułka, podkreślenie.
     */
    public function accentHexFromText(string $text): string
    {
        return $this->fromText($text)['accent']
            ?? config('course-covers.default.accent', '#34d399');
    }
}
