<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wycofany artykuł, który MA następcę, oddaje 301 na niego zamiast 410.
 *
 * DLACZEGO TO ISTNIEJE: 410 mówi Google „zapomnij" i sygnały rankingowe parują,
 * 301 mówi „przeniosłem tu" i konsolidują się na następcy. Powód biznesowy jest
 * konkretny (GSC 08.08-04.09.2026): `letter-i-in-solid-explanation-examples`
 * stał na **pozycji 6.7** - pierwsza strona Google - i oddawał 410, czyli
 * oddawaliśmy tę pozycję za darmo.
 *
 * Test pilnuje trzech rzeczy naraz, bo każda z nich cicho psuje efekt:
 *  1. slug Z mapą przekierowań -> 301 na wskazany cel,
 *  2. slug BEZ mapy -> nadal 410 (nie wolno zamienić wszystkich wycofanych w 301
 *     na byle co - 301 na niepowiązaną stronę Google czyta jako soft-404),
 *  3. każdy cel z mapy jest ścieżką WZGLĘDNĄ na własnym hoście - inaczej
 *     przekierowanie wyszłoby poza domenę albo przybiło zły host (patrz mina
 *     z kanonikalizacją w CLAUDE.md).
 */
class RetiredArticleRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function makeRetiredArticle(string $slug): void
    {
        Article::create([
            'slug' => $slug,
            'name' => "Legacy {$slug}",
            'contents' => [['type' => 'text', 'content' => 'Legacy body.']],
            'is_published' => false,
            'language' => 'en',
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_wycofany_artykul_z_nastepca_oddaje_301(): void
    {
        config()->set('articles.retired_slugs', ['legacy-with-successor']);
        config()->set('articles.retired_redirects', [
            'legacy-with-successor' => '/php-enums-complete-guide',
        ]);

        $this->makeRetiredArticle('legacy-with-successor');

        $this->get('/legacy-with-successor')
            ->assertStatus(301)
            ->assertRedirect('/php-enums-complete-guide');
    }

    public function test_wycofany_artykul_bez_nastepcy_nadal_oddaje_410(): void
    {
        config()->set('articles.retired_slugs', ['legacy-no-successor']);
        config()->set('articles.retired_redirects', [
            'legacy-with-successor' => '/php-enums-complete-guide',
        ]);

        $this->makeRetiredArticle('legacy-no-successor');

        $this->get('/legacy-no-successor')->assertStatus(410);
    }

    public function test_nieistniejacy_slug_nadal_oddaje_404(): void
    {
        config()->set('articles.retired_slugs', []);
        config()->set('articles.retired_redirects', []);

        $this->get('/nie-ma-takiego-artykulu-nigdy-nie-bylo')->assertStatus(404);
    }

    /**
     * Każdy wpis w realnym configu musi wskazywać slug, który faktycznie jest
     * wycofany - inaczej mapa jest martwa i nikt tego nie zauważy.
     */
    public function test_kazdy_cel_przekierowania_dotyczy_wycofanego_sluga(): void
    {
        $retired = config('articles.retired_slugs', []);
        $redirects = config('articles.retired_redirects', []);

        $this->assertNotEmpty($redirects, 'Mapa retired_redirects nie może być pusta - to ona odzyskuje pozycje.');

        foreach ($redirects as $slug => $target) {
            $this->assertContains(
                $slug,
                $retired,
                "Slug '{$slug}' jest w retired_redirects, ale nie w retired_slugs - przekierowanie nigdy nie zadziała."
            );

            $this->assertStringStartsWith(
                '/',
                $target,
                "Cel '{$target}' musi być ścieżką względną na własnym hoście, nie pełnym URL-em."
            );
        }
    }
}
