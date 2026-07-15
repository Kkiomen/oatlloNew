<?php

namespace App\Http\Controllers;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialStyleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Podgląd grafik social media w przeglądarce – WYŁĄCZNIE dla wygody przy pracy.
 *
 * Eksport (`social:export`) NIE korzysta z HTTP: rasteryzator zrzuca lokalny plik
 * file://. Te trasy są rejestrowane warunkowo (config('social.preview_enabled')),
 * więc na produkcji nie ma ich w tablicy routingu w ogóle.
 */
class SocialPreviewController extends Controller
{
    public function __construct(
        private MarkdownSocialPostRepository $posts,
        private SocialImageService $images,
    ) {
    }

    /**
     * Wszystkie slajdy posta jeden pod drugim, w skali – szybkie spojrzenie
     * na całą karuzelę bez klikania po plikach.
     */
    public function index(string $slug): Response
    {
        $post = $this->post($slug);

        return response()->view('social.preview', [
            'post'   => $post,
            'canvas' => $post->type->canvas(),
        ]);
    }

    /**
     * Pojedynczy slajd w dokładnej kanwie – dokładnie to, co zobaczy rasteryzator.
     *
     * `?style=` wymusza skórkę (galeria stylów pokazuje ten sam slajd w każdej).
     * Nieznana nazwa jest ignorowana przez SocialImageService, więc podrzucenie
     * bzdury w URL-u nie wywali widoku – pokaże po prostu styl dobrany automatem.
     */
    public function slide(string $slug, int $index, Request $request): Response
    {
        $post = $this->post($slug);
        $slide = $post->slides[$index - 1] ?? abort(404, "Slajd {$index} nie istnieje.");

        $style = $request->query('style');

        return response($this->images->renderSlide($post, $slide, is_string($style) ? $style : null))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Galeria: ten sam slajd we WSZYSTKICH skórkach z pakietu, obok siebie.
     *
     * Odpowiednik `php artisan social:styles {slug}`, tylko bez rasteryzacji i bez
     * zrzucania PNG-ów na dysk – do oglądania pakietu i porównywania stylów.
     */
    public function styles(string $slug, Request $request, SocialStyleResolver $styles): Response
    {
        $post = $this->post($slug);

        $index = max(1, min((int) $request->query('slide', 1), $post->slideCount()));

        return response()->view('social.gallery', [
            'post'    => $post,
            'canvas'  => $post->type->canvas(),
            'index'   => $index,
            'styles'  => $styles->names(),
            'resolver' => $styles,
            'auto'    => $styles->resolve($post),
        ]);
    }

    private function post(string $slug): SocialPost
    {
        return $this->posts->findBySlug($slug) ?? abort(404, "Nie ma posta '{$slug}'.");
    }
}
