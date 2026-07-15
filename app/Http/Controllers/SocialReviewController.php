<?php

namespace App\Http\Controllers;

use App\Services\Social\Review\SocialCalendar;
use App\Services\Social\Review\SocialReview;
use App\Services\Social\Review\SocialReviewQueue;
use App\Services\Social\Review\SocialReviewRepository;
use App\Services\Social\Review\SocialReviewVerdict;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Panel akceptacji postów – po jednym naraz, tylko te bez aktualnego werdyktu.
 *
 * Zero bazy: werdykt ląduje w pliku .md w resources/social/reviews/, dokładnie
 * jak sam post. Trasy rejestrowane WARUNKOWO (config('social.preview_enabled')),
 * więc na produkcji nie ma ich w tablicy routingu – panel jest narzędziem
 * roboczym na localhoście, nie funkcją serwisu.
 */
class SocialReviewController extends Controller
{
    public function __construct(
        private SocialReviewQueue $queue,
        private SocialReviewRepository $reviews,
    ) {
    }

    /**
     * Szerokość kafelka posta w panelu. Kanwa jest skalowana transformem (nie
     * przeliczana), więc podgląd zostaje wierny temu, co zrzuci rasteryzator.
     */
    private const CARD_WIDTH = 400;

    /** Sufit wysokości – inaczej story 9:16 nie mieści się na ekranie. */
    private const CARD_MAX_HEIGHT = 700;

    /**
     * Post do obejrzenia. Gdy kolejka pusta – podsumowanie.
     *
     * `?i=N` to kursor po kolejce oczekujących: pozwala obejrzeć kolejny post BEZ
     * wydawania werdyktu. Celowo w URL-u, a nie w sesji – kolejka i tak liczy się
     * z plików przy każdym żądaniu, więc stan w sesji rozjeżdżałby się z nią po
     * pierwszej edycji pliku. Kursor bez stanu nie ma jak skłamać.
     */
    public function index(Request $request): Response
    {
        $pending = $this->queue->pending();

        // Werdykt zdejmuje post z kolejki, więc ten sam indeks pokazuje już
        // następny post – dlatego po zapisie wracamy na i=0 i nic nie przeskakuje.
        $cursor = max(0, min((int) $request->query('i', 0), max(0, $pending->count() - 1)));
        $item = $pending->get($cursor);
        $canvas = $item?->post->type->canvas();

        return response()->view('social.review', [
            'item'    => $item,
            'canvas'  => $canvas,
            'scale'   => $canvas === null ? 1.0 : $this->scaleFor($canvas),
            'cursor'  => $cursor,
            'pending' => $pending->count(),
            'summary' => $this->queue->summary(),
            'broken'  => $this->queue->brokenCount(),
        ]);
    }

    /**
     * @param array{width:int, height:int} $canvas
     */
    private function scaleFor(array $canvas): float
    {
        return round(min(
            self::CARD_WIDTH / $canvas['width'],
            self::CARD_MAX_HEIGHT / $canvas['height'],
        ), 4);
    }

    /**
     * Zapis werdyktu. Powód jest WYMAGANY przy czerwonym – recenzja bez powodu
     * jest bezużyteczna dla tego, kto potem ma post poprawić.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $item = $this->queue->find($slug) ?? abort(404, "Nie ma posta '{$slug}'.");

        $data = $request->validate([
            'verdict' => ['required', 'string', 'in:approved,changes'],
            'reason'  => ['nullable', 'string', 'max:4000', 'required_if:verdict,changes'],
        ], [
            'reason.required_if' => 'Podaj powód – bez niego nie wiadomo, co poprawić.',
        ]);

        $verdict = SocialReviewVerdict::from($data['verdict']);

        $this->reviews->save(new SocialReview(
            slug: $item->post->slug,
            verdict: $verdict,
            reason: trim((string) ($data['reason'] ?? '')),
            reviewedAt: CarbonImmutable::now(),
            // Skrót TEJ wersji pliku: gdy post zostanie poprawiony, werdykt
            // przestanie pasować i post wróci do kolejki.
            fingerprint: $item->fingerprint,
        ));

        return redirect()->route('social.review')
            ->with('reviewed', ['slug' => $item->post->slug, 'verdict' => $verdict->value]);
    }

    /**
     * Kalendarz zaakceptowanych: co i kiedy jest gotowe do wystawienia.
     *
     * `?m=YYYY-MM` przewija miesiące, `?day=YYYY-MM-DD` rozwija jeden dzień.
     * Oba bezstanowo w URL-u – jak kursor w panelu i z tego samego powodu: kalendarz
     * liczy się z plików przy każdym żądaniu, więc stan w sesji rozjechałby się
     * z nim po pierwszej edycji.
     */
    public function calendar(Request $request, SocialCalendar $calendar): Response
    {
        $month = $this->parseDate($request->query('m'), 'Y-m') ?? CarbonImmutable::now()->startOfMonth();
        $day = $this->parseDate($request->query('day'), 'Y-m-d');

        return response()->view('social.calendar', [
            'month'     => $month,
            'days'      => $calendar->month($month),
            'day'       => $day,
            'dayEntries' => $day === null ? collect() : $calendar->day($day),
            'undated'   => $calendar->undated(),
            'formats'   => (array) config('social.formats', []),
            'summary'   => $this->queue->summary(),
        ]);
    }

    /**
     * Data z URL-a. Bzdura => null (czyli wartość domyślna), nigdy wyjątek –
     * ręcznie sklejony link nie ma prawa wywalić panelu.
     */
    private function parseDate(mixed $value, string $format): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!' . $format, $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cofnięcie werdyktu – post wraca do kolejki.
     */
    public function destroy(string $slug): RedirectResponse
    {
        $item = $this->queue->find($slug) ?? abort(404, "Nie ma posta '{$slug}'.");

        $this->reviews->forget($item->post->slug);

        return redirect()->route('social.review');
    }
}
