<?php

declare(strict_types=1);

namespace App\Services;

use Anthropic;
use App\Enums\OpenAiModel;
use App\Magisterka\CodeReviewAnalyzerService;
use App\Magisterka\DocumentationFileLoader;
use Illuminate\Support\Facades\Http;

class PracaMagisterska
{
    public function __construct(

    ){}

    public function test()
    {
        $this->testCodePrice();
    }

    public function testCodePrice(): void
    {
        $systemPrompt = 'Przeanalizuj kod, aby zidentyfikować wszelkie problemy lub naruszenia tych zasad. Jasno wyjaśnij problem w zrozumiały sposób,
        a następnie dostarczyć poprawioną lub ulepszoną wersję kodu.

        # Steps
        1. Thinking - think out loud about the quality of the code, what elements need improvement
        1. **Code Analysis**: Examine the given code for violations or issues related to SOLID principles, KISS, DRY, and design patterns.
        2. **Problem Explanation**: Clearly articulate the issues found, ensuring the explanation is easily understandable.
        3. **Code Improvement**: Propose a solution or improved code that adheres to the best practices and principles.

        # Output Format

        Provide a two-part response:
        1. **Explanation**: A concise but thorough explanation of the issues identified in the original code.
        2. **Improved Code**: A corrected or improved version of the code that follows SOLID, KISS, DRY, and utilizes appropriate design patterns.

        ## note
        Odpowiedź daj w języku polskim';

        $code = '<?php

        function calculatePrice($productType, $price, $taxRate, $discountRate = 0) {
            if ($productType === \'book\') {
                $taxRate = 0; // Ksiazki sa zwolnione z podatku
            }
            $priceWithTax = $price + ($price * $taxRate);
            $finalPrice = $priceWithTax - ($priceWithTax * $discountRate);

            return $finalPrice;
        }

        function printReceipt($productName, $price, $taxRate, $discountRate, $productType) {
            $finalPrice = calculatePrice($productType, $price, $taxRate, $discountRate);

            echo "Product: $productName\n";
            echo "Type: $productType\n";
            echo "Price: $price\n";
            echo "Final Price: $finalPrice\n";
        }

        // Wywolanie funkcji
        printReceipt("Harry Potter", 50, 0.23, 0.1, "book");';


//        // ANTHROPIC - CODE
//        $client = Anthropic::client($_ENV['ANTHROPIC_KEY']);
//
//        $result = $client->messages()->create([
//            'model' => 'claude-3-5-sonnet-latest',
//            'max_tokens' => 1024,
//            'messages' => [
//                ['role' => 'assistant', 'content' => $systemPrompt],
//                ['role' => 'user', 'content' => $code]
//            ]
//        ]);
//
//        dd($result);

        // OPEN API

        $settings = [];
        $settings['model'] = OpenAiModel::GPT4O_MINI->value;

        $systemPrompt = mb_convert_encoding($systemPrompt, 'UTF-8', 'auto');
        $userContent = mb_convert_encoding($code, 'UTF-8', 'auto');

        // Przygotowanie wiadomosci
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userContent],
        ];

        $result = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions',
                array_merge($settings, ['messages' => $messages])
            );


        $result = json_decode($result->body(), true);
        echo $result['choices'][0]['message']['content'];
        dd($result['choices'][0]['message']['content'],$result);
        $result = $result['choices'][0]['message']['content'];


    }


    /**
     * Oblicza cenę końcową produktu z uwzględnieniem podatku.
     *
     * Funkcja przyjmuje cenę bazową produktu oraz stawkę podatku wyrażoną jako ułamek dziesiętny i zwraca cenę końcową,
     * w której cena bazowa została powiększona o wartość podatku. Opis zawiera również jednostki miary oraz przykład użycia.
     *
     * @param float $price Cena bazowa produktu w złotych.
     * @param float $taxRate Stawka podatku wyrażona jako ułamek dziesiętny (np. 0.23 oznacza 23%).
     * @return float Cena końcowa produktu.
     *
     * Przykład użycia:
     * <code>
     * echo calculateTotal(100, 0.23); // Wynik: 123.0
     * </code>
     */
    function calculateTotal($price, $taxRate) {
        return $price * (1 + $taxRate);
    }


    public function codeReviewCodeFromFileVersionOne(): string
    {
        $path = app_path('Magisterka/example_code_sa.txt');

        $code = file_get_contents($path);
        $analyze = CodeReviewAnalyzerService::analyze($code);
        $documentations = DocumentationFileLoader::loadAllDocByAnalyze($analyze);

        $systemPrompt = 'Przeanalizuj kod, aby zidentyfikować wszelkie problemy lub naruszenia tych zasad. Jasno wyjaśnij problem w zrozumiały sposób,
        a następnie dostarczyć poprawioną lub ulepszoną wersję kodu.

        # Steps
        1. Thinking - think out loud about the qauality of the code, what elements need improvement
        1. **Code Analysis**: Examine the given code for violations or issues related to SOLID principles, KISS, DRY, and design patterns.
        2. **Problem Explanation**: Clearly articulate the issues found, ensuring the explanation is easily understandable.
        3. **Code Improvement**: Propose a solution or improved code that adheres to the best practices and principles.

        # Output Format

        Provide a two-part response:
        1. **Explanation**: A concise but thorough explanation of the issues identified in the original code.
        2. **Improved Code**: A corrected or improved version of the code that follows SOLID, KISS, DRY, and utilizes appropriate design patterns.

        ## note
        Odpowiedź daj w języku polskim';


        if(!empty($documentations)){
            $systemPrompt .= '## Uwzględnij również w swojej CR poniższą dokumentację: \n\n' . implode("\n\n", $documentations);
        }


        // ANTHROPIC - CODE
        $client = Anthropic::client($_ENV['ANTHROPIC_KEY']);

//        $result = $client->messages()->create([
//            'model' => 'claude-3-5-sonnet-latest',
//            'max_tokens' => 1024,
//            'messages' => [
//                ['role' => 'assistant', 'content' => $systemPrompt],
//                ['role' => 'user', 'content' => $code]
//            ]
//        ]);

        dd($result);
    }
}
