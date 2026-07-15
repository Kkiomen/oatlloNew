<?php

namespace Tests\Feature;

use App\Services\Social\Publish\SocialAutoPublisher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Socialowa część ticka /api/cron.
 *
 * Endpoint istnieje od dawna jako PUBLICZNY GET i to było nieszkodliwe: najgorsze,
 * co robił obcy strzał, to przyspieszenie publikacji artykułu, który i tak był
 * zaplanowany u nas. Publikacja na CUDZĄ platformę to inna waga – pali limity API
 * i zostawia publiczny ślad. Stąd token wyłącznie na tę część.
 *
 * RefreshDatabase, bo tick rusza też artykuły z bazy (reszta modułu social bazy
 * nie potrzebuje i nadal jej nie dotyka).
 */
class CronSocialTickTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_the_tick_still_works_without_a_social_token_configured(): void
    {
        config(['social.auto_publish.token' => '']);

        $this->mock(SocialAutoPublisher::class, fn ($m) => $m->shouldNotReceive('run'));

        $this->getJson('/api/cron')
            ->assertOk()
            ->assertJson(['success' => true, 'social' => ['state' => 'no_token_configured']]);
    }

    /**
     * Sedno: obcy GET nie ma prawa ruszyć Instagrama, choć artykuły i sitemap
     * zostają otwarte (żeby nie psuć działającego n8n).
     */
    public function test_a_caller_without_the_token_cannot_publish_to_instagram(): void
    {
        config(['social.auto_publish.token' => 'sekret']);

        $this->mock(SocialAutoPublisher::class, fn ($m) => $m->shouldNotReceive('run'));

        $this->getJson('/api/cron')
            ->assertOk()
            ->assertJson([
                'success' => true,               // artykuły i sitemap poszły
                'social'  => ['state' => 'unauthorized'],
            ]);
    }

    public function test_a_wrong_token_cannot_publish_to_instagram(): void
    {
        config(['social.auto_publish.token' => 'sekret']);

        $this->mock(SocialAutoPublisher::class, fn ($m) => $m->shouldNotReceive('run'));

        $this->withToken('nie-ten')
            ->getJson('/api/cron')
            ->assertOk()
            ->assertJson(['social' => ['state' => 'unauthorized']]);
    }

    public function test_the_right_token_runs_the_autopublisher(): void
    {
        config(['social.auto_publish.token' => 'sekret']);

        $this->mock(SocialAutoPublisher::class, fn ($m) => $m->shouldReceive('run')->once()->andReturn([
            'state'           => 'ran',
            'published_count' => 1,
            'published'       => [['slug' => 'demo', 'format' => 'post']],
            'failed'          => [],
            'skipped'         => [],
        ]));

        $this->withToken('sekret')
            ->getJson('/api/cron')
            ->assertOk()
            ->assertJson(['social' => ['state' => 'ran', 'published_count' => 1]]);
    }

    /**
     * Cudze API leżące nie może zabrać publikacji artykułów ani sitemapy –
     * to niezależne obowiązki tego samego ticka.
     */
    public function test_a_broken_zernio_does_not_break_the_rest_of_the_tick(): void
    {
        config(['social.auto_publish.token' => 'sekret']);

        $this->mock(SocialAutoPublisher::class, fn ($m) => $m->shouldReceive('run')
            ->once()
            ->andThrow(new \RuntimeException('Zernio leży')));

        $this->withToken('sekret')
            ->getJson('/api/cron')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'social'  => ['state' => 'error', 'message' => 'Zernio leży'],
            ]);
    }
}
