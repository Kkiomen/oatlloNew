<?php

declare(strict_types=1);

namespace App\Aidevs;

use App\Enums\OpenAiModel;
use Exception;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class TaskService
{

    const TASK = 'POLIGON';
    const API_KEY = '6982ce64-7d13-4d2e-a23a-ba07ba2c8f45';

    const URL_POLIGON_VERIFY = 'https://poligon.aidevs.pl/verify';

    public function task1()
    {
        $url = 'https://xyz.ag3nts.org/';
//        $login = 'tester';
//        $password = '574e112a';
//
//        $response = Http::get($url);
//        $bodyPage = $response->body();
//
        /*        preg_match('/<p id="human-question">.*?<br\s*\/?>(.*?)<\/p>/s', $bodyPage, $matches);*/
//        $question = $matches[1];
//
//        $r = AidevsPrompt::generateContent($question);
//
//        $response = Http::asForm()->post($url, [
//            'username' => $login,
//            'password' => $password,
//            'answer' => $r
//        ]);
//        dump($response);
//        dd($response->body());
//        echo 'dfdsf';
    }

    public static function poligon(?string $url, string $task, mixed $data): string
    {
        $url = $url ?? self::URL_POLIGON_VERIFY;

        $re = Http::post($url, [
            'task' => $task,
            'apikey' => self::API_KEY,
            'answer' => $data
        ]);



        dd($re->body());

        return $re->json()['message'];
    }

    public function task2()
    {
        $content = Http::get('https://centrala.ag3nts.org/data/'.self::API_KEY.'/json.txt');
        $jsonInformation = json_decode($content->body(), true);
        $jsonInformation['apikey'] = self::API_KEY ;
        $testData = $jsonInformation['test-data'];

        foreach ($testData as &$data){
            if(isset($data['question']) && str_contains($data['question'], '+')){
                $dataCalc = explode('+', $data['question']);
                $data['answer'] = (int)$dataCalc[0] + (int)$dataCalc[1];
            }

            if(isset($data['test'])){
                $question = $data['test']['q'];
                $answer = OpenAiHelper::getResult(
                    user: $question,
                    system: 'Odpowiedz na pytanie, podaj poprawną odpowiedź i nic więcej');

                $data['test']['a'] = str_replace(['.'], '', $answer);
            }
        }

        $jsonInformation['test-data'] = $testData;


        try{
            $re = Http::post('https://centrala.ag3nts.org/report', [
                'task' => 'JSON',
                'apikey' => self::API_KEY,
                'answer' => $jsonInformation
            ]);
            dd($re->body());
        }catch (\Exception $e){
            dd($e);
        }

    }

    public function task4(): string
    {
        return '
            Jesteś robotem a twoim celem jest zwrócenie ścieżki poleceń ruchów w formacie json
            UWAGI:
            - Odpowiedź musi być w formacie JSON
            - Jeśli robot ma iść w górę dodaj do tablicy "UP"
            - jeśli robot ma iść w dół dodaj do tablicy "DOWN"
            - jeśli robot ma iść w prawo dodaj do tablicy "RIGHT"
            - jeśli robot ma iść w lewo dodaj do tablicy "LEFT"

            <result structure description>
            Odpowiedź ma być obiektem JSON z polem "steps", gdzie jako wartość przekazujesz string z informacjami w jakim kierunku musi przemieszczać się robot. Każde polecenie jest po przecinku
            </result structure description>

            <trasa robota>
            - masz iść 2 razy w górę
            - masz iść 2 razy w prawo
            - masz iść 2 razy w dół
            - masz iść 4 razy w prawo
            </trasa robota>


            <example result json structure>
            {
             "steps": "UP, RIGHT, DOWN, LEFT"
            }
            </example result json structure>
        ';
    }

    public function tasks02e01()
    {
        $transcription = '';

        $directory = base_path('app/Aidevs/taskData/tasks02e01');
        if (is_dir($directory)) {
            // Pobieramy wszystkie pliki i foldery w katalogu
            $files = scandir($directory);
            // Przechodzimy przez każdy element
            foreach ($files as $file) {
                // Pomijamy '.' i '..'
                if ($file !== '.' && $file !== '..') {
                    $filePath = $directory . '/' . $file;

                    // Sprawdzamy, czy jest to plik
                    if (is_file($filePath)) {
                        $response =  OpenAI::audio()->transcribe([
                            'model' => 'whisper-1',
                            'file' => fopen($filePath, 'r'),
                            'response_format' => 'verbose_json',
                            'timestamp_granularities' => ['segment', 'word']
                        ]);

                        $transcription .= '### <interview><interviewee>' . $file . "</interviewee>\n <transcription>" . $response->text . "</transcription> </interview>\n\n";
                    }
                }
            }
        }

        $systemPrompt= 'Weź głęboki oddech. Z otrzymanych transkryptów zbuduj wspólny kontekst dla swojego prompta

Znajdź odpowiedź na pytanie, na jakiej ulicy znajduje się uczelnia, na której wykłada Andrzej Maj

Pamiętaj, że zeznania świadków mogą być sprzeczne, niektórzy z nich mogą się mylić, a inni odpowiadać w dość dziwaczny sposób.

Nazwa ulicy nie pada w treści transkrypcji. Musisz użyć wiedzy wewnętrznej modelu, aby uzyskać odpowiedź.';

        $answer = OpenAiHelper::getResult(
            user: $transcription,
            system: $systemPrompt);

        dd($answer);

        //PERSONAL|JAGIELLO|NOWYOUKNOW
    }

    public function task11(): string
    {
        //NOWYOUKNOW

        $directory = base_path('app/Aidevs/taskData/pliki_z_fabryki/facts');
        $facts = '';
        if (is_dir($directory)) {
            $files = scandir($directory);
            // Przechodzimy przez każdy element
            foreach ($files as $file) {
                // Pomijamy '.' i '..'
                if ($file !== '.' && $file !== '..') {
                    $filePath = $directory . '/' . $file;

                    $content = file_get_contents($filePath);

                    if(str_contains($content, 'entry deleted')){
                        continue;
                    }

                    $facts .= $content . "\n";
                }
            }
        }

        $facts = '<facts>' . $facts . '</facts>';

        $systemPrompt = '
        Wygeneruj słowa kluczowe w formie mianownika (czyli np. “sportowiec”, a nie “sportowcem”, “sportowców” itp.),
        które pomogą ludziom z centrali wyszukać odpowiedni dokument.
        Zwróć uwagę na różne szczegóły, takie jak np zwierzęta itp Tagi mają odzwierciedlać kluczowe aspekty tekstu np. w którym sektorze znaleziono odciski palców Barbary Zawadzkiej.
        Słowa kluczowe powinny być podane w formie listy, każde słowo kluczowe powinno być oddzielone przecinkiem.
        <example_result>
        nauczyciel, Aleksander Ragowski, Szkoła Podstawowa nr 9, (...)
        </example_result>
        ';

        $directory = base_path('app/Aidevs/taskData/pliki_z_fabryki');

        try{


            $fileKeywords = [];
            if (is_dir($directory)) {
                $files = scandir($directory);
                // Przechodzimy przez każdy element
                foreach ($files as $file) {
                    // Pomijamy '.' i '..'
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $directory . '/' . $file;

                        if (is_file($filePath)) {
                            $content = file_get_contents($filePath);

                            $userContent = '<file_name>' . $file . '</file_name> \n   '. $facts . ' <content>' . $content . '</content>';

                            $answer = OpenAiHelper::getResult(
                                user: $userContent,
                                system: $systemPrompt,
                                model: OpenAiModel::GPT4O
                            );

                            $fileKeywords[$file] = $answer;
                        }

                    }
                }
            }

        }catch (Exception $e){
            dump($filePath, $file, $e);
        }


        dump($fileKeywords);

        TaskService::poligon('https://centrala.ag3nts.org/report','dokumenty', $fileKeywords);
        return '';
    }

    public static function task12(){
        //        TaskService::poligon('https://centrala.ag3nts.org/report','wektory', '2024-02-21');
        $query = 'W raporcie, z którego dnia znajduje się wzmianka o kradzieży prototypu broni?';

//        $c = '';
//
//        $directory = base_path('app/Aidevs/taskData/pliki_z_fabryki/weapons_tests');
//        $facts = '';
//        if (is_dir($directory)) {
//            $files = scandir($directory);
//            // Przechodzimy przez każdy element
//            foreach ($files as $file) {
//                // Pomijamy '.' i '..'
//                if ($file !== '.' && $file !== '..') {
//                    $filePath = $directory . '/' . $file;
//
//                    $content = file_get_contents($filePath);
//                    $c .=  ' \n\n' . $content;
//
//                    $answer = OpenAiHelper::getResult(
//                        user: 'Text: ' . $content . '\n\n Filename: ' . $file,
//                        system: 'Przygotuj informacje do zapisanie w bazie wektorowej. Nadaj cały konspekt, słowa kluczowe. Zwróć szczególną uwagę nad informacjami związanymi z datą'
//                    );
//                    QdrantHelper::addPoint('Text: ' . $content . '\n\n Filename: ' . $file, 'test');
//                }
//            }
//        }

        $searchResults = QdrantHelper::search($query, 'test');

        $contents = '';
        foreach ($searchResults as $element) {
            $content = $element['payload']['meta'];
            $answer = OpenAiHelper::getResult(
                user: 'Query: ' . $query . '\n Text:' . $content,
                system: 'You are a helpful assistant that determines if a given text is relevant to a query. Respond with 1 if relevant, 0 if not relevant.',
                model: OpenAiModel::GPT4O
            );

            $contents .= '\n\n<file> ' . $content . ' </file>';
        }

        $systemPrompt = 'Jesteś asystentem do wyszukiwania dat na postawie konspektu oraz nazwy pliku. Przeanalizuj treść oraz nazwe każdego z pliku aby odpowiedzieć na pytanie użytkownika. ### Baza wiedzy \n\n ' . $contents;

        $answer = OpenAiHelper::getResult(
            user: $query,
            system:$systemPrompt
        );

        print_r($contents);

        dd($answer , $systemPrompt, $contents, $searchResults);
    }
}
