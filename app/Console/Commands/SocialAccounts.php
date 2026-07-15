<?php

namespace App\Console\Commands;

use App\Services\Social\Publish\ZernioClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Wypisuje konta podpięte do klucza Zernio – stąd bierze się ZERNIO_ACCOUNT_ID.
 *
 * Istnieje, bo to jedyna wartość konfiguracji, której NIE DA SIĘ przepisać
 * z panelu ani zgadnąć: klucz kopiujesz, a id konta trzeba wyciągnąć z API
 * (`GET /v1/accounts`). Bez tej komendy pierwszy kontakt z Zernio zaczynałby się
 * od ręcznego curla z tokenem w historii powłoki.
 *
 * Nic nie publikuje i niczego nie zapisuje – to czysty odczyt.
 */
class SocialAccounts extends Command
{
    protected $signature = 'social:accounts';

    protected $description = 'Wypisuje konta podpięte do klucza Zernio (stąd ZERNIO_ACCOUNT_ID)';

    public function handle(ZernioClient $zernio): int
    {
        if (trim((string) config('social.zernio.key')) === '') {
            $this->error('Brak ZERNIO_API_KEY w .env.');
            $this->line('Klucz: zernio.com -> Settings -> API Keys -> Create API Key.');
            $this->line('Pokazują go RAZ (trzymają tylko hash SHA-256), więc od razu wklej do .env.');

            return self::FAILURE;
        }

        try {
            $accounts = $zernio->accounts();
        } catch (RequestException $e) {
            $this->error('Zernio odpowiedziało HTTP ' . $e->response->status() . ': '
                . mb_substr($e->response->body(), 0, 300));

            if ($e->response->status() === 401) {
                $this->line('401 = zły albo cofnięty klucz. Sprawdź, czy ZERNIO_API_KEY zaczyna się od "sk_".');
            }

            return self::FAILURE;
        } catch (ConnectionException $e) {
            $this->error('Brak połączenia z Zernio: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($accounts === []) {
            $this->warn('Klucz działa, ale nie ma podpiętego ŻADNEGO konta.');
            $this->line('Podepnij Instagrama w panelu Zernio (pierwsze 2 konta są darmowe), potem odpal to ponownie.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($accounts as $account) {
            $rows[] = [
                $this->idOf($account),
                $account['platform'] ?? '?',
                $account['username'] ?? $account['displayName'] ?? '?',
                $account['platformStatus'] ?? (($account['isActive'] ?? false) ? 'active' : 'inactive'),
                $this->tokenNote($account),
            ];
        }

        $this->table(['id (-> ZERNIO_ACCOUNT_ID)', 'platforma', 'konto', 'status', 'token'], $rows);

        $instagram = array_values(array_filter(
            $accounts,
            fn (array $a) => ($a['platform'] ?? '') === 'instagram',
        ));

        if ($instagram === []) {
            $this->warn('Żadne z tych kont nie jest Instagramem – moduł publikuje wyłącznie na Instagrama.');

            return self::FAILURE;
        }

        $this->newLine();

        // NIE ZGADUJEMY przy kilku kontach. Ten klucz widzi więcej niż jedną markę
        // (Oatllo i Aisello), a "weź pierwszy Instagram z listy" wskazywał TEN ZŁY –
        // posty Oatllo poleciałyby na cudzy profil. Tego się nie cofa.
        if (count($instagram) > 1) {
            $this->warn('Masz ' . count($instagram) . ' konta na Instagramie – wybierz świadomie, ja nie zgaduję:');

            foreach ($instagram as $account) {
                $this->line('  ZERNIO_ACCOUNT_ID=' . $this->idOf($account)
                    . '   # ' . ($account['username'] ?? '?'));
            }

            $this->newLine();
            $this->line('Wklej do .env NA PRODUKCJI (tam leci tick) ten, na który ma publikować Oatllo.');

            return self::SUCCESS;
        }

        $this->info('Wklej do .env NA PRODUKCJI (tam leci tick):');
        $this->line('  ZERNIO_ACCOUNT_ID=' . $this->idOf($instagram[0])
            . '   # ' . ($instagram[0]['username'] ?? '?'));

        return self::SUCCESS;
    }

    /**
     * Zernio zwraca `_id` (mongowe), nie `id`. Fallbacki zostają, bo to jedyne
     * miejsce zależne od kształtu ich odpowiedzi, a "?" w tej kolumnie jest
     * bezużyteczne.
     *
     * @param  array<string, mixed>  $account
     */
    private function idOf(array $account): string
    {
        return (string) ($account['_id'] ?? $account['id'] ?? $account['accountId'] ?? '?');
    }

    /**
     * Token Instagrama WYGASA (Meta odnawia go okresowo). Wygasły = tick zacznie
     * dostawać 401 i posty przestaną wychodzić, więc lepiej zobaczyć to teraz niż
     * po cichym braku publikacji.
     *
     * @param  array<string, mixed>  $account
     */
    private function tokenNote(array $account): string
    {
        $expires = $account['tokenExpiresAt'] ?? null;

        if (! is_string($expires) || $expires === '') {
            return '—';
        }

        try {
            $at = \Carbon\CarbonImmutable::parse($expires);
        } catch (\Throwable) {
            return '—';
        }

        $days = \Carbon\CarbonImmutable::now()->diffInDays($at, false);

        if ($days < 0) {
            return 'WYGASŁ';
        }

        // ceil, nie (int): rzutowanie obcina, więc token ważny jeszcze 4.99 dnia
        // meldował "za 4 dni". Przy terminie ważności zaniżanie o dzień to ostatnie,
        // czego się chce.
        return $at->format('Y-m-d') . ' (za ' . (int) ceil($days) . ' dni)';
    }
}
