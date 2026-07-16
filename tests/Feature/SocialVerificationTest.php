<?php

namespace Tests\Feature;

use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\Review\SocialReviewRepository;
use App\Services\Social\Review\SocialVerification;
use App\Services\Social\Review\SocialVerificationStamp;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Weryfikacja merytoryczna postów (pieczątka `verified:`).
 *
 * Lint pilnuje FORMATU. To pilnuje PRAWDY — a pieczątka bez odcisku znaczyłaby
 * "sprawdzone kiedyś, w nieznanej wersji", czyli dokładnie ten błąd, przed którym
 * chroni `fingerprint` werdyktów człowieka.
 *
 * Bez RefreshDatabase — moduł social nadal nie ma tabeli.
 */
class SocialVerificationTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/social-verify-' . uniqid());
        File::ensureDirectoryExists($this->dir);
        config(['social.path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function raw(string $body = "## Hook\n\nTreść."): string
    {
        return "---\ntype: quote\nslug: demo\nstatus: ready\ncaption: Podpis.\n---\n\n{$body}\n";
    }

    // ------------------------------------------------------------- pieczątka

    public function test_stamps_a_post_with_verdict_and_checks(): void
    {
        $stamped = SocialVerificationStamp::apply(
            $this->raw(),
            SocialVerification::APPROVED,
            ['fakty zgodne ze źródłem', 'kod: składnia PHP 8.1'],
            "AMPHP to biblioteka.\nZgodne z artykułem.",
        );

        $post = (new MarkdownSocialPostParser())->toPost($stamped, 'demo');

        $this->assertNotNull($post->verified);
        $this->assertTrue($post->verified->isApproved());
        $this->assertSame(['fakty zgodne ze źródłem', 'kod: składnia PHP 8.1'], $post->verified->checks);
        $this->assertStringContainsString('AMPHP to biblioteka.', $post->verified->notes);
    }

    public function test_a_post_without_a_stamp_has_no_verification(): void
    {
        $post = (new MarkdownSocialPostParser())->toPost($this->raw(), 'demo');

        $this->assertNull($post->verified);
    }

    /**
     * Sedno: pieczątka dotyczy KONKRETNEJ wersji treści.
     */
    public function test_the_stamp_matches_the_content_it_was_made_for(): void
    {
        $stamped = SocialVerificationStamp::apply($this->raw(), SocialVerification::APPROVED);
        $post = (new MarkdownSocialPostParser())->toPost($stamped, 'demo');

        $this->assertTrue($post->verified->matches(SocialVerificationStamp::contentFingerprint($stamped)));
    }

    /**
     * NAJWAŻNIEJSZY test. Bez tego "zweryfikowane" znaczyłoby "zweryfikowane
     * kiedyś, w nieznanej wersji" — a poprawka po weryfikacji przechodziłaby
     * z zielonym stemplem.
     */
    public function test_editing_the_content_kills_the_stamp(): void
    {
        $stamped = SocialVerificationStamp::apply($this->raw(), SocialVerification::APPROVED);
        $edited = str_replace('Treść.', 'Zupełnie inna treść.', $stamped);

        $post = (new MarkdownSocialPostParser())->toPost($edited, 'demo');

        $this->assertFalse(
            $post->verified->matches(SocialVerificationStamp::contentFingerprint($edited)),
            'Zmiana treści MUSI unieważnić weryfikację.',
        );
    }

    public function test_restamping_replaces_the_old_stamp_instead_of_stacking(): void
    {
        $once = SocialVerificationStamp::apply($this->raw(), SocialVerification::ISSUES, [], 'Zły port.');
        $twice = SocialVerificationStamp::apply($once, SocialVerification::APPROVED, [], 'Poprawione.');

        $this->assertSame(1, substr_count($twice, 'verified:'));

        $post = (new MarkdownSocialPostParser())->toPost($twice, 'demo');

        $this->assertTrue($post->verified->isApproved());
        $this->assertStringNotContainsString('Zły port', $twice);
    }

    /**
     * NIE JEST TEORETYCZNY. Dwie osobne weryfikacje wpisały w `--check` treść
     * z backslashem (`Amp\async`, `App\Tests\`). Cudzysłów PODWÓJNY w YAML-u
     * traktuje `\a` jak sekwencję ucieczki, więc frontmatter przestawał się
     * parsować — a repozytorium czyta katalog w całości, więc JEDEN zły plik
     * kładł lint, panel, kalendarz i eksport dla WSZYSTKICH 150 postów.
     *
     * @dataProvider nastyChecks
     */
    public function test_a_check_with_yaml_metacharacters_does_not_break_the_frontmatter(string $check): void
    {
        $stamped = SocialVerificationStamp::apply(
            $this->raw(),
            SocialVerification::APPROVED,
            [$check],
        );

        $post = (new MarkdownSocialPostParser())->toPost($stamped, 'demo');

        $this->assertSame([$check], $post->verified->checks, "Check '{$check}' nie przetrwał zapisu.");
    }

    public static function nastyChecks(): array
    {
        return [
            'backslash w nazwie klasy'  => ['kod uzywa Amp\async i Amp\await'],
            'backslash na koncu'        => ['namespace App\Tests\\'],
            'dwukropek ze spacja'       => ['kod: skladnia poprawna'],
            'apostrof'                  => ["Faker's unique() sie przelewa"],
            'cudzyslow'                 => ['post mowi "truncates", a to nieprawda'],
            'hash i at'                 => ['#hashtag oraz @handle'],
            'myslnik na poczatku'       => ['- wyglada jak lista YAML'],
            'nawiasy klamrowe'          => ['{ wyglada jak mapa YAML }'],
            'wielolinijkowy znak'       => ['pipe | i > w tresci'],
        ];
    }

    public function test_notes_with_yaml_metacharacters_survive(): void
    {
        $notes = "Klasa Amp\async: \"cytat\" i 'apostrof'\nDruga linia z # hashem";

        $stamped = SocialVerificationStamp::apply($this->raw(), SocialVerification::ISSUES, [], $notes);
        $post = (new MarkdownSocialPostParser())->toPost($stamped, 'demo');

        $this->assertStringContainsString('Amp\async', $post->verified->notes);
        $this->assertStringContainsString('Druga linia z # hashem', $post->verified->notes);
    }

    // -------------------------------------------- współistnienie z werdyktem

    /**
     * DRUGI NAJWAŻNIEJSZY. Odcisk werdyktu człowieka MUSI ignorować pieczątkę:
     * recenzent ocenia TREŚĆ, nie cudzą adnotację o niej. Bez tego dopisanie
     * weryfikacji do 150 postów skasowałoby wszystkie gotowe zielone werdykty.
     */
    public function test_stamping_does_not_invalidate_a_humans_verdict(): void
    {
        $raw = $this->raw();
        $before = SocialReviewRepository::fingerprint($raw);

        $stamped = SocialVerificationStamp::apply($raw, SocialVerification::APPROVED, ['cokolwiek'], 'Uwaga.');

        $this->assertSame(
            $before,
            SocialReviewRepository::fingerprint($stamped),
            'Pieczątka weryfikacji nie ma prawa ruszyć odcisku werdyktu.',
        );
    }

    /**
     * Ale zmiana TREŚCI dalej musi unieważniać werdykt — inaczej zabralibyśmy
     * bezpiecznik przy okazji naprawiania czegoś innego.
     */
    public function test_editing_the_content_still_invalidates_the_verdict(): void
    {
        $stamped = SocialVerificationStamp::apply($this->raw(), SocialVerification::APPROVED);
        $edited = str_replace('Treść.', 'Inna treść.', $stamped);

        $this->assertNotSame(
            SocialReviewRepository::fingerprint($stamped),
            SocialReviewRepository::fingerprint($edited),
        );
    }

    /**
     * Wsteczna zgodność: pliki sprzed wprowadzenia pieczątki nie mogą zmienić
     * odcisku, bo to skasowałoby werdykty wydane wcześniej.
     */
    public function test_files_without_a_stamp_keep_their_original_fingerprint(): void
    {
        $raw = $this->raw();

        $this->assertSame(sha1(preg_replace('/\R/', "\n", $raw)), SocialReviewRepository::fingerprint($raw));
    }

    // ----------------------------------------------------------- komenda

    public function test_the_command_stamps_the_file_on_disk(): void
    {
        File::put($this->dir . '/demo.md', $this->raw());

        $this->artisan('social:verify', [
            'slug'      => 'demo',
            '--verdict' => 'approved',
            '--check'   => ['fakty ze źródła'],
            '--note'    => 'Sprawdzone.',
        ])->assertSuccessful();

        $post = (new MarkdownSocialPostParser())->toPost((string) File::get($this->dir . '/demo.md'), 'demo');

        $this->assertTrue($post->verified->isApproved());
        $this->assertSame(['fakty ze źródła'], $post->verified->checks);
    }

    public function test_the_command_refuses_an_unknown_verdict(): void
    {
        File::put($this->dir . '/demo.md', $this->raw());

        $this->artisan('social:verify', ['slug' => 'demo', '--verdict' => 'maybe'])
            ->expectsOutputToContain("--verdict musi być")
            ->assertFailed();
    }

    public function test_the_command_refuses_an_unknown_post(): void
    {
        $this->artisan('social:verify', ['slug' => 'nie-ma-mnie', '--verdict' => 'approved'])
            ->assertFailed();
    }

    /**
     * Nieznany klucz frontmattera to ERROR lintu, więc `verified:` musi być
     * jawnie dozwolony — inaczej pieczątka blokowałaby eksport.
     */
    public function test_the_stamp_does_not_break_the_lint_gate(): void
    {
        $stamped = SocialVerificationStamp::apply($this->raw(), SocialVerification::APPROVED, ['x'], 'y');

        $linter = new \App\Services\Social\SocialPostLinter(
            new MarkdownSocialPostParser(),
            new \App\Services\Social\SocialStyleResolver(),
        );

        $this->assertSame([], $linter->lintRaw($stamped, 'demo'));
    }
}
