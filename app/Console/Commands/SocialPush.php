<?php

namespace App\Console\Commands;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Wysyła LOKALNIE wyrenderowane grafiki na serwer, żeby Zernio miało skąd je wziąć.
 *
 * To brakujące ogniwo całej automatyzacji: Instagram nie przyjmuje plików, tylko
 * publiczne URL-e HTTPS. PNG-i powstają z headless Edge na Windowsie i są
 * gitignorowane (są wyliczalne z `.md`, więc nie ma ich po co wozić w gicie),
 * a produkcja nie ma ani przeglądarki, ani Remotiona. Ktoś więc musi przenieść
 * bajty – i to jest ta komenda.
 *
 * Kolejność: `social:export {slug}` (i `social:video {slug}` dla reela) -> `social:push {slug}`.
 *
 * Alternatywą był upload wprost do Zernio (`/v1/media/presign`), ale ich media
 * leży w temporary storage wygasającym PO 7 DNIACH od uploadu. Przy paczce
 * planowanej na miesiąc wszystko po ~8. dniu opublikowałoby się z martwym linkiem.
 */
class SocialPush extends Command
{
    protected $signature = 'social:push
                            {slug : Slug posta (musi być wcześniej wyeksportowany)}
                            {--from= : Katalog z plikami (domyślnie storage/app/social-export/{slug})}
                            {--to= : Baza URL serwera (domyślnie SOCIAL_PUSH_TARGET albo APP_URL)}
                            {--dry-run : Pokaż, co poszłoby na serwer, i nic nie wysyłaj}';

    protected $description = 'Wysyła wyeksportowane grafiki/reel posta na serwer (hosting pod publikacją)';

    public function handle(MarkdownSocialPostRepository $repository): int
    {
        $slug = (string) $this->argument('slug');
        $post = $repository->findBySlug($slug);

        if ($post === null) {
            $this->error("Nie ma posta '{$slug}' w " . config('social.path') . '.');

            return self::FAILURE;
        }

        $dir = (string) ($this->option('from') ?: storage_path('app/social-export/' . $slug));

        if (! File::isDirectory($dir)) {
            $this->error("Brak katalogu '{$dir}'. Najpierw: php artisan social:export {$slug}");

            return self::FAILURE;
        }

        $files = $this->filesToPush($dir, $post);

        if ($files === []) {
            $this->error("W '{$dir}' nie ma ani jednego PNG/MP4. Najpierw: php artisan social:export {$slug}");

            return self::FAILURE;
        }

        $target = rtrim((string) ($this->option('to') ?: config('social.push.target') ?: config('app.url')), '/');
        $token = (string) config('social.push.token');

        $this->line("Cel: <comment>{$target}</comment>");

        foreach ($files as $file) {
            $this->line('  ' . basename($file['path']) . '  ' . $this->human((int) filesize($file['path'])));
        }

        if ($this->option('dry-run')) {
            $this->info('--dry-run: nic nie wysłano.');

            return self::SUCCESS;
        }

        if (trim($token) === '') {
            $this->error('Brak SOCIAL_PUSH_TOKEN – bez niego serwer odrzuci wysyłkę (to ten sam sekret co SOCIAL_MEDIA_TOKEN na produkcji).');

            return self::FAILURE;
        }

        $sent = 0;

        foreach ($files as $file) {
            $path = $file['path'];

            try {
                $response = Http::withToken($token)
                    // `acceptJson` NIE jest kosmetyką: bez niego Laravel na błędzie
                    // walidacji robi PRZEKIEROWANIE (formularzowe 302) zamiast 422,
                    // klient grzecznie za nim idzie, dostaje 200 ze strony głównej
                    // i wysyłka odrzucona przez serwer wygląda jak udana.
                    ->acceptJson()
                    // Bez tego to samo przekierowanie zamieniłoby 413 w "sukces".
                    ->withoutRedirecting()
                    ->timeout(120)
                    ->attach('file', (string) file_get_contents($path), basename($path))
                    ->post($target . '/api/social/media/' . $slug, ['index' => $file['index']]);
            } catch (ConnectionException $e) {
                $this->error('  ✗ ' . basename($path) . ' – brak połączenia: ' . $e->getMessage());

                return self::FAILURE;
            }

            if (! $response->successful()) {
                $this->error('  ✗ ' . basename($path) . ' – HTTP ' . $response->status() . ': '
                    . mb_substr((string) ($response->json('message') ?? $response->body()), 0, 300));

                return self::FAILURE;
            }

            $url = $response->json('url');

            // Odpowiedź BEZ url-a to nie jest nasz endpoint (przekierowanie, proxy,
            // strona błędu z kodem 200). Milczący "sukces" tutaj znaczyłby, że
            // publikacja padnie dopiero za tydzień, na produkcji, po cichu.
            if (! is_string($url) || $url === '') {
                $this->error('  ✗ ' . basename($path) . ' – serwer odpowiedział ' . $response->status()
                    . ', ale bez URL-a pliku. To nie jest odpowiedź endpointu uploadu. Sprawdź --to.');

                return self::FAILURE;
            }

            $sent++;
            $this->line('  <info>✓</info> ' . basename($path) . ' -> ' . $url);
        }

        $this->newLine();
        $this->info("Wysłano {$sent} plik(ów). Publikacją zajmie się tick /api/cron o terminie z publish_at.");

        return self::SUCCESS;
    }

    /**
     * Co wysyłamy: slajdy posta + reel, jeśli post jest w formacie `reel`.
     *
     * Lista par, nie mapa `index => path`: reel i slajd 1 miałyby ten sam indeks
     * i jeden nadpisałby drugiego. Reel dostaje `index: 1`, bo `index` opisuje
     * wyłącznie pozycję slajdu, a nazwę pliku (`reel.mp4`) i tak skleja serwer
     * z rozpoznanego typu – klient nie ma wpływu na to, co ląduje na dysku.
     *
     * @return list<array{index: int, path: string}>
     */
    private function filesToPush(string $dir, SocialPost $post): array
    {
        $files = [];

        foreach (range(1, max(1, $post->slideCount())) as $index) {
            $png = $dir . DIRECTORY_SEPARATOR . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . '.png';

            if (File::exists($png)) {
                $files[] = ['index' => $index, 'path' => $png];
            }
        }

        $reel = $dir . DIRECTORY_SEPARATOR . 'reel.mp4';

        if (File::exists($reel) && $post->hasFormat('reel')) {
            $files[] = ['index' => 1, 'path' => $reel];
        }

        return $files;
    }

    private function human(int $bytes): string
    {
        return $bytes > 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
