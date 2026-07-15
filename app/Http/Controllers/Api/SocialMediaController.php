<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Social\Publish\SocialMediaStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mini-cloud: przyjmuje grafiki wyrenderowane LOKALNIE i hostuje je pod publicznym
 * URL-em, żeby Zernio (a przez nie Instagram) miało skąd je pobrać.
 *
 * Dlaczego to w ogóle istnieje: PNG-i powstają z headless Edge na Windowsie i są
 * gitignorowane – produkcja ich nie ma i nie umie zrobić. `social:push` wysyła tu
 * gotowe pliki, a `/api/cron` publikuje je z serwera.
 *
 * TO JEST PUBLICZNY ZAPIS PLIKÓW DO KATALOGU SERWOWANEGO PO HTTP, więc każda
 * decyzja poniżej jest celowa:
 *
 *  - **Nazwę pliku sklejamy SAMI** z numeru slajdu (`01.png`) albo z typu (`reel.mp4`).
 *    Klient nie ma wpływu ani na rozszerzenie, ani na ścieżkę – nie da się więc
 *    wgrać `.php` ani wyjść z katalogu, nawet gdyby ktoś przesłał taką nazwę.
 *  - **Slug przechodzi przez `Str::slug`** (SocialMediaStore), więc `../` znika.
 *  - **Typ pliku sprawdzamy po ZAWARTOŚCI** (magic bytes), nie po nazwie ani po
 *    nagłówku Content-Type – jedno i drugie pisze klient.
 *  - **Brak tokenu w configu => trasa NIE ISTNIEJE** (routes/api.php). Domyślnie
 *    wyłączone bije domyślnie otwarte.
 */
class SocialMediaController extends Controller
{
    public function __construct(private readonly SocialMediaStore $store)
    {
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        if (! $this->tokenMatches($request)) {
            // 404, nie 403: endpoint istnieje tylko dla tego, kto zna token.
            // Bez tego skanery dostają potwierdzenie, że jest tu co łamać.
            return response()->json(['message' => 'Not found.'], 404);
        }

        // PHP wycina żądanie większe niż post_max_size ZANIM dojdzie do walidacji:
        // $_FILES i $_POST są wtedy puste, a `required` powiedziałoby tylko "brak
        // pliku" – i szłoby się godzinę zastanawiać, czemu reel "nie dochodzi".
        //
        // Porównujemy CONTENT_LENGTH z post_max_size, a nie samo "brak pliku przy
        // niepustym body": ten drugi warunek strzelał przy KAŻDYM żądaniu bez pliku
        // i kazał podnosić limity, które wynosiły 128M. Mylna diagnoza jest gorsza
        // niż żadna – wysyła człowieka w tydzień grzebania nie tam, gdzie trzeba.
        $postMax = $this->iniBytes((string) ini_get('post_max_size'));
        $length = (int) $request->server('CONTENT_LENGTH');

        if ($request->file('file') === null && $postMax > 0 && $length > $postMax) {
            return response()->json([
                'message' => 'Żądanie ma ' . $length . ' B, a PHP przyjmuje najwyżej post_max_size='
                    . ini_get('post_max_size') . ' – odrzuciło je przed aplikacją. Podnieś post_max_size '
                    . 'i upload_max_filesize (jest ' . ini_get('upload_max_filesize') . '), a na nginxie '
                    . 'client_max_body_size.',
            ], 413);
        }

        // Plik większy niż upload_max_filesize DOCIERA jako obiekt, tylko nieważny –
        // więc sprawdzamy to PRZED walidacją. Reguła `file` odrzuciłaby go pierwsza
        // i odpowiedziała "The file failed to upload", co nie mówi ani co jest nie
        // tak, ani gdzie to podkręcić.
        $file = $request->file('file');

        if ($file !== null && ! $file->isValid()) {
            return response()->json([
                'message' => 'Upload nieudany (' . $file->getErrorMessage() . '). Prawie na pewno plik jest większy '
                    . 'niż upload_max_filesize=' . ini_get('upload_max_filesize') . ' – reel waży ~3 MB, więc to '
                    . 'pierwsze, co trzeba podnieść (plus post_max_size i client_max_body_size na nginxie).',
            ], 413);
        }

        $validated = $request->validate([
            'file'  => ['required', 'file'],
            'index' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $max = (int) config('social.media.max_bytes');

        if ($file->getSize() > $max) {
            return response()->json([
                'message' => "Plik ma {$file->getSize()} B, limit to {$max} B.",
            ], 422);
        }

        $extension = $this->sniff((string) $file->getRealPath());

        if ($extension === null) {
            return response()->json([
                'message' => 'Dozwolone są wyłącznie PNG i MP4 (sprawdzane po zawartości pliku).',
            ], 422);
        }

        $name = $this->store->fileName((int) $validated['index'], $extension);
        $path = $this->store->put($slug, $name, (string) file_get_contents((string) $file->getRealPath()));

        return response()->json([
            'success' => true,
            'slug'    => $this->store->normalizeSlug($slug),
            'file'    => $name,
            'path'    => $path,
            'url'     => $this->store->url($slug, $name),
        ]);
    }

    /**
     * "8M" / "128M" / "1G" -> bajty. ini_get zwraca skrót, a porównywać trzeba
     * z CONTENT_LENGTH, które jest liczbą.
     */
    private function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $number = (int) $value;
        $suffix = strtolower(substr($value, -1));

        return match ($suffix) {
            'g'     => $number * 1024 * 1024 * 1024,
            'm'     => $number * 1024 * 1024,
            'k'     => $number * 1024,
            default => $number,
        };
    }

    /**
     * Porównanie tokenu w stałym czasie – zwykłe `===` przecieka długość
     * wspólnego prefiksu przez czas odpowiedzi.
     */
    private function tokenMatches(Request $request): bool
    {
        $expected = (string) config('social.media.token');

        if (trim($expected) === '') {
            return false;
        }

        return hash_equals($expected, (string) $request->bearerToken());
    }

    /**
     * Rozpoznanie typu po MAGIC BYTES.
     *
     * Nazwa pliku i Content-Type pochodzą od klienta, więc nie są dowodem na nic.
     * Pliki lądują w katalogu serwowanym publicznie – o tym, co się w nim znajdzie,
     * musi decydować zawartość.
     */
    private function sniff(string $path): ?string
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $head = (string) fread($handle, 12);
        fclose($handle);

        if (str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        // MP4/MOV: pudełko ftyp zaczyna się od 4 bajtów rozmiaru, potem 'ftyp'.
        if (strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp') {
            return 'mp4';
        }

        return null;
    }
}
