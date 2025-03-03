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

        $queryEmbedding = OpenAiHelper::embedding($userMessage);
        $matches = $this->findSimilarEmbeddings($queryEmbedding);

        $systemPrompt = 'Jesteś filozofem. Twoim zdaniem jest doradzenie użytkownikowi czy jego projekt jest etyczny/mornalny, zweryfikować czy jego projekt jest zgodny z planem rozwoju AI w polsce (informacje w bazie wiedzy). Nie możesz pisać o niczym innym. #### BAZA WIEDZY' .
            implode('### \n ', $matches);

        $result = OpenAiHelper::getResult($userMessage, $systemPrompt);

        return response()->json([
            'userMessage' => $userMessage,
            'result' => $result
        ]);
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



    function cosineSimilarity(array $vectorA, array $vectorB): float {
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

    function findSimilarEmbeddings(array $targetEmbedding, float $threshold = 0.75): array {
        $similarMessages = [];

        $fileDictionary = base_path('app/Etyka/documentation_file.json');
        $dictionary = json_decode(file_get_contents($fileDictionary), true);

        foreach ($dictionary as $dictionaryElement) {
            if (!isset($dictionaryElement['embedding']) || !is_array($dictionaryElement['embedding'])) {
                continue; // Pomijamy wiadomości bez embeddingu
            }

            $similarity = $this->cosineSimilarity($dictionaryElement['embedding'], $targetEmbedding);

            if ($similarity >= $threshold) {
                $dictionaryElement['similarity'] = $similarity; // Dodajemy wynik podobieństwa
                $similarMessages[] = $dictionaryElement;
            }
        }

        // Sortujemy wyniki według podobieństwa malejąco
        usort($similarMessages, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return $similarMessages;
    }
}
