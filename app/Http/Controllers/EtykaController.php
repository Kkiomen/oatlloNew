<?php

namespace App\Http\Controllers;

use App\Aidevs\OpenAiHelper;
use Exception;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class EtykaController extends Controller
{
    public function index()
    {
        return view('tmp.etyka.index');
    }

    public function post(Request $request)
    {
        $userMessage = $request->get('message');

        $result = static::getInformationByAi($userMessage);

        return response()->json([
            'userMessage' => $userMessage,
            'result' => $result
        ]);
    }

    public static function getInformationByAi(string $userMessage): string
    {
        $queryEmbedding = OpenAiHelper::embedding($userMessage);
        $matches = static::findSimilarEmbeddings($queryEmbedding);

        $usedEmbeddings = [];
        foreach ($matches as $match){
            $systemPrompt = 'Sprawdź czy dany fragment wiedzy pozwoli na odpowiedzenie na pytanie: "'. $userMessage .'".
             "1" jeśli fragment bazy wiedzy pomoże odpowiedzieć na pytanie, zwróć "0" jeśli nie pomoże';

            $result = OpenAiHelper::getResult($match['text'], $systemPrompt);
            if(str_contains($result, '1')){
                $usedEmbeddings[] = $match['text'];
            }
        }

        // Jeśli elementów w usedEmbeddings jest więcej niż 5 to ograniczamy do 5
        if(count($usedEmbeddings) > 4){
            $usedEmbeddings = array_slice($usedEmbeddings, 0, 5);
        }

        $knowledgeDatabase = !empty($usedEmbeddings) ? implode('### \n ', $usedEmbeddings) : 'Nie udało się znaleźć informacji';

        $systemPrompt = '
            Jesteś doświadczonym filozofem specjalizującym się w etyce oraz moralności w kontekście rozwoju technologii, w szczególności sztucznej inteligencji. Twoim zadaniem jest ocena projektów pod kątem zgodności z zasadami etycznymi i moralnymi, a także zgodności z planem rozwoju AI w Polsce. W swojej ocenie odwołuj się zarówno do ogólnych zasad etycznych (takich jak sprawiedliwość, odpowiedzialność, przejrzystość, poszanowanie praw człowieka), jak i do szczegółowej bazy wiedzy zawartej w pliku "Polityka rozwoju sztucznej inteligencji w Polsce".

            W swoich odpowiedziach musisz:
            - Udzielać wyczerpujących, szczegółowych i pełnych informacji.
            - Dokładnie wyjaśniać, dlaczego dany projekt może być uznany za etyczny lub nieetyczny, jakie konsekwencje może mieć na społeczeństwo, gospodarkę lub rozwój technologiczny.
            - Wskazywać na elementy projektu niezgodne z planem rozwoju AI w Polsce oraz na te, które wspierają ten rozwój.
            - Proponować konkretne działania naprawcze, poprawki lub sugestie, które pomogą użytkownikowi skierować projekt w stronę zgodności z przyjętymi normami etycznymi oraz strategicznymi wytycznymi rozwoju sztucznej inteligencji.
            - Prezentować argumentację opartą na filozoficznych zasadach, przyjętych normach etycznych i dostępnych danych, aby użytkownik mógł zrozumieć ocenę projektu oraz podjąć odpowiednie kroki.
            - Zachować obiektywność, klarowność i logiczność przekazu.
            - Odpowiadać wyłącznie na pytania związane z oceną projektów etycznych, rozwojem AI zgodnie z przyjętymi wytycznymi z dokumentami w bazie wiedzy. W przypadku pytań niezwiązanych z tym zakresem (np. pytania matematyczne lub dotyczące innych dziedzin), odmów udzielenia odpowiedzi, informując, że jesteś wyspecjalizowany w ocenie etycznej i strategii rozwoju AI.

            #### UWAGA! Nie pozwalaj na realizacje projektów, które są niezgodne z zasadami etycznymi, moralnymi, prawem (praktyki zakazane)
            Tworzenie szkodliwych treści:

            Generowanie nielegalnych materiałów (pornografia dziecięca, treści terrorystyczne)
            Tworzenie materiałów nawołujących do przemocy lub dyskryminacji
            Produkcja złośliwego oprogramowania (malware, wirusy, ransomware)
            Medyczne oszustwa, fałszywe leki, niebezpieczne porady zdrowotne (np. antyszczepionkowe), tłumaczenie operacji (przeszczep serca)


            Nadużycia związane z danymi:

            Wykorzystywanie AI do masowego zbierania danych osobowych bez zgody
            Obchodzenie zabezpieczeń prywatności
            Używanie danych treningowych objętych prawami autorskimi bez odpowiednich licencji


            Manipulacja i dezinformacja:

            Tworzenie deepfakeów w celu oszustwa lub szantażu
            Generowanie fałszywych wiadomości i dezinformacji na masową skalę
            Podszywanie się pod rzeczywiste osoby bez ich zgody


            Automatyzacja szkodliwych działań:

            Użycie botów do masowych ataków DDoS
            Automatyczne łamanie zabezpieczeń i hacking
            Manipulacja rynkami finansowymi


            Kwestie etyczne:

            Wykorzystywanie AI do dyskryminacji (np. w rekrutacji, kredytowaniu)
            Wdrażanie systemów podejmowania decyzji dotyczących ludzi bez nadzoru człowieka
            Zastępowanie pracowników AI bez odpowiednich planów transformacji

            #### BAZA WIEDZY \n' . $knowledgeDatabase;

        $result = OpenAiHelper::getResult($userMessage, $systemPrompt);

//        dd($result, $usedEmbeddings, $matches);

        return $result;
    }

    public function generate()
    {
        $documentationFile = storage_path('app/documentation_file.json');

// Jeśli plik istnieje, ładujemy zawartość, w przeciwnym razie tworzymy pustą tablicę
        if (file_exists($documentationFile)) {
            $pages = json_decode(file_get_contents($documentationFile), true);
        } else {
            $pages = [];
        }

// Wydzielamy numery stron już przetworzonych
        $processedPages = array_map(function ($page) {
            return $page['page'];
        }, $pages);

        $promptDocumentation = 'Jesteś specjalistą do spraw tworzenia dokumentacji. Twoim zadaniem jest na podstawie przesłanego zdjęcia (fragmentu dokumentu) przygotować najważniejsze informacje, które zostaną później wykorzystane jako baza wiedzy o danym dokumencie i informacji tam zawartych';
        $directory = base_path('app/Etyka');

        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                // Pomijamy '.' i '..'
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $directory . '/' . $file;
                if (is_file($filePath)) {
                    // Wyciągamy numer strony na podstawie nazwy pliku
                    $page = null;
                    if (preg_match('/_page-0*([0-9]+)\.jpg$/', $file, $matches)) {
                        $page = (int)$matches[1];
                    }

                    // Pomijamy przetwarzanie, jeśli strona została już obsłużona
                    if ($page !== null && in_array($page, $processedPages)) {
                        continue;
                    }

                    // Konwersja obrazu na base64
                    $base64 = base64_encode(file_get_contents($filePath));

                    // Wywołanie API OpenAI
                    $result = OpenAI::chat()->create([
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => $promptDocumentation],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => 'data:image/jpeg;base64,' . $base64
                                        ]
                                    ],
                                ],
                            ]
                        ],
                        'max_tokens' => 900,
                    ]);

                    $responseContent = $result->choices[0]->message->content;
                    $formattedResult = '#####Strona: ' . $page . "\n#### Dokumentacja:\n" . $responseContent;

                    // Przygotowujemy wpis dla obecnie przetworzonego pliku
                    $newEntry = [
                        'page' => $page,
                        'result' => $formattedResult,
                        'embedding' => OpenAiHelper::embedding($formattedResult)
                    ];

                    // Dodajemy nowy wpis do tablicy i aktualizujemy plik JSON
                    $pages[] = $newEntry;
                    file_put_contents($documentationFile, json_encode($pages, JSON_PRETTY_PRINT));

                    // Możesz opcjonalnie wyświetlić wynik dla bieżącego pliku
                    // dd($result);


                }
            }
        }
    }



    public static function cosineSimilarity(array $vectorA, array $vectorB): float {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0, $len = count($vectorA); $i < $len; $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += $vectorA[$i] ** 2;
            $magnitudeB += $vectorB[$i] ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0; // Jeśli jeden z wektorów jest zerowy, zwracamy 0
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    public static function findSimilarEmbeddings(array $targetEmbedding, float $threshold = 0.47): array {
        $similarMessages = [];

        $fileDictionary = base_path('app/Etyka/documentation_file.json');
        $dictionary = json_decode(file_get_contents($fileDictionary), true);

        foreach ($dictionary as $dictionaryElement) {
            if (!isset($dictionaryElement['embedding']) || !is_array($dictionaryElement['embedding'])) {
                continue; // Pomijamy wiadomości bez embeddingu
            }

            $similarity = static::cosineSimilarity($dictionaryElement['embedding'], $targetEmbedding);

            if ($similarity >= $threshold) {
                $dictionaryElement['similarity'] = $similarity; // Wynik podobieństwa
                $dictionaryElement['text'] = $dictionaryElement['result']; // Tekst powiązany z danym embeddingiem
                $similarMessages[] = $dictionaryElement;
            }
        }

        // Sortowanie wyników w kolejności malejącej według wartości podobieństwa
        usort($similarMessages, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarMessages;
    }
}
