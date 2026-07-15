<?php

namespace App\Services\Social;

use App\Services\Theme\TechThemeResolver;
use Illuminate\Support\Str;

/**
 * Renderuje slajdy posta social media do samowystarczalnych dokumentów HTML.
 *
 * DLACZEGO HTML, a nie SVG jak okładki artykułów/kursów:
 *  - cel jest RASTROWY (Instagram nie przyjmie SVG), więc plik i tak przechodzi
 *    przez headless – a przeglądarka łamie tekst NAPRAWDĘ. Odpada cała ręczna
 *    matematyka z CourseCoverImageService (CHAR_WIDTH_RATIO, layoutTitle, wrap),
 *    która tylko przybliża szerokość znaku,
 *  - font da się wkleić w base64, więc dokument nie ma podzasobów i renderuje
 *    się identycznie spod file:// bez serwera.
 *
 * Okładki SVG zostają jakie są – są serwowane po HTTP i mają być małe. Inne
 * ograniczenia, inne narzędzie. Ta rozbieżność jest ZAMIERZONA.
 */
class SocialImageService
{
    /**
     * Stos monospace do bloków kodu. Nie wklejamy fontu mono w base64, bo repo
     * go nie ma; fallback monospace->monospace (Consolas/Menlo) jest nieszkodliwy,
     * w przeciwieństwie do braku Montserrata, który spadał na font PROPORCJONALNY
     * i psuł łamanie linii.
     */
    private const MONO = "'JetBrains Mono','Fira Code',ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace";

    /** Ciemny i jasny atrament – wybierane kontrastem, nie na oko. */
    private const INK_DARK = '#0b1120';

    private const INK_LIGHT = '#f8fafc';

    public function __construct(
        private TechThemeResolver $themes,
        private EmbeddedFontProvider $fonts,
        private SocialStyleResolver $styles,
    ) {
    }

    /**
     * Jeden samowystarczalny dokument HTML na slajd, w kolejności slajdów.
     *
     * @param  string|null  $styleOverride  Wymusza skórkę (podgląd pakietu, `--style=`)
     * @return list<string>
     */
    public function renderPost(SocialPost $post, ?string $styleOverride = null): array
    {
        return array_map(
            fn (SocialSlide $slide) => $this->renderSlide($post, $slide, $styleOverride),
            $post->slides,
        );
    }

    public function renderSlide(SocialPost $post, SocialSlide $slide, ?string $styleOverride = null): string
    {
        $theme = $this->theme($post);
        $canvas = $post->type->canvas();
        [$brandName, $brandTld] = $this->splitBrand();

        $style = $styleOverride !== null && $this->styles->exists($styleOverride)
            ? $styleOverride
            : $this->styles->resolve($post);

        return view($post->type->view(), array_merge([
            'post'          => $post,
            'slide'         => $slide,
            'width'         => $canvas['width'],
            'height'        => $canvas['height'],
            'style'         => $style,
            'styleChrome'   => $this->styles->chrome($style),
            'accent'        => $theme['accent'],
            'accentSoft'    => $this->rgba($theme['accent'], 0.16),
            'accentFaint'   => $this->rgba($theme['accent'], 0.08),
            // Atrament DLA TŁA W KOLORZE AKCENTU (styl spotlight). Liczony
            // z kontrastu, bo akcenty bywają jasne (amber) i ciemne (czerwień
            // Laravela) – sztywny kolor byłby nieczytelny na połowie z nich.
            'accentInk'     => $this->inkFor($theme['accent']),
            'accentInkSoft' => $this->inkFor($theme['accent']) === self::INK_DARK
                ? 'rgba(11,17,32,0.72)'
                : 'rgba(248,250,252,0.75)',
            'label'         => $theme['label'],
            'logo'          => (string) ($theme['logo'] ?? ''),
            'fontCss'       => $this->fonts->css(),
            'mono'          => self::MONO,
            'headlineClass' => $this->headlineClass($slide),
            'linkHost'      => $post->linkHost(),
            'sourcePath'    => $this->sourcePath($post),
            'announceLabel' => $post->sourceType === 'course' ? 'Free course' : 'New article',
            'brandName'     => $brandName,
            'brandTld'      => $brandTld,
            'brandHandle'   => (string) config('social.brand.handle', '@oatllo'),
        ], $this->canvasMetrics($post->type), $this->codeParts($slide)))->render();
    }

    /**
     * Dobiera czytelny kolor tekstu na tle o podanym kolorze.
     *
     * Liczone luminancją względną (WCAG 2.x), nie na oko: akcent amber (#fbbf24)
     * wymaga ciemnego tekstu, a czerwień Laravela (#ff2d20) jasnego. Bez tego styl
     * 'spotlight' byłby nieczytelny na połowie technologii.
     */
    public function inkFor(string $background): string
    {
        [$r, $g, $b] = $this->rgbChannels($background) ?? [11, 17, 32];

        $luminance = 0.2126 * $this->linearize($r)
            + 0.7152 * $this->linearize($g)
            + 0.0722 * $this->linearize($b);

        // Kontrast wobec bieli vs wobec czerni – wygrywa wyższy.
        $contrastWithLight = 1.05 / ($luminance + 0.05);
        $contrastWithDark = ($luminance + 0.05) / 0.05;

        return $contrastWithDark >= $contrastWithLight ? self::INK_DARK : self::INK_LIGHT;
    }

    /**
     * Kanał sRGB -> wartość liniowa (krok wymagany przez wzór na luminancję).
     */
    private function linearize(int $channel): float
    {
        $c = $channel / 255;

        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }

    /**
     * Rozbija wyrenderowany slajd na kod i prozę.
     *
     * Widok `quote` składa je osobno: kod trafia do "okna kodu" z paskiem
     * tytułowym, a proza pod nie jako puenta. Widok `carousel` ich nie używa –
     * tam kod jest zwykłym elementem treści.
     *
     * @return array{codeHtml:string, proseHtml:string, fileName:string}
     */
    private function codeParts(SocialSlide $slide): array
    {
        $code = '';
        $prose = $slide->html;

        if (preg_match_all('/<pre>.*?<\/pre>/s', $slide->html, $m)) {
            $code = implode('', $m[0]);
            $prose = trim((string) preg_replace('/<pre>.*?<\/pre>/s', '', $slide->html));
        }

        return [
            'codeHtml'  => $code,
            'proseHtml' => $prose,
            'fileName'  => $slide->codeLanguage() ?? 'oatllo',
        ];
    }

    /**
     * Marginesy i znak wodny zależne od kanwy.
     *
     * Story ma OGROMNE marginesy góra/dół, bo interfejs Instagrama (avatar i
     * pasek postępu u góry, pole odpowiedzi u dołu) zasłania te pasy. Cokolwiek
     * tam wyląduje, jest nieczytelne.
     *
     * @return array<string, int>
     */
    private function canvasMetrics(SocialPostType $type): array
    {
        return $type === SocialPostType::Story
            ? ['padTop' => 250, 'padBottom' => 320, 'watermarkSize' => 380, 'watermarkBottom' => 430]
            : ['padTop' => 96, 'padBottom' => 96, 'watermarkSize' => 400, 'watermarkBottom' => 168];
    }

    /**
     * Ścieżka linku (np. "/course/docker-basics") – widok zapowiedzi skleja ją
     * z hostem, żeby pokazać pełny adres bez "https://".
     */
    private function sourcePath(SocialPost $post): string
    {
        if ($post->link === null) {
            return '';
        }

        $path = parse_url($post->link, PHP_URL_PATH);

        return is_string($path) ? rtrim($path, '/') : '';
    }

    /**
     * Motyw technologii (logo + akcent + etykieta) – ten sam resolver co okładki
     * kursów, więc kurs o Dockerze i post o Dockerze mają identyczną identyfikację.
     *
     * Gdy jednak NIC nie pasuje, social CELOWO nie bierze motywu 'default' z
     * config/course-covers.php. Tamten fallback ma kształt kursu (czapka absolwenta
     * + etykieta "Free course") i na poście o cachingu czy code review byłby po
     * prostu nieprawdziwy. To granica wspólnego resolvera: dopasowanie technologii
     * jest wspólne, ale "co, gdy nie ma technologii" to już decyzja produktu.
     *
     * @return array{accent:string, accent_color:string, label:string, logo:string}
     */
    public function theme(SocialPost $post): array
    {
        $key = $this->themes->keyFromText($post->themeHaystack());

        return $key === null
            ? $this->fallbackTheme($post)
            : (array) config('course-covers.themes.' . $key);
    }

    /**
     * Motyw dla treści bez technologii.
     *
     * Akcent rotowany po crc32(slug) – inaczej KAŻDY luźny post byłby emerald i
     * feed znudziłby się kolorem dokładnie tak, jak wcześniej znudziłby się stylem.
     * crc32, nie rand(): ten sam post musi renderować się identycznie przy każdym
     * eksporcie. Bez logo – nie ma marki, której moglibyśmy uczciwie użyć.
     *
     * @return array{accent:string, accent_color:string, label:string, logo:string}
     */
    private function fallbackTheme(SocialPost $post): array
    {
        /** @var list<array{accent:string, accent_color:string}> $palette */
        $palette = array_values((array) config('social.fallback_theme.accents', []));

        $accent = $palette === []
            ? ['accent' => '#fb7185', 'accent_color' => 'rose']
            : $palette[crc32($post->slug) % count($palette)];

        return [
            'accent'       => $accent['accent'],
            'accent_color' => $accent['accent_color'],
            // `topic:` autora jest lepszą etykietą niż cokolwiek, co zgadniemy.
            'label'        => $this->fallbackLabel($post),
            'logo'         => '',
        ];
    }

    private function fallbackLabel(SocialPost $post): string
    {
        $topic = trim((string) $post->topic);

        return $topic !== ''
            ? Str::headline($topic)
            : (string) config('social.fallback_theme.label', 'Oatllo');
    }

    /**
     * Dobiera klasę skali nagłówka do jego długości.
     *
     * To NIE jest liczenie szerokości tekstu (tym zajmuje się CSS) – tylko wybór
     * jednego z czterech stopni, żeby krótki hook był wielki, a długi się mieścił.
     * Hook dostaje większą skalę: to on jest miniaturą w feedzie.
     */
    private function headlineClass(SocialSlide $slide): string
    {
        $length = mb_strlen((string) $slide->headline);

        $scale = $slide->isHook()
            ? [28 => 'h-xxl', 48 => 'h-xl', 70 => 'h-l']
            : [30 => 'h-xl', 55 => 'h-l'];

        foreach ($scale as $max => $class) {
            if ($length <= $max) {
                return $class;
            }
        }

        return 'h-m';
    }

    /**
     * Rozbija markę na "oatllo" + ".com", żeby TLD dostało kolor akcentu –
     * ten sam zabieg co w okładkach (covers/course-cover.blade.php).
     *
     * @return array{0:string, 1:string}
     */
    private function splitBrand(): array
    {
        $domain = (string) config('social.brand.domain', 'oatllo.com');
        $dot = strpos($domain, '.');

        return $dot === false
            ? [$domain, '']
            : [substr($domain, 0, $dot), substr($domain, $dot)];
    }

    /**
     * Hex -> rgba(). Liczymy w PHP zamiast używać color-mix()/opacity w CSS:
     * opacity przygasiłoby też tekst w pigułce, a color-mix() to zbędne ryzyko
     * zależne od wersji przeglądarki.
     */
    private function rgba(string $hex, float $alpha): string
    {
        $channels = $this->rgbChannels($hex);

        if ($channels === null) {
            return "rgba(148,163,184,{$alpha})";
        }

        [$r, $g, $b] = $channels;

        return "rgba({$r},{$g},{$b},{$alpha})";
    }

    /**
     * Hex (#abc lub #aabbcc) -> [r, g, b]. null gdy wartość nie jest kolorem.
     *
     * @return array{0:int, 1:int, 2:int}|null
     */
    private function rgbChannels(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        /** @var array{0:int, 1:int, 2:int} $channels */
        $channels = array_map('hexdec', str_split($hex, 2));

        return $channels;
    }
}
