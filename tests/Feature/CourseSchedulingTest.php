<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\Course\MarkdownCourseRepository;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Planowanie publikacji kursów .md przez frontmatter `published_at`.
 *
 * Kurs z datą w przyszłości jest ukryty wszędzie (lista /kursy, strona główna,
 * sitemap, bezpośredni URL) aż do terminu - analogicznie do artykułów .md. Liczone
 * przy renderze (Course::isLive()), bez bazy i bez crona: kurs pojawia się SAM, gdy
 * published_at <= teraz. Dzięki temu możemy wypuszczać kursy w kontrolowanym tempie
 * (np. jeden tygodniowo), zamiast zrzucać wszystko naraz.
 */
class CourseSchedulingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Sama logika widoczności - bez plików, deterministycznie. */
    public function test_is_live_respects_published_at_and_is_published(): void
    {
        // Nieopublikowany = niewidoczny, niezależnie od daty.
        $draft = new Course();
        $draft->is_published = false;
        $this->assertFalse($draft->isLive());

        // Opublikowany bez daty = żywy od razu (wsteczna zgodność istniejących kursów).
        $noDate = new Course();
        $noDate->is_published = true;
        $noDate->published_at = null;
        $this->assertTrue($noDate->isLive());

        // Data w przyszłości = ukryty do terminu.
        $future = new Course();
        $future->is_published = true;
        $future->published_at = Carbon::now()->addWeek();
        $this->assertFalse($future->isLive());

        // Data w przeszłości = widoczny.
        $past = new Course();
        $past->is_published = true;
        $past->published_at = Carbon::now()->subDay();
        $this->assertTrue($past->isLive());
    }

    /** Zaplanowany kurs .md (redis-basics: 2026-07-24) jest ukryty przed terminem. */
    public function test_scheduled_course_is_hidden_before_its_date(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00');

        $repo = app(MarkdownCourseRepository::class);
        $liveSlugs = $repo->published()->pluck('slug')->all();

        $this->assertNotContains('redis-basics', $liveSlugs, 'Zaplanowany kurs nie powinien być na liście przed terminem.');

        $redis = $repo->findCourse('redis-basics');
        $this->assertNotNull($redis, 'Plik kursu istnieje...');
        $this->assertFalse($redis->isLive(), '...ale nie jest jeszcze widoczny.');
    }

    /** Po nadejściu terminu kurs pojawia się sam - bez zmiany pliku, bez crona. */
    public function test_scheduled_course_appears_after_its_date(): void
    {
        Carbon::setTestNow('2026-07-25 12:00:00');

        $repo = app(MarkdownCourseRepository::class);
        $liveSlugs = $repo->published()->pluck('slug')->all();

        $this->assertContains('redis-basics', $liveSlugs, 'Po terminie kurs powinien być widoczny na liście.');
        $this->assertTrue($repo->findCourse('redis-basics')->isLive());
    }

    /** Kurs bez published_at (np. nginx-basics) pozostaje widoczny - nic się nie zmienia. */
    public function test_course_without_published_at_stays_live(): void
    {
        $repo = app(MarkdownCourseRepository::class);
        $nginx = $repo->findCourse('nginx-basics');

        $this->assertNotNull($nginx);
        $this->assertNull($nginx->published_at);
        $this->assertTrue($nginx->isLive());
    }
}
