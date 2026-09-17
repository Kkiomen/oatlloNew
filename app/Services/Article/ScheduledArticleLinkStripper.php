<?php

namespace App\Services\Article;

use App\Models\Article;

/**
 * Zdejmuje przy renderze linki do artykułów .md, które jeszcze nie wyszły.
 *
 * Kolejka `.md` ma ponad sto artykułów z `published_at` w przyszłości, a autor
 * lekcji albo artykułu podlinkowuje je od razu. Do dnia publikacji taki link
 * oddaje 404 - na żywej, zindeksowanej stronie, czyli dokładnie tam, gdzie Google
 * liczy jakość. Zamiast kasować linki w plikach (i pamiętać, żeby dopisać je
 * z powrotem), odpakowujemy `<a>` do samego tekstu tylko na czas oczekiwania:
 * w dniu publikacji link pojawia się sam, bez commita, jak cały artykuł.
 *
 * Tak jak InternalLinker: każdy błąd => treść bez zmian, render nigdy nie pada.
 */
class ScheduledArticleLinkStripper
{
    /** @var array<string,true>|null slug => true, memoizowane na czas żądania */
    private ?array $scheduled = null;

    public function __construct(private MarkdownArticleRepository $repository)
    {
    }

    public function strip(string $html): string
    {
        if ($html === '' || stripos($html, '<a') === false) {
            return $html;
        }

        try {
            $scheduled = $this->scheduledSlugs();
            if ($scheduled === []) {
                return $html;
            }

            $hosts = implode('|', array_map(
                fn (string $h) => preg_quote($h, '#'),
                array_unique(array_filter([
                    (string) config('social.brand.domain', 'oatllo.com'),
                    parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
                ]))
            ));

            $pattern = '#<a\b[^>]*\bhref=(["\'])(?:https?://(?:www\.)?(?:' . $hosts . '))?/([a-z0-9-]+)/?(?:[?\#][^"\']*)?\1[^>]*>(.*?)</a>#is';

            return preg_replace_callback($pattern, function (array $m) use ($scheduled) {
                return isset($scheduled[strtolower($m[2])]) ? $m[3] : $m[0];
            }, $html) ?? $html;
        } catch (\Throwable $e) {
            report($e);

            return $html;
        }
    }

    /**
     * @return array<string,true>
     */
    private function scheduledSlugs(): array
    {
        return $this->scheduled ??= $this->repository->all()
            ->reject(fn (Article $a) => $a->isLive())
            ->mapWithKeys(fn (Article $a) => [strtolower((string) $a->slug) => true])
            ->all();
    }
}
