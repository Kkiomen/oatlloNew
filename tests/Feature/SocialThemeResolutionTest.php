<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\Course\CourseCoverImageService;
use App\Services\Course\MarkdownCourseRepository;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Theme\TechThemeResolver;
use Tests\TestCase;

/**
 * Dobór motywu technologii (logo + akcent), współdzielony przez okładki kursów
 * i grafiki social media.
 *
 * Najważniejsza część to test charakteryzujący: pilnuje, że wyciągnięcie pętli
 * dopasowania z CourseCoverImageService do TechThemeResolver niczego nie
 * zmieniło w okładkach kursów.
 *
 * Bez RefreshDatabase – kursy plikowe i social nie dotykają bazy.
 */
class SocialThemeResolutionTest extends TestCase
{
    private TechThemeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TechThemeResolver();
    }

    public function test_matches_a_technology_by_keyword(): void
    {
        $this->assertSame('Laravel', $this->resolver->fromText('laravel eloquent tips')['label']);
        $this->assertSame('Docker', $this->resolver->fromText('how to use docker compose')['label']);
    }

    public function test_unknown_topic_falls_back_to_the_default_theme(): void
    {
        $theme = $this->resolver->fromText('something about knitting');

        $this->assertSame(config('course-covers.default'), $theme);
    }

    /**
     * Kolejność motywów w configu = priorytet. Docker musi wygrać z generycznym
     * devops, bo "docker" i "deploy" potrafią wystąpić w jednym zdaniu.
     */
    public function test_specific_theme_wins_over_a_generic_one(): void
    {
        $this->assertSame('Docker', $this->resolver->fromText('deploy with docker on a linux server')['label']);
        $this->assertSame('Laravel', $this->resolver->fromText('laravel on php 8')['label']);
    }

    /**
     * 'nginx' jest słowem kluczowym RÓWNIEŻ w generycznym 'devops' (siatka
     * bezpieczeństwa dla starych treści), więc własny motyw nginxa działa wyłącznie
     * dzięki temu, że stoi w configu WYŻEJ. Przeniesienie go niżej cicho odbierze
     * nginxowi markę i wróci ogólne okno terminala.
     */
    public function test_nginx_has_its_own_brand_and_beats_generic_devops(): void
    {
        $theme = $this->resolver->fromText('nginx reverse proxy tuning');

        $this->assertSame('nginx', $theme['label']);
        $this->assertSame('#009639', $theme['accent']);
        $this->assertNotSame('DevOps', $theme['label']);

        // Treści bez nginxa nadal mają trafiać w DevOps.
        $this->assertSame('DevOps', $this->resolver->fromText('ci/cd deploy on linux')['label']);
    }

    public function test_key_from_text_reports_a_miss_instead_of_guessing(): void
    {
        $this->assertSame('docker', $this->resolver->keyFromText('docker compose up'));
        $this->assertSame('nginx', $this->resolver->keyFromText('nginx config'));
        $this->assertNull($this->resolver->keyFromText('something about knitting'));
    }

    /**
     * MINA: config/course-covers.php ma słowa kluczowe z prefiksem spacji
     * (' oop', ' js', ' ts') udające granicę słowa. Działają WYŁĄCZNIE dlatego,
     * że resolver opakowuje haystack spacjami. Gdyby ktoś powielił pętlę bez
     * opakowania, te motywy przestałyby matchować PO CICHU.
     */
    public function test_space_prefixed_keywords_still_match(): void
    {
        $this->assertSame('PHP', $this->resolver->fromText('clean oop design')['label']);
        $this->assertSame('JavaScript', $this->resolver->fromText('modern js patterns')['label']);
        $this->assertSame('JavaScript', $this->resolver->fromText('strict ts config')['label']);
    }

    public function test_space_prefixed_keyword_matches_at_the_start_of_the_text(): void
    {
        // Bez opakowania haystacka spacjami ten przypadek by nie przeszedł.
        $this->assertSame('JavaScript', $this->resolver->fromText('js closures explained')['label']);
    }

    /**
     * Prefiks spacji kotwiczy TYLKO lewą stronę wyrazu – ' js' nadal trafia w
     * początek "jsonwebtoken". To nie jest pełna granica słowa i nigdy nie była.
     *
     * Tutaj akurat wynik jest nieszkodliwy (JWT to świat JS), ale zachowanie
     * trzeba znać: dopasowanie jest PODCIĄGOWE, nie słownikowe.
     */
    public function test_space_prefix_anchors_only_the_left_side_of_a_word(): void
    {
        $this->assertSame('JavaScript', $this->resolver->fromText('jsonwebtoken basics')['label']);
    }

    /**
     * REGRESJA: dopasowanie jest podciągowe, więc keyword Dockera 'compose'
     * trafiał w 'composer' – narzędzie PHP. Każdy tekst o Composerze dostawał
     * wieloryba Dockera. Naprawione zawężeniem keywordu do 'docker compose'.
     */
    public function test_composer_is_php_not_docker(): void
    {
        $this->assertSame('PHP', $this->resolver->fromText('php composer autoloading')['label']);
        $this->assertSame('Docker', $this->resolver->fromText('docker compose for local dev')['label']);
    }

    /**
     * REGRESJA: docker stał w configu PRZED kubernetesem i łapał na keywordzie
     * 'container', więc kurs o Kubernetesie dostawał logo Dockera.
     */
    public function test_kubernetes_wins_over_docker_despite_mentioning_containers(): void
    {
        $this->assertSame('Kubernetes', $this->resolver->fromText('Kubernetes Basics - orchestration of containers')['label']);
        $this->assertSame('Kubernetes', $this->resolver->fromText('kubernetes runs docker containers')['label']);
    }

    public function test_matching_is_case_insensitive(): void
    {
        $this->assertSame('Docker', $this->resolver->fromText('DOCKER Compose')['label']);
    }

    public function test_accent_helpers_return_the_palette_and_hex(): void
    {
        $this->assertSame('sky', $this->resolver->accentColorFromText('docker basics'));
        $this->assertSame('#2496ed', $this->resolver->accentHexFromText('docker basics'));

        $this->assertSame(
            config('course-covers.default.accent_color'),
            $this->resolver->accentColorFromText('completely unrelated'),
        );
    }

    /**
     * TEST CHARAKTERYZUJĄCY – to on czyni refaktor bezpiecznym.
     *
     * CourseCoverImageService::resolveTheme() deleguje teraz do TechThemeResolver.
     * Publiczne API i wynik muszą zostać dokładnie takie jak przed zmianą.
     */
    public function test_course_cover_theme_resolution_is_unchanged(): void
    {
        $service = app(CourseCoverImageService::class);

        $expected = [
            'docker-basics' => ['label' => 'Docker', 'accent' => '#2496ed', 'accent_color' => 'sky'],
            'php'           => ['label' => 'PHP', 'accent' => '#34d399', 'accent_color' => 'emerald'],
        ];

        foreach ($expected as $slug => $want) {
            $course = app(MarkdownCourseRepository::class)->findCourse($slug);
            $this->assertNotNull($course, "Kurs {$slug} zniknął z resources/courses.");

            $theme = $service->resolveTheme($course);

            $this->assertSame($want['label'], $theme['label'], "Kurs {$slug}: zmienił się label motywu.");
            $this->assertSame($want['accent'], $theme['accent'], "Kurs {$slug}: zmienił się accent.");
            $this->assertSame($want['accent_color'], $service->accentColor($course), "Kurs {$slug}: zmienił się accent_color.");
        }
    }

    public function test_course_theme_falls_back_to_default_for_an_unknown_topic(): void
    {
        $course = new Course();
        $course->name = 'Knitting for beginners';
        $course->slug = 'knitting';
        $course->exists = false;

        $this->assertSame(config('course-covers.default'), app(CourseCoverImageService::class)->resolveTheme($course));
    }

    /**
     * Motyw 'php' ma CELOWO puste logo (okładka bez znaku) – widoki muszą to
     * guardować, więc pilnujemy, że pustka jest zamierzona, a nie regresją.
     */
    public function test_php_theme_has_an_intentionally_empty_logo(): void
    {
        $this->assertSame('', $this->resolver->fromText('php composer')['logo']);
    }

    public function test_every_theme_has_the_keys_the_views_rely_on(): void
    {
        $themes = array_merge(
            array_values((array) config('course-covers.themes')),
            [config('course-covers.default')],
        );

        foreach ($themes as $theme) {
            foreach (['accent', 'accent_color', 'label', 'logo'] as $key) {
                $this->assertArrayHasKey($key, $theme, "Motyw '{$theme['label']}' nie ma klucza '{$key}'.");
            }
        }
    }

    /**
     * SEDNO: motyw 'default' z course-covers to czapka absolwenta i etykieta
     * "Free course" – dobre na okładce kursu, kłamliwe na poście o cachingu.
     * Social ma własny fallback i NIE WOLNO mu wpaść w kursowy.
     */
    public function test_social_never_falls_back_to_the_course_default(): void
    {
        $theme = app(SocialImageService::class)->theme($this->socialPost('caching-basics', 'caching'));

        $this->assertNotSame('Free course', $theme['label'], 'Post o cachingu nie jest kursem.');
        $this->assertSame('Caching', $theme['label'], 'Etykieta ma pochodzić z topic: autora.');
        $this->assertSame('', $theme['logo'], 'Bez technologii nie ma marki, której moglibyśmy uczciwie użyć.');
        $this->assertNotSame(config('course-covers.default.logo'), $theme['logo'], 'Czapka absolwenta nie ma prawa trafić na posta.');
    }

    /**
     * Okładki KURSÓW zostają nietknięte – tam kursowy fallback jest poprawny.
     */
    public function test_course_covers_still_use_the_course_default(): void
    {
        $this->assertSame('Free course', $this->resolver->fromText('something about knitting')['label']);
    }

    public function test_social_fallback_label_uses_the_brand_when_there_is_no_topic(): void
    {
        $theme = app(SocialImageService::class)->theme($this->socialPost('some-loose-post', null));

        $this->assertSame(config('social.fallback_theme.label'), $theme['label']);
    }

    /**
     * Akcent luźnych postów jest ROTOWANY – inaczej każdy z nich byłby emerald
     * i przy dużym wolumenie feed znudziłby się kolorem zamiast stylem.
     */
    public function test_loose_posts_get_rotating_accents(): void
    {
        $service = app(SocialImageService::class);
        $accents = [];

        foreach (['alpha-tip', 'beta-tip', 'gamma-tip', 'delta-tip', 'epsilon-tip', 'zeta-tip'] as $slug) {
            $accents[] = $service->theme($this->socialPost($slug, 'code review'))['accent'];
        }

        $this->assertGreaterThan(1, count(array_unique($accents)), 'Wszystkie luźne posty mają jeden kolor.');
    }

    public function test_loose_post_accent_is_deterministic(): void
    {
        $service = app(SocialImageService::class);
        $first = $service->theme($this->socialPost('stable-tip', 'regex'))['accent'];

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $service->theme($this->socialPost('stable-tip', 'regex'))['accent']);
        }
    }

    /**
     * Te akcenty trafiają pod styl `spotlight`, czyli tekst ląduje NA nich. Każdy
     * musi spełniać WCAG AA dla dużego tekstu (3:1) z atramentem liczonym przez
     * inkFor() – inaczej luźny post byłby kolorowy i nieczytelny.
     */
    public function test_every_fallback_accent_meets_wcag_contrast(): void
    {
        $service = app(SocialImageService::class);

        foreach ((array) config('social.fallback_theme.accents') as $entry) {
            $ink = $service->inkFor($entry['accent']);
            $ratio = $this->contrast($entry['accent'], $ink);

            $this->assertGreaterThanOrEqual(
                3.0,
                $ratio,
                "Akcent {$entry['accent']} z atramentem {$ink} daje " . round($ratio, 2) . ':1 – poniżej WCAG AA.',
            );
        }
    }

    private function contrast(string $a, string $b): float
    {
        $lum = function (string $hex): float {
            [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
            $lin = static fn (int $c): float => ($c / 255) <= 0.04045
                ? ($c / 255) / 12.92
                : ((($c / 255) + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
        };

        return (max($lum($a), $lum($b)) + 0.05) / (min($lum($a), $lum($b)) + 0.05);
    }

    private function socialPost(string $slug, ?string $topic): \App\Services\Social\SocialPost
    {
        $fm = "type: carousel\nslug: {$slug}" . ($topic !== null ? "\ntopic: {$topic}" : '');

        return (new MarkdownSocialPostParser())->toPost("---\n{$fm}\n---\n\n## A\n\nB.", $slug);
    }

    public function test_social_post_topic_overrides_theme_derivation(): void
    {
        $parser = new MarkdownSocialPostParser();

        $post = $parser->toPost(
            "---\ntype: quote\ntitle: Deploying a Laravel app\ntopic: docker\n---\n\n## Hi\n\nBody.",
            'x',
        );

        $this->assertSame('Docker', $this->resolver->fromText($post->themeHaystack())['label']);
    }

    public function test_social_post_derives_the_theme_from_title_and_hashtags(): void
    {
        $parser = new MarkdownSocialPostParser();

        $post = $parser->toPost(
            "---\ntype: quote\ntitle: Fix N+1 queries\nhashtags: [eloquent, php]\n---\n\n## Hi\n\nBody.",
            'x',
        );

        $this->assertSame('Laravel', $this->resolver->fromText($post->themeHaystack())['label']);
    }

    /**
     * Motyw 'ai' ma słowo kluczowe 'ai' BEZ prefiksu spacji, więc trafia też w
     * środek wyrazu ("available", "training"). Dla kursów to nieszkodliwe (nazwy
     * są krótkie), ale haystack posta social jest luźniejszy – dochodzą hashtagi.
     *
     * Ten test DOKUMENTUJE zachowanie, nie chwali go: dlatego skill social-writer
     * każe ustawiać jawny `topic:`, gdy temat nie jest oczywisty.
     */
    public function test_ai_keyword_is_greedy_which_is_why_explicit_topic_exists(): void
    {
        $this->assertSame('AI', $this->resolver->fromText('available queue drivers')['label']);

        // Jawny topic ratuje sytuację.
        $this->assertSame('Laravel', $this->resolver->fromText('laravel')['label']);
    }
}
