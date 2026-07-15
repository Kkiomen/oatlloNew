<?php

namespace App\Services\Social\Review;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Kalendarz zaakceptowanych treści: co i kiedy jest gotowe do wystawienia.
 *
 * Jednostką NIE jest post, tylko para (post × format). Jeden plik .md bywa tego
 * samego dnia i postem w feedzie, i reelem z tych samych slajdów – w kalendarzu
 * muszą to być dwie osobne pozycje, bo to dwie osobne publikacje.
 *
 * Pokazujemy WYŁĄCZNIE zaakceptowane (zielony werdykt pasujący do aktualnej treści
 * pliku). Post „do poprawy” albo nieobejrzany nie jest zaplanowany, tylko w robocie –
 * wpisanie go do kalendarza sugerowałoby gotowość, której nie ma. Liczbę takich
 * dzień pokazuje osobno, żeby dziura w planie nie była niewidzialna.
 *
 * Zero bazy: wszystko liczone z plików .md przy każdym żądaniu.
 */
class SocialCalendar
{
    public function __construct(private SocialReviewQueue $queue)
    {
    }

    /**
     * Zaakceptowane pozycje pogrupowane po dniu (klucz `Y-m-d`).
     *
     * Posty bez `publish_at` NIE mają dnia – trafiają do `undated()`, a nie na siłę
     * na dzisiaj: kalendarz ma pokazywać plan, a nie go zmyślać.
     *
     * @return Collection<string, Collection<int, SocialCalendarEntry>>
     */
    public function approvedByDay(): Collection
    {
        return $this->entries($this->queue->approved())
            ->filter(fn (SocialCalendarEntry $e) => $e->day() !== null)
            ->groupBy(fn (SocialCalendarEntry $e) => $e->day())
            ->sortKeys();
    }

    /**
     * Zaakceptowane, ale bez terminu – gotowe i nieprzypisane do dnia.
     *
     * @return Collection<int, SocialCalendarEntry>
     */
    public function undated(): Collection
    {
        return $this->entries($this->queue->approved())
            ->filter(fn (SocialCalendarEntry $e) => $e->day() === null)
            ->values();
    }

    /**
     * Ile pozycji danego dnia czeka jeszcze na werdykt (albo wróciło do poprawy).
     * Bez tego dzień z samymi nieocenionymi postami wyglądałby jak wolny.
     *
     * @return Collection<string, int>
     */
    public function unsettledCountByDay(): Collection
    {
        $items = $this->queue->items()
            ->reject(fn (SocialReviewItem $i) => $i->isApproved())
            ->reject(fn (SocialReviewItem $i) => $i->post->status === \App\Services\Social\SocialPost::STATUS_PUBLISHED);

        return $this->entries($items)
            ->filter(fn (SocialCalendarEntry $e) => $e->day() !== null)
            ->groupBy(fn (SocialCalendarEntry $e) => $e->day())
            ->map(fn (Collection $group) => $group->count());
    }

    /**
     * Siatka miesiąca: pełne tygodnie (pon-nd), żeby widok nie musiał liczyć
     * offsetów. Każdy dzień niesie swoje pozycje.
     *
     * @return list<array{date: CarbonImmutable, inMonth: bool, entries: Collection<int, SocialCalendarEntry>, unsettled: int}>
     */
    public function month(CarbonImmutable $month): array
    {
        $byDay = $this->approvedByDay();
        $unsettled = $this->unsettledCountByDay();

        $start = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $end = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);

        $days = [];

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $key = $day->format('Y-m-d');

            $days[] = [
                'date'      => $day,
                'inMonth'   => $day->month === $month->month,
                'entries'   => $byDay->get($key, collect()),
                'unsettled' => (int) $unsettled->get($key, 0),
            ];
        }

        return $days;
    }

    /**
     * @return Collection<int, SocialCalendarEntry>
     */
    public function day(CarbonImmutable $day): Collection
    {
        return $this->approvedByDay()->get($day->format('Y-m-d'), collect());
    }

    /**
     * Rozbija posty na pozycje (post × format).
     *
     * @param  Collection<int, SocialReviewItem>  $items
     * @return Collection<int, SocialCalendarEntry>
     */
    private function entries(Collection $items): Collection
    {
        return $items
            ->flatMap(fn (SocialReviewItem $item) => array_map(
                fn (string $format) => new SocialCalendarEntry($item, $format),
                $item->post->formats,
            ))
            ->sortBy([
                fn (SocialCalendarEntry $a, SocialCalendarEntry $b) => ($a->post()->publishAt?->getTimestamp() ?? 0) <=> ($b->post()->publishAt?->getTimestamp() ?? 0),
                fn (SocialCalendarEntry $a, SocialCalendarEntry $b) => strcmp($a->format, $b->format),
            ])
            ->values();
    }
}
