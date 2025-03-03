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

        $fileDictionary = base_path('app/Etyka/documentation_file.json');
        $dictionary = json_decode(file_get_contents($fileDictionary), true);

        // Funkcja obliczająca kosinusowe podobieństwo dwóch wektorów
        function cosineSimilarity(array $vec1, array $vec2): float {
            if (count($vec1) !== count($vec2)) {
                throw new Exception('Wektory muszą mieć taką samą długość.');
            }

            $dotProduct = 0.0;
            $normVec1 = 0.0;
            $normVec2 = 0.0;

            for ($i = 0; $i < count($vec1); $i++) {
                $dotProduct += $vec1[$i] * $vec2[$i];
                $normVec1 += $vec1[$i] ** 2;
                $normVec2 += $vec2[$i] ** 2;
            }

            if ($normVec1 == 0 || $normVec2 == 0) {
                return 0.0;
            }

            return $dotProduct / (sqrt($normVec1) * sqrt($normVec2));
        }

// Funkcja przeszukująca bazę wiedzy w poszukiwaniu elementów, które mają podobieństwo >= 75%
        function findMatchingElements(array $knowledgeBase, array $queryEmbedding, float $threshold = 0.75): string {
            $result = '';

            foreach ($knowledgeBase as $element) {
                // Sprawdzamy, czy element posiada wymagane pola
                if (!isset($element['embedding']) || !isset($element['result'])) {
                    continue;
                }

                $similarity = cosineSimilarity($queryEmbedding, $element['embedding']);

                // Jeżeli podobieństwo spełnia warunek, dodajemy element do wyniku
                if ($similarity >= $threshold) {
                    $result .= $element['result'];
                }
            }

            return $result;
        }

        $queryEmbedding = OpenAiHelper::embedding($userMessage);

        $matches = findMatchingElements($dictionary, $queryEmbedding);

        $systemPrompt = 'Jesteś filozofem. Twoim zdaniem jest doradzenie użytkownikowi czy jego projekt jest etyczny/mornalny, zweryfikować czy jego projekt jest zgodny z planem rozwoju AI w polsce (informacje w bazie wiedzy). Nie możesz pisać o niczym innym. #### BAZA WIEDZY' . $matches;

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
}
