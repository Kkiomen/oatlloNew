<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\Course\CourseCoverImageService;
use App\Services\Course\MarkdownCourseRepository;
use Illuminate\Http\Response;

/**
 * Serwuje wygenerowaną okładkę KURSU jako SVG (motyw "logo technologii").
 *
 * Grafika jest budowana dynamicznie z danych kursu (nazwa, slug, opis) i pasuje
 * do tematu (kolor + logo). Nie zależy od rozszerzeń graficznych PHP – działa
 * identycznie lokalnie i na produkcji. Źródło kursu jak wszędzie: plik .md ma
 * pierwszeństwo, potem baza.
 */
class CourseCoverController extends Controller
{
    public function __construct(
        private MarkdownCourseRepository $repository,
        private CourseCoverImageService $covers,
    ) {
    }

    public function show(string $slug): Response
    {
        $course = $this->repository->findCourse($slug)
            ?? Course::where('slug', $slug)->first();

        if (!$course) {
            abort(404);
        }

        $svg = $this->covers->renderForCourse($course);

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
