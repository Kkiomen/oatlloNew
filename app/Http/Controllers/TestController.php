<?php

namespace App\Http\Controllers;



use App\Aidevs\OpenAiHelper;
use App\Services\PracaMagisterska;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class TestController extends Controller
{
    const TASK = 'photos';
    const API_KEY = '6982ce64-7d13-4d2e-a23a-ba07ba2c8f45';

    const URL_POLIGON_VERIFY = 'https://poligon.aidevs.pl/verify';


    public function test(Request $reques, PracaMagisterska $pracaMagisterska)
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

        $promptDocumentation = 'Jesteś specjalistą do spraw tworzenia dokumentacji. Twoim zadaniem jest na podstawie przesłanego zdjęcia (fragmentu dokumentu) przygotować najważniejsze informacje, które zostaną później wykorzystane jako baza wiedzy o danym dokumencie i informacji tam zawartych. Opisz najważniejsze informacje. Nie możesz pominąć szczegółów i musisz być precyzyjny. Na początku w dwóch zdaniach opisz w skrócie co znajdziemy na stronie. A następnie szczegółowo opisz informacje tam zawarte. Pamiętaj, że będzie to fragment bazy wiedze a użytkownik może zadać szczegółowe pytanie';
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
//                    $result = OpenAI::chat()->create([
//                        'model' => 'gpt-4o-mini',
//                        'messages' => [
//                            [
//                                'role' => 'user',
//                                'content' => [
//                                    ['type' => 'text', 'text' => $promptDocumentation],
//                                    [
//                                        'type' => 'image_url',
//                                        'image_url' => [
//                                            'url' => 'data:image/jpeg;base64,' . $base64
//                                        ]
//                                    ],
//                                ],
//                            ]
//                        ],
//                        'max_tokens' => 900,
//                    ]);

                    $responseContent = $result->choices[0]->message->content;
                    $formattedResult = $responseContent .  ' \n #####Strona: ' . $page . "\n#### Dokumentacja:\n" ;

                    // Przygotowujemy wpis dla obecnie przetworzonego pliku
//                    $newEntry = [
//                        'page' => $page,
//                        'result' => $formattedResult,
//                        'embedding' => OpenAiHelper::embedding($formattedResult)
//                    ];
//
//                    // Dodajemy nowy wpis do tablicy i aktualizujemy plik JSON
//                    $pages[] = $newEntry;
//                    file_put_contents($documentationFile, json_encode($pages, JSON_PRETTY_PRINT));

                    // Możesz opcjonalnie wyświetlić wynik dla bieżącego pliku
                    // dd($result);


                }
            }
        }


//
//
//                    $promptDocumentation = 'Jesteś specjalistą do spraw tworzenia dokumentacji. Twoim zadaniem jest na podstawie przesłanego zdjęcia (fragmentu dokumentu) przygotować najważniejsze informacje, które zostaną później wykorzystane jako baza wiedzy o danym dokumencie i informacji tam zawartych';
//        $pages = [];
//
//        $directory = base_path('app/Etyka');
//        $lp = 0;
//        if (is_dir($directory)) {
//            $files = scandir($directory);
//            // Przechodzimy przez każdy element
//            foreach ($files as $file) {
//                // Pomijamy '.' i '..'
//                if ($file !== '.' && $file !== '..') {
//                    $filePath = $directory . '/' . $file;
//
//                    if (is_file($filePath)) {
//                        $base64 = base64_encode(file_get_contents($filePath));
//
//                        $result = OpenAI::chat()->create([
//                            'model' => 'gpt-4o-mini',
//                            'messages' => [
//                                [
//                                    'role' => 'user',
//                                    'content' => [
//                                        ['type' => 'text', 'text' => $promptDocumentation],
//                                        [
//                                            'type' => 'image_url',
//                                            "image_url" => [
//                                                'url' => 'data:image/jpeg;base64,' . $base64
//                                            ]
//                                        ],
//                                    ],
//                                ]
//                            ],
//                            'max_tokens' => 900,
//                        ]);
//
//                        $page = null;
//                        if (preg_match('/_page-0*([0-9]+)\.jpg$/', $file, $matches)) {
//                            $page = (int)$matches[1];
//                        }
//
//                        $result = $result->choices[0]->message->content;
//                        $lp++;
//
//                        $result = '#####Strona: ' . $page . '\n #### Dokumentacja: \n' . $result;
//
//                        $pages[] = [
//                            'page' => $page,
//                            'result' => $result,
//                            'embedding' => OpenAiHelper::embedding($result)
//                        ];
//
//                        dump($page, $result);
//                        if ($lp > 2) {
//                            break;
//                        }
//                    }
//                }
//            }
//        }
//
//        $json = json_encode($pages, JSON_PRETTY_PRINT);
//
//        file_put_contents(storage_path('app/documentation_file.json'), $json);
////
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
///
//### PRZYKŁAD:
//        $pages = [
//            [
//                'page' => 1,
//                'result' => 'Dokumentacja techniczna to dokument, który zawiera informacje techniczne na temat produktu, systemu lub usługi. Dokumentacja techniczna może zawierać informacje na temat specyfikacji produktu, instrukcji obsługi, opisu funkcji, procedur, testów, konfiguracji, instalacji, integracji, rozwoju, konserwacji, wsparcia, bezpieczeństwa, zgodności, wydajności, dostępności',
//                'embedding' => [0,15,15,15,6,8,21,8,5,8,16,26,15,416]
//            ],
//            [
//                'page' => 2,
//                'result' => 'Dokumentacja techniczna to dokument, który zawiera informacje techniczne na temat produktu, systemu lub usługi. Dokumentacja techniczna może zawierać informacje na temat specyfikacji produktu, instrukcji obsługi, opisu funkcji, procedur, testów, konfiguracji, instalacji, integracji, rozwoju, konserwacji, wsparcia, bezpieczeństwa, zgodności, wydajności, dostępności',
//                'embedding' => [0,15,15,15,6,8,21,8,5,8,16,26,15,416]
//            ],
//            [
//                'page' => 3,
//                'result' => 'Dokumentacja techniczna to dokument, który zawiera informacje techniczne na temat produktu, systemu lub usługi. Dokumentacja techniczna może zawierać informacje na temat specyfikacji produktu, instrukcji obsługi, opisu funkcji, procedur, testów, konfiguracji, instalacji, integracji, rozwoju, konserwacji, wsparcia, bezpieczeństwa, zgodności, wydajności, dostępności',
//                'embedding' => [0,15,15,15,6,8,21,8,5,8,16,26,15,416]
//            ],
//        ];
//
//        $json = json_encode($pages, JSON_PRETTY_PRINT);
//
//        file_put_contents(storage_path('app/pliffk.json'), $json);

//        $result = OpenAI::chat()->create([
//            'model' => 'gpt-4o-mini',
//            'messages' => [
//                [
//                    'role' => 'user',
//                    'content' => [
//                        ['type' => 'text', 'text' => 'Describe image'],
//                        [
//                            'type' => 'image_url',
//                            "image_url" => [
//                                'url' => $base64
//                            ]
//                        ],
//                    ],
//                ]
//            ],
//            'max_tokens' => 900,
//        ]);
//
//        dd($result);

//        $pracaMagisterska->codeReviewCodeFromFileVersionOne();
//
//        $path = app_path('Magisterka/example_code_sa.txt');

//        $fileCode = file_get_contents($path);
//        $analyze = CodeReviewAnalyzerService::analyze($fileCode);
//
//        dd(DocumentationFileLoader::loadAllDocByAnalyze($analyze));
//        dd(DocumentationFileLoader::servicePresentationPut());
//        $pracaMagisterska->test();

//        InternalUrlsGenerator::generate();
    }
}
